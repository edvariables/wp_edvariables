<?php 
/**
 * edv Admin -> Évènement
 * Custom post type for WordPress in Admin UI.
 * 
 * Capabilities
 * Colonnes de la liste des évènements
 * Dashboard
 *
 * Voir aussi edv_Post
 */
class edv_Admin_Post {

	public static function init() {

		self::init_hooks();
	}

	public static function init_hooks() {
		add_action( 'admin_head', array(__CLASS__, 'init_post_type_supports'), 10, 4 );
		add_filter( 'map_meta_cap', array(__CLASS__, 'map_meta_cap'), 10, 4 );
		
		//add custom columns for list view
		add_filter( 'manage_' . edv_Post::post_type . '_posts_columns', array( __CLASS__, 'manage_columns' ) );
		add_action( 'manage_' . edv_Post::post_type . '_posts_custom_column', array( __CLASS__, 'manage_custom_columns' ), 10, 2 );
		//set custom columns sortable
		add_filter( 'manage_edit-' . edv_Post::post_type . '_sortable_columns', array( __CLASS__, 'manage_sortable_columns' ) );
		if(basename($_SERVER['PHP_SELF']) === 'edit.php'
		&& isset($_GET['post_type']) && $_GET['post_type'] === edv_Post::post_type)
			add_action( 'pre_get_posts', array( __CLASS__, 'on_pre_get_posts'), 10, 1);

		add_action( 'wp_dashboard_setup', array(__CLASS__, 'add_dashboard_widgets'), 10 ); //dashboard
	}
	/****************/

	/**
	 * Liste de évènements
	 */
	public static function manage_columns( $columns ) {
		unset( $columns );
		$columns = array(
			'cb'     => __( 'Sélection', EDV_TAG ),
			'titre'     => __( 'Titre', EDV_TAG ),
			'dates'     => __( 'Date(s)', EDV_TAG ),
			'details'     => __( 'Détails', EDV_TAG ),
			'edp_category'     => __( 'Catégories', EDV_TAG ),
			'organisateur'     => __( 'Organisateur', EDV_TAG ),
			'diffusion'      => __( 'Diffusion', EDV_TAG ),
			'author'        => __( 'Auteur', EDV_TAG ),
			'date'      => __( 'Date', EDV_TAG )
		);
		return $columns;
	}
	public static function manage_custom_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'titre' :
				//Evite la confusion avec edv_Post::the_title
				$post = get_post( $post_id );
				echo $post->post_title;
				
				break;
			case 'organisateur' :
				$organisateur = get_post_meta( $post_id, 'edp-organisateur', true );
				$email = get_post_meta( $post_id, 'edp-email', true );
				echo $organisateur . ($email && $organisateur ? ' - ' : '' ) . $email;
				
				break;
			case 'dates' :
				echo edv_Post::get_event_dates_text( $post_id );
				break;
			case 'edp_category' :
				the_terms( $post_id, $column, '<cite class="entry-terms">', ', ', '</cite>' );
				break;
			case 'diffusion' :
				the_terms( $post_id, $column, '<cite class="entry-terms">', ', ', '</cite>' );
				break;
			case 'details' :
				$post = get_post( $post_id );
				$localisation = get_post_meta( $post_id, 'edp-localisation', true );
				if(strlen($localisation)>20)
						$localisation = trim(substr($localisation, 0, 20)) . '...';
				// $organisateur = get_post_meta( $post_id, 'edp-organisateur', true );
				$description = $post->post_content;
				if(strlen($description)>20)
						$description = trim(substr($description, 0, 20)) . '...';
				$siteweb    = get_post_meta( $post_id, 'edp-siteweb', true );
				$phone    = get_post_meta( $post_id, 'edp-phone', true );
				echo trim(
					  ($localisation ? $localisation . ' - ' : '')
					// . ($organisateur ? $organisateur . ' - ' : '')
					. ($description ? $description . ' - ' : '')
					. ($siteweb ? make_clickable( esc_html($siteweb) ) . ' - ' : '')
					. ($phone ? antispambot($phone) : '')
				);
				break;
			default:
				break;
		}
	}

	public static function manage_sortable_columns( $columns ) {
		$columns['titre']    = 'titre';
		$columns['author']    = 'author';
		$columns['dates'] = 'dates';
		$columns['details'] = 'details';
		$columns['diffusion'] = 'diffusion';
		$columns['organisateur'] = 'organisateur';
		return $columns;
	}
	/**
	 * Sort custom column
	 */
	public static function on_pre_get_posts( $query ) {
		global $wpdb;
		if(empty($query->query_vars)
		|| empty($query->query_vars['orderby']))
			return;
		switch( $query->query_vars['orderby']) {
			case 'dates':
				$query->set('meta_key','edp-date-debut');  
				$query->set('orderby','meta_value');  
			case 'organisateur':
				$query->set('meta_key','edp-email');  
				$query->set('orderby','meta_value');  
		}
	}
	/****************/

	/**
	 * N'affiche le bloc Auteur qu'en Archive (liste) / modification rapide
	 * N'affiche l'éditeur que pour l'évènement modèle ou si l'option edv::edpost_show_content_editor
	 */
	public static function init_post_type_supports(){
		global $post;
		if( current_user_can('manage_options') ){
			if(is_archive()){
				add_post_type_support( 'edpost', 'author' );
			}
		}
	}

	/**
	 * map_meta_cap
	 TODO all
	 */
	public static function map_meta_cap( $caps, $cap, $user_id, $args ) {

		if( 0 ) {
			echo "<br>\n-------------------------------------------------------------------------------";
			print_r(func_get_args());
			/*echo "<br>\n-----------------------------------------------------------------------------";
			print_r($caps);*/
		}
		if($cap == 'edit_edposts'){
			//var_dump($cap, $caps);
					$caps = array();
					//$caps[] = ( current_user_can('manage_options') ) ? 'read' : 'do_not_allow';
					$caps[] = 'read';
			return $caps;
		}
		/* If editing, deleting, or reading an event, get the post and post type object. */
		if ( 'edit_edpost' == $cap || 'delete_edpost' == $cap || 'read_edpost' == $cap ) {
			$post = get_post( $args[0] );
			$post_type = get_post_type_object( $post->post_type );

			/* Set an empty array for the caps. */
			$caps = array();
		}

		/* If editing an event, assign the required capability. */
		if ( 'edit_edpost' == $cap ) {
			if ( $user_id == $post->post_author )
				$caps[] = $post_type->cap->edit_posts;
			else
				$caps[] = $post_type->cap->edit_others_posts;
		}

		/* If deleting an event, assign the required capability. */
		elseif ( 'delete_edpost' == $cap ) {
			if ( $user_id == $post->post_author )
				$caps[] = $post_type->cap->delete_posts;
			else
				$caps[] = $post_type->cap->delete_others_posts;
		}

		/* If reading a private event, assign the required capability. */
		elseif ( 'read_edpost' == $cap ) {

			if ( 'private' != $post->post_status )
				$caps[] = 'read';
			elseif ( $user_id == $post->post_author )
				$caps[] = 'read';
			else
				$caps[] = $post_type->cap->read_private_posts;
		}

		/* Return the capabilities required by the user. */
		return $caps;
	}

	/**
	 * dashboard_widgets
	 */

	/**
	 * Init
	 */
	public static function get_my_edposts($num_posts = 5) {
		global $wpdb;
		$blog_prefix = $wpdb->get_blog_prefix();
	    $current_user = wp_get_current_user();
		$user_id = $current_user->ID;
		$user_email = $current_user->user_email;
		
		$sql = "SELECT post.ID, post.post_title, post.post_name, post.post_status, post.post_date, post.post_modified"
			. "\n FROM {$blog_prefix}posts post"
			. "\n INNER JOIN {$blog_prefix}postmeta post_email"
			. "\n ON post_email.post_id = post.ID"
			. "\n AND post_email.meta_key = 'edp-email'"
			. "\n INNER JOIN {$blog_prefix}postmeta post_date_debut"
			. "\n ON post_date_debut.post_id = post.ID"
			. "\n AND post_date_debut.meta_key = 'edp-date-debut'"
			. "\n INNER JOIN {$blog_prefix}postmeta post_date_fin"
			. "\n ON post_date_fin.post_id = post.ID"
			. "\n AND post_date_fin.meta_key = 'edp-date-fin'"
			. "\n WHERE post.post_type = '".edv_Post::post_type."'"
			. "\n AND post.post_status IN ('publish', 'pending', 'draft')"
			. "\n AND GREATEST(post_date_debut.meta_value, post_date_fin.meta_value) >= CURRENT_DATE()"
			. "\n AND post.post_author = {$user_id}"
			. "\n AND ( post.post_author = {$user_id}"
				. "\n OR post_email.meta_value = '{$user_email}')"
			. "\n ORDER BY post.post_modified DESC"
			. "\n LIMIT {$num_posts}"
			;
		$dbresults = $wpdb->get_results($sql);
		if( is_a($dbresults, 'WP_Error') )
			throw $dbresults;
		return $dbresults;
	}
	
	/**
	 * Init
	 */
	public static function add_dashboard_widgets() {
	    global $wp_meta_boxes;
		//TODO : trier par les derniers ajoutés
		//TODO : author OR email
		$edposts = self::get_my_edposts(5);
	    if( count($edposts) ) {
			add_meta_box( 'dashboard_my_edposts',
				__('Mes évènements', EDV_TAG),
				array(__CLASS__, 'dashboard_my_edposts_cb'),
				'dashboard',
				'normal',
				'high',
				array('edposts' => $edposts) );
		}

	    if( class_exists('edv_Admin_Multisite')
		&& (WP_DEBUG || is_multisite())){
			$blogs = edv_Admin_Multisite::get_other_blogs_of_user();
			if( $blogs && count($blogs) ) {
				add_meta_box( 'dashboard_my_blogs',
					__('Mes autres sites edv', EDV_TAG),
					array(__CLASS__, 'dashboard_my_blogs_cb'),
					'dashboard',
					'normal',
					'high',
					array('blogs' => $blogs) );
			}
		}
		
		if(current_user_can('manage_options')
		|| current_user_can('edpost')){
		    $edposts = edv_Posts::get_posts( 10, [
				'post_status' => ['publish', 'pending', 'draft']
			]);
			if( count($edposts) ) {
				add_meta_box( 'dashboard_all_edposts',
					__('Les évènements', EDV_TAG),
					array(__CLASS__, 'dashboard_all_edposts_cb'),
					'dashboard',
					'side',
					'high',
					array('edposts' => $edposts) );
			}
		}
	}

	/**
	 * Callback
	 */
	public static function dashboard_my_blogs_cb($post , $widget) {
		$blogs = $widget['args']['blogs'];
		?><ul><?php
		foreach($blogs as $blog){
			//;
			echo '<li>';
			?><header class="entry-header"><?php 
				echo sprintf('<h3 class="entry-title"><a href="%s/wp-admin"><img src="%s" class="coo-favicon"/>%s</a></h3>', $blog->siteurl, $blog->siteurl . '/favicon.ico', $blog->blogname);
			?></header><?php
			echo '</li>';
			
		}
		?></ul><?php
	}

	/**
	 * Callback
	 */
	public static function dashboard_my_edposts_cb($post , $widget) {
		$edposts = $widget['args']['edposts'];
		$edit_url = current_user_can('manage_options');
		$post_statuses = get_post_statuses();
		?><ul><?php
		foreach($edposts as $edpost){
			echo '<li>';
			?><header class="entry-header"><?php 
				if($edit_url)
					$url = get_edit_post_link($edpost);
				else
					$url = edv_Post::get_post_permalink($edpost);
				echo sprintf( '<h3 class="entry-title"><a href="%s">%s</a></h3>', $url, edv_Post::get_post_title($edpost) );
				the_terms( $edpost->ID, edv_Post::taxonomy_edp_category, 
					sprintf( '<div><cite class="entry-terms">' ), ', ', '</cite></div>' );
				$the_date = get_the_date('', $edpost);
				$the_modified_date = get_the_modified_date('', $edpost);
				$html = sprintf('<span>ajouté le %s</span>', $the_date) ;
				if($the_date != $the_modified_date)
					$html .= sprintf('<span>, mis à jour le %s</span>', $the_modified_date) ;
				if($edpost->post_status != 'publish')
					$html .= sprintf('<br><b>%s</b>', edv::icon( 'warning',$post_statuses[$edpost->post_status])) ;		
				echo sprintf( '<cite>%s</cite>', $html);		
			?></header><?php
			/*?><div class="entry-summary">
				<?php echo get_the_excerpt($edpost); //TODO empty !!!!? ?>
			</div><?php */
			echo '<hr></li>';
			
		}
		?></ul><?php
	}

	/**
	 * Callback
	 */
	public static function dashboard_all_edposts_cb($post , $widget) {
		$edposts = $widget['args']['edposts'];
		$today_date = date(get_option( 'date_format' ));
		$max_rows = 4;
		$post_statuses = get_post_statuses();
		?><ul><?php
		foreach($edposts as $edpost){
			echo '<li>';
			edit_post_link( edv_Post::get_post_title($edpost), '<h3 class="entry-title">', '</h3>', $edpost );
			$the_date = get_the_date('', $edpost);
			$the_modified_date = get_the_modified_date('', $edpost);
			$html = '';
			$html .= sprintf('<span>ajouté le %s</span>', $the_date) ;
			if($the_date != $the_modified_date)
				$html .= sprintf('<span>, mis à jour le %s</span>', $the_modified_date) ;
			if($edpost->post_status != 'publish')
				$html .= sprintf('<br><b>%s</b>', edv::icon( 'warning', $post_statuses[$edpost->post_status])) ;
			
			$meta_key = 'edp-email';
			$value = edv_Post::get_post_meta($edpost, $meta_key, true);
			if($value)
				$html .= sprintf(' - %s', $value);
			
			echo sprintf( '<cite>%s</cite>', $html);		
			echo '<hr></li>';

			if( --$max_rows <= 0 && $the_modified_date != $today_date )
				break;
		}
		echo sprintf('<li><a href="%s">%s...</a></li>', get_home_url( null, 'wp-admin/edit.php?post_type=' . edv_Post::post_type), __('Tous les évènements', EDV_TAG));
		?>
		</ul><?php
	}
	
	/**
	 *
	 *
	 * cf edv_Admin::on_wp_ajax_admin_action_cb
	 */
	public static function on_wp_ajax_action_insert_term($data){
		$tax_name = $data['taxonomy'];
		$term = $data['term'];
		$result = wp_insert_term($term, $tax_name
			, array(
				'slug' => sanitize_title($term)
			)
		);
		if( is_wp_error($result) )
			return edv::icon('warning', $result->get_error_message());
		return edv::icon('info', sprintf('Le terme "%s" a été ajouté.', $tax_name));
	}

}
?>