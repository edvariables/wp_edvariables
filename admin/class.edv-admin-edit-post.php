<?php

/**
 * edv Admin -> Edit -> Post
 * Custom post type for WordPress in Admin UI.
 * 
 * Edition d'un évènement
 * Définition des metaboxes et des champs personnalisés des Évènements 
 *
 * Voir aussi edv_Post, edv_Admin_Post
 */
class edv_Admin_Edit_Post extends edv_Admin_Edit_Post_Type {

	public static function init() {
		parent::init();

		self::init_hooks();
	}
	
	public static function init_hooks() {
		
		if(basename($_SERVER['PHP_SELF']) === 'post.php'
		&& isset($_POST['post_type'])
			&& $_POST['post_type'] === edv_Post::post_type 
		&& isset($_POST['post_status'])
			&& ! in_array($_POST['post_status'], [ 'trash', 'trashed' ]) ){
			add_filter( 'wp_insert_post_data', array(__CLASS__, 'wp_insert_post_data_cb'), 10, 2 );
		}
		
		if( in_array( basename($_SERVER['PHP_SELF']), [ 'revision.php', 'admin-ajax.php' ])) {
			add_filter( 'wp_get_revision_ui_diff', array(__CLASS__, 'on_wp_get_revision_ui_diff_cb'), 10, 3 );		
		}
		
		if(array_key_exists('post_type', $_POST)
		&& $_POST['post_type'] === edv_Post::post_type){
			/** validation du post_content **/
			add_filter( 'content_save_pre', array(__CLASS__, 'on_post_content_save_pre'), 10, 1 );

			/** save des meta values et + **/
			if(basename($_SERVER['PHP_SELF']) === 'post.php'){
				add_action( 'save_post_edpost', array(__CLASS__, 'save_post_edpost_cb'), 10, 3 );
			}
			/** initialisation des diffusions par défaut pour les nouveaux évènements */
			if(basename($_SERVER['PHP_SELF']) === 'post-new.php'){
				add_filter( 'wp_terms_checklist_args', array( __CLASS__, "on_wp_terms_checklist_args" ), 10, 2 ); 
			}
		}
		add_action( 'add_meta_boxes_' . edv_Post::post_type, array( __CLASS__, 'register_edpost_metaboxes' ), 10, 1 ); //edit
	}
	/****************/

	/**
	 * Callback lors de l'enregistrement d'un évènement.
	 * A ce stade, les metaboxes ne sont pas encore sauvegardées
	 */
	public static function wp_insert_post_data_cb ($data, $postarr ){
		if($data['post_type'] != edv_Post::post_type)
			return $data;
		
		if( array_key_exists('edp-create-user', $postarr) && $postarr['edp-create-user'] ){
			$data = self::create_user_on_save($data, $postarr);
		}
		
		//On sauve les révisions de meta_values
		$post_id = empty($postarr['post_ID']) ? $postarr['ID'] : $postarr['post_ID'];
		edv_Post_Edit::save_post_revision($post_id, $postarr, true);
		
		return $data;
	}
	
	/**
	 * Callback lors de l'enregistrement du post_content d'un évènement.
	 */
	public static function on_post_content_save_pre($value){
		// &amp; &gt; ...
		if( preg_match('/\&\w+\;/', $value ) !== false){
			$value = html_entity_decode( $value );
		}
		
		return $value;
	}
	/**
	 * Callback lors de l'enregistrement d'un évènement.
	 * A ce stade, les metaboxes ne sont pas encore sauvegardées
	 */
	public static function save_post_edpost_cb ($post_id, $post, $is_update){
		
		if( $post->post_status == 'trashed' ){
			return;
		}
		self::save_metaboxes($post_id, $post);
	}

	/**
	 * Lors du premier enregistrement, on crée l'utilisateur
	 * A ce stade, les metaboxes ne sont pas encore sauvegardées
	 */
	public static function create_user_on_save ($data, $postarr){
		/* $email = array_key_exists('edp-email', $postarr) ? $postarr['edp-email'] : false;
		if(!$email || !is_email($email)) {
			edv_Admin::add_admin_notice("Il manque l'adresse e-mail de l\'organisateur de l\'évènement ou elle est incorrecte.", 'error');
			return $data;
		}
		$user_name = array_key_exists('edp-organisateur', $postarr) ? $postarr['edp-organisateur'] : false;
		$user_login = array_key_exists('edp-create-user-slug', $postarr) ? $postarr['edp-create-user-slug'] : false;
	
		$user_data = array(
			'description' => 'Évènement ' . $data['post_title'],
		);
		$user = edv_User::create_user_for_edpost($email, $user_name, $user_login, $user_data, 'subscriber');
		if( is_wp_error($user)) {
			edv_Admin::add_admin_notice($user, 'error');
			return;
		}
		if($user){
			unset($_POST['edp-create-user']);
			unset($postarr['edp-create-user']);

			$data['post_author'] = $user->ID;
			//edv_Admin::add_admin_notice(debug_print_backtrace(), 'warning');
			edv_Admin::add_admin_notice("Désormais, l'auteur de la page est {$user->display_name}", 'success');
		}

		return $data; */
	}

	/**
	 * Register Meta Boxes (boite en édition de l'évènement)
	 */
	public static function register_edpost_metaboxes($post){
		add_meta_box('edv_edpost-dates', __('Dates de l\'évènement', EDV_TAG), array(__CLASS__, 'metabox_callback'), edv_Post::post_type, 'normal', 'high');
		add_meta_box('edv_edpost-description', __('Description', EDV_TAG), array(__CLASS__, 'metabox_callback'), edv_Post::post_type, 'normal', 'high');
		add_meta_box('edv_edpost-organisateur', __('Organisateur de l\'évènement', EDV_TAG), array(__CLASS__, 'metabox_callback'), edv_Post::post_type, 'normal', 'high');
		add_meta_box('edv_edpost-general', __('Informations générales', EDV_TAG), array(__CLASS__, 'metabox_callback'), edv_Post::post_type, 'normal', 'high');
				
		if( current_user_can('manage_options') ){
			self::register_metabox_admin();
		}
	}

	/**
	 * Register Meta Box pour un nouvel évènement.
	 Uniquement pour les admins
	 */
	public static function register_metabox_admin(){
		$title = self::$the_post_is_new ? __('Nouvel évènement', EDV_TAG) : __('Évènement', EDV_TAG);
		add_meta_box('edv_edpost-admin', $title, array(__CLASS__, 'metabox_callback'), edv_Post::post_type, 'side', 'high');
	}

	/**
	 * Callback
	 */
	public static function metabox_callback($post, $metabox){
		//var_dump(func_get_args());
		
		switch ($metabox['id']) {
			
			case 'edv_edpost-dates':
				parent::metabox_html( self::get_metabox_dates_fields(), $post, $metabox );
				break;
			
			case 'edv_edpost-description':
				parent::metabox_html( self::get_metabox_description_fields(), $post, $metabox );
				break;
			
			case 'edv_edpost-organisateur':
				parent::metabox_html( self::get_metabox_organisateur_fields(), $post, $metabox );
				break;
			
			case 'edv_edpost-general':
				parent::metabox_html( self::get_metabox_general_fields(), $post, $metabox );
				break;
			
			case 'edv_edpost-admin':
				self::post_author_metabox_field( $post );
				parent::metabox_html( self::get_metabox_admin_fields(), $post, $metabox );
				break;
			
			default:
				break;
		}
	}

	public static function get_metabox_all_fields(){
		return array_merge(
			// self::get_metabox_titre_fields(),
			self::get_metabox_dates_fields(),
			self::get_metabox_description_fields(),
			self::get_metabox_organisateur_fields(),
			self::get_metabox_general_fields()
		);
	}	

	// public static function get_metabox_titre_fields(){
		// return array(
			// array('name' => 'edp-titre',
				// 'label' => false )
		// );
	// }	

	public static function get_metabox_dates_fields(){
		return array(
			array('name' => 'edp-date-debut',
				'label' => __('Date de début', EDV_TAG),
				'input' => 'date',
				'fields' => array(array(
					'name' => 'edp-date-journee-entiere',
					'label' => __('toute la journée', EDV_TAG),
					'type' => 'checkbox',
					'default' => '0'
				))
			),
			array('name' => 'edp-heure-debut',
				'label' => __('Heure de début', EDV_TAG),
				'input' => 'time'
			),
			array('name' => 'edp-date-fin',
				'label' => __('Date de fin', EDV_TAG),
				'input' => 'date'
			),
			array('name' => 'edp-heure-fin',
				'label' => __('Heure de fin', EDV_TAG),
				'input' => 'time'
			)
		);
	}

	public static function get_metabox_description_fields(){
		
		return array(
			array('name' => 'post_content',
				'label' => false,
				'input' => 'tinymce',
				'settings' => array (
					'textarea_rows' => 7
				)
			),
			array('name' => 'edp-siteweb',
				'label' => __('Site Web de l\'évènement', EDV_TAG),
				'type' => 'url'
			),
			array('name' => 'edp-localisation',
				'label' => __('Lieu de l\'évènement', EDV_TAG),
				'input' => 'text'
			)
		);
	}	

	public static function get_metabox_organisateur_fields(){

		$field_show = array(
			'name' => '%s-show',
			'label' => __('afficher sur le site', EDV_TAG),
			'type' => 'checkbox',
			'default' => '1'
		);
				
		$fields = array(
			array('name' => 'edp-organisateur',
				'label' => __('Organisateur', EDV_TAG),
				'fields' => array($field_show)
			),
			array('name' => 'edp-phone',
				'label' => __('Téléphone', EDV_TAG),
				'type' => 'text'
			),
			array('name' => 'edp-email',
				'label' => __('Email', EDV_TAG),
				'type' => 'email',
				'fields' => array($field_show)
			),
			/*,
			array('name' => 'edp-gps',
				'label' => __('Coord. GPS', EDV_TAG),
				'type' => 'gps',
				'fields' => array($field_show)
			)*/
		);
		//codesecret
		$field = array('name' => 'edp-'.EDV_POST_SECRETCODE ,
			'label' => 'Code secret pour cet évènement',
			'type' => 'input' ,
			'readonly' => true ,
			'class' => 'readonly' 
		);
		if(self::$the_post_is_new)
			$field['value'] = edv::get_secret_code(6);
		$fields[] = $field;
  
		// sessionid
		// if(self::$the_post_is_new){
			// $fields[] = array('name' => 'edp-sessionid',
				// 'type' => 'hidden',
				// 'value' => edv::get_session_id()
			// );
		// }
		return $fields;
	}

	public static function get_metabox_general_fields(){
		$fields = array();

		$fields[] =
			array('name' => 'edp-message-contact',
				'label' => __('Les visiteurs peuvent envoyer un e-mail.', EDV_TAG),
				'type' => 'bool',
				'default' => 'checked'
			)
		;
		return $fields;
	}

	/**
	 * Ces champs ne sont PAS enregistrés car get_metabox_all_fields ne les retourne pas dans save_metaboxes
	 */
	public static function get_metabox_admin_fields(){
		global $post;
		$fields = array();
		if( ! self::$the_post_is_new ){
			$user_info = get_userdata($post->post_author);
			if( is_object($user_info) )
				$user_email = $user_info->user_email;
			else
				$user_email = false;
		}
 		if(self::$the_post_is_new
		|| $user_email != get_post_meta($post->ID, 'edp-email', true) ) {
			$fields[] = array(
				'name' => 'edp-create-user',
				'label' => __('Créer l\'utilisateur d\'après l\'e-mail', EDV_TAG),
				'input' => 'checkbox',
				'default' => 'checked',
				'container_class' => 'side-box'
			);
			/*$fields[] = array(
				'name' => 'edp-create-user-slug',
				'label' => __('Identifiant du nouvel utilisateur', EDV_TAG),
				'input' => 'text',
				'container_class' => 'side-box'
			);*/
		}
		// multi-sites
		/* if( ! self::$the_post_is_new && ( WP_DEBUG || is_multisite() )) {//
			$blogs = edv_Admin_Multisite::get_other_blogs_of_user($post->post_author);
			if(count($blogs) > 1){
				$field = array(
					'name' => 'edp-multisite-synchronise',
					// 'label' => __('Synchroniser cette page vers', EDV_TAG),
					// 'input' => 'checkbox',
					'label' => __('Vos autres sites', EDV_TAG),
					'input' => 'label',
					'fields' => array()
				);
				foreach($blogs as $blog){
					$field['fields'][] = 
						array('name' => sprintf('edp-multisite[%s]', $blog->userblog_id),
							//'label' => preg_replace('/edv\sd[eu]s?\s/', '', $blog->blogname),
							'label' => sprintf('<a href="%s/wp-admin">%s</a>', $blog->siteurl, preg_replace('/edv\sd[eu]s?\s/', '', $blog->blogname)),
							'input' => 'link',
							//'input' => 'label',
							'container_class' => 'description'
						)
					;	
				}
				$fields[] = $field;
			}
		} */
		
		return $fields;
	}

	/**
	 * Remplace la metabox Auteur par un liste déroulante dans une autre metabox
	 */
	private static function post_author_metabox_field( $post ) {
		global $user_ID;
		?><label for="post_author_override"><?php _e( 'Utilisateur' ); ?></label><?php
		wp_dropdown_users(
			array(
				// 'capability'       => 'authors',
				'name'             => 'post_author_override',
				'selected'         => empty( $post->ID ) ? $user_ID : $post->post_author,
				'include_selected' => true,
				'show'             => 'display_name_with_login',
			)
		);
	}
	
	public static function on_wp_terms_checklist_args($args, int $post_id){
		if($args['taxonomy'] === edv_Post::taxonomy_diffusion){
			$meta_name = 'default_checked';
			$args['selected_cats'] = [];
			foreach($args['popular_cats'] as $term_id)
				if( get_term_meta($term_id, $meta_name, true) )
					$args['selected_cats'][] = $term_id;
		}
		return $args;
	}

	/**
	 * Dans la visualisation des différences entre révisions, ajoute les meta_value
	 */
	public static function on_wp_get_revision_ui_diff_cb($return, $compare_from, $compare_to ){
		$metas_from = is_object($compare_from) ? get_post_meta($compare_from->ID, '', true) : [];
		$metas_to = get_post_meta($compare_to->ID, '', true);
		$meta_names = array_keys(array_merge($metas_from, $metas_to));
		
		$row_index = 0;
		foreach($meta_names as $meta_name){
			$from_exists = isset($metas_from[$meta_name]) && count($metas_from[$meta_name]) ;
			$from_value = $from_exists ? implode(', ', $metas_from[$meta_name]) : null;
			$to_exists = isset($metas_to[$meta_name]) && count($metas_to[$meta_name]);
			$post_value = $to_exists ? implode(', ', $metas_to[$meta_name]) : null;
			$return[] = array (
				'id' => $meta_name,
				'name' => $meta_name,
				'diff' => sprintf( "<table class='diff is-split-view'>
<tbody>
<tr><td class='%s'><span aria-hidden='true' class='dashicons dashicons-%s'></span><span class='screen-reader-text'>Avant </span><del>%s</del>
</td><td class='%s'><span aria-hidden='true' class='dashicons dashicons-%s'></span><span class='screen-reader-text'>Après </span><ins>%s</ins>
</td></tr>
</tbody>
</table>"
				, $from_exists ? 'diff-deletedline' : 'hide-children'
				, $from_exists && $post_value !== null ? 'minus' : 'arrow-left'
				, $from_value === null ? '' : htmlentities(var_export($from_value, true))
				, $to_exists ? 'diff-addedline' : ''
				, $to_exists && $from_exists ? 'plus' : 'yes'
				, $post_value === null ? '(idem)' : htmlentities(var_export($post_value, true))
			));
			$row_index++;
		}
		return $return;
	}
	
}
?>