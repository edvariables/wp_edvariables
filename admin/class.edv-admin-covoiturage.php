<?php 
/**
 * edv Admin -> Covoiturage
 * Custom post type for WordPress in Admin UI.
 * 
 * Capabilities
 * Colonnes de la liste des covoiturages
 * Dashboard
 *
 * Voir aussi edv_Covoiturage
 */
class edv_Admin_Covoiturage {

	public static function init() {

		self::init_hooks();
	}

	public static function init_hooks() {
		add_action( 'admin_head', array(__CLASS__, 'init_post_type_supports'), 10, 4 );
		add_filter( 'map_meta_cap', array(__CLASS__, 'map_meta_cap'), 10, 4 );
		
		//add custom columns for list view
		add_filter( 'manage_' . edv_Covoiturage::post_type . '_posts_columns', array( __CLASS__, 'manage_columns' ) );
		add_action( 'manage_' . edv_Covoiturage::post_type . '_posts_custom_column', array( __CLASS__, 'manage_custom_columns' ), 10, 2 );
		//set custom columns sortable
		add_filter( 'manage_edit-' . edv_Covoiturage::post_type . '_sortable_columns', array( __CLASS__, 'manage_sortable_columns' ) );
		if(basename($_SERVER['PHP_SELF']) === 'edit.php'
		&& isset($_GET['post_type']) && $_GET['post_type'] === edv_Covoiturage::post_type)
			add_action( 'pre_get_posts', array( __CLASS__, 'on_pre_get_posts'), 10, 1);

		add_action( 'wp_dashboard_setup', array(__CLASS__, 'add_dashboard_widgets'), 10 ); //dashboard
	}
	/****************/

	/**
	 * Liste de covoiturages
	 */
	public static function manage_columns( $columns ) {
		unset( $columns );
		$columns = array(
			'cb'     => __( 'Sélection', EDV_TAG ),
			'cov_intention'     => __( 'Intention', EDV_TAG ),
			'titre'     => __( 'Titre', EDV_TAG ),
			'dates'     => __( 'Date(s)', EDV_TAG ),
			'details'     => __( 'Détails', EDV_TAG ),
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
				//Evite la confusion avec edv_Covoiturage::the_title
				$post = get_post( $post_id );
				echo $post->post_title;
				
				break;
			case 'cov_intention' :
				$post = get_post( $post_id );
				echo edv_Covoiturage_Post_type::get_intention_label(get_post_meta( $post_id, 'cov-intention', true ));
				
				break;
			case 'organisateur' :
				$organisateur = get_post_meta( $post_id, 'cov-organisateur', true );
				$email = get_post_meta( $post_id, 'cov-email', true );
				echo $organisateur . ($email && $organisateur ? ' - ' : '' ) . $email;
				
				break;
			case 'dates' :
				echo edv_Covoiturage::get_covoiturage_dates_text( $post_id );
				break;
			case 'diffusion' :
				the_terms( $post_id, $column, '<cite class="entry-terms">', ', ', '</cite>' );
				break;
			case 'details' :
				$post = get_post( $post_id );
				$description = $post->post_content;
				if(strlen($description)>20)
						$description = trim(substr($description, 0, 20)) . '...';
				$phone      = get_post_meta( $post_id, 'cov-phone', true );
				$phone_show = get_post_meta( $post_id, 'cov-phone-show', true );
				echo trim(
					  ($description ? $description . ' - ' : '')
					. ($phone && $phone_show ? antispambot($phone) : '')
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
				$query->set('meta_key','cov-date-debut');  
				$query->set('orderby','meta_value');  
			case 'organisateur':
				$query->set('meta_key','cov-email');  
				$query->set('orderby','meta_value');  
		}
	}
	/****************/

	/**
	 * N'affiche le bloc Auteur qu'en Archive (liste) / modification rapide
	 * N'affiche l'éditeur que pour le covoiturage modèle ou si l'option edv::covoiturage_show_content_editor
	 */
	public static function init_post_type_supports(){
		global $post;
		if( current_user_can('manage_options') ){
			if(is_archive()){
				add_post_type_support( 'covoiturage', 'author' );
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
		if($cap == 'edit_covoiturages'){
			//var_dump($cap, $caps);
					$caps = array();
					//$caps[] = ( current_user_can('manage_options') ) ? 'read' : 'do_not_allow';
					$caps[] = 'read';
			return $caps;
		}
		/* If editing, deleting, or reading an event, get the post and post type object. */
		if ( 'edit_covoiturage' == $cap || 'delete_covoiturage' == $cap || 'read_covoiturage' == $cap ) {
			$post = get_post( $args[0] );
			$post_type = get_post_type_object( $post->post_type );

			/* Set an empty array for the caps. */
			$caps = array();
		}

		/* If editing an event, assign the required capability. */
		if ( 'edit_covoiturage' == $cap ) {
			if ( $user_id == $post->post_author )
				$caps[] = $post_type->cap->edit_posts;
			else
				$caps[] = $post_type->cap->edit_others_posts;
		}

		/* If deleting an event, assign the required capability. */
		elseif ( 'delete_covoiturage' == $cap ) {
			if ( $user_id == $post->post_author )
				$caps[] = $post_type->cap->delete_posts;
			else
				$caps[] = $post_type->cap->delete_others_posts;
		}

		/* If reading a private event, assign the required capability. */
		elseif ( 'read_covoiturage' == $cap ) {

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
	public static function get_my_covoiturages($num_posts = 5) {
		global $wpdb;
		$blog_prefix = $wpdb->get_blog_prefix();
	    $current_user = wp_get_current_user();
		$user_id = $current_user->ID;
		$user_email = $current_user->user_email;
		
		$sql = "SELECT post.ID, post.post_title, post.post_name, post.post_status, post.post_date, post.post_modified"
			. "\n FROM {$blog_prefix}posts post"
			. "\n INNER JOIN {$blog_prefix}postmeta post_email"
			. "\n ON post_email.post_id = post.ID"
			. "\n AND post_email.meta_key = 'cov-email'"
			. "\n INNER JOIN {$blog_prefix}postmeta post_date_debut"
			. "\n ON post_date_debut.post_id = post.ID"
			. "\n AND post_date_debut.meta_key = 'cov-date-debut'"
			. "\n WHERE post.post_type = '".edv_Covoiturage::post_type."'"
			. "\n AND post.post_status IN ('publish', 'pending', 'draft')"
			. "\n AND post_date_debut.meta_value >= CURRENT_DATE()"
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
		$covoiturages = self::get_my_covoiturages(5);
	    if( count($covoiturages) ) {
			add_meta_box( 'dashboard_my_covoiturages',
				__('Mes covoiturages', EDV_TAG),
				array(__CLASS__, 'dashboard_my_covoiturages_cb'),
				'dashboard',
				'normal',
				'high',
				array('covoiturages' => $covoiturages) );
		}
		
		if(current_user_can('manage_options')
		|| current_user_can('covoiturage')){
		    $covoiturages = edv_Covoiturages::get_posts( 10, [
				'post_status' => ['publish', 'pending', 'draft']
				, 'orderby' => ['post_modified' => 'DESC']
			]);
			if( count($covoiturages) ) {
				add_meta_box( 'dashboard_all_covoiturages',
					__('Les covoiturages', EDV_TAG),
					array(__CLASS__, 'dashboard_all_covoiturages_cb'),
					'dashboard',
					'side',
					'high',
					array('covoiturages' => $covoiturages) );
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
	public static function dashboard_my_covoiturages_cb($post , $widget) {
		$covoiturages = $widget['args']['covoiturages'];
		$edit_url = current_user_can('manage_options');
		$post_statuses = get_post_statuses();
		?><ul><?php
		foreach($covoiturages as $covoiturage){
			echo '<li>';
			?><header class="entry-header"><?php 
				if($edit_url)
					$url = get_edit_post_link($covoiturage);
				else
					$url = edv_Covoiturage::get_post_permalink($covoiturage);
				echo sprintf( '<h3 class="entry-title"><a href="%s">%s</a></h3>', $url, edv_Covoiturage::get_post_title($covoiturage) );
				$the_date = get_the_date('', $covoiturage);
				$the_modified_date = get_the_modified_date('', $covoiturage);
				$html = sprintf('<span>publié le %s</span>', $the_date) ;
				if($the_date != $the_modified_date)
					$html .= sprintf('<span>, mis à jour le %s</span>', $the_modified_date) ;
				if($covoiturage->post_status != 'publish')
					$html .= sprintf('<br><b>%s</b>', edv::icon( 'warning',$post_statuses[$covoiturage->post_status])) ;		
				echo sprintf( '<cite>%s</cite>', $html);		
			?></header><?php
			/*?><div class="entry-summary">
				<?php echo get_the_excerpt($covoiturage); //TODO empty !!!!? ?>
			</div><?php */
			echo '<hr></li>';
			
		}
		?></ul><?php
	}

	/**
	 * Callback
	 */
	public static function dashboard_all_covoiturages_cb($post , $widget) {
		$covoiturages = $widget['args']['covoiturages'];
		$today_date = date(get_option( 'date_format' ));
		$max_rows = 4;
		$post_statuses = get_post_statuses();
		?><ul><?php
		foreach($covoiturages as $covoiturage){
			echo '<li>';
			edit_post_link( edv_Covoiturage::get_post_title($covoiturage), '<h3 class="entry-title">', '</h3>', $covoiturage );
			$the_date = get_the_date('', $covoiturage);
			$the_modified_date = get_the_modified_date('', $covoiturage);
			$html = '';
			$html .= sprintf('<span>ajouté le %s</span>', $the_date) ;
			if($the_date != $the_modified_date)
				$html .= sprintf('<span>, mis à jour le %s</span>', $the_modified_date) ;
			if($covoiturage->post_status != 'publish')
				$html .= sprintf('<br><b>%s</b>', edv::icon( 'warning', $post_statuses[$covoiturage->post_status])) ;
			
			$meta_key = 'cov-email';
			$value = edv_Covoiturage::get_post_meta($covoiturage, $meta_key, true);
			if($value)
				$html .= sprintf(' - %s', $value);
			
			echo sprintf( '<cite>%s</cite>', $html);		
			echo '<hr></li>';

			if( --$max_rows <= 0 && $the_modified_date != $today_date )
				break;
		}
		echo sprintf('<li><a href="%s">%s...</a></li>', get_home_url( null, 'wp-admin/edit.php?post_type=' . edv_Covoiturage::post_type), __('Tous les covoiturages', EDV_TAG));
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