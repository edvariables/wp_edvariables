<?php

/**
 * edv -> Post
 * Custom post type for WordPress.
 * Custom user role for WordPress.
 * 
 * Définition du Post Type 'edpost'
 * Définition de la taxonomie 'edv_category'
 * Définition du rôle utilisateur 'edpost'
 * A l'affichage d'un message, le Content est remplacé par celui de l'message Modèle
 * En Admin, le bloc d'édition du Content est masqué d'après la définition du Post type : le paramètre 'supports' qui ne contient pas 'editor', see edv_Admin_Post::init_PostType_Supports
 *
 * Voir aussi edv_Admin_Post
 */
class edv_Post_Post_type {
	
	/**
	 * Post post type.
	 */
	public static function register_post_type() {

		$labels = array(
			'name'                  => _x( 'Messages', 'Post Type General Name', EDV_TAG ),
			'singular_name'         => _x( 'Message', 'Post Type Singular Name', EDV_TAG ),
			'menu_name'             => __( 'Messages', EDV_TAG ),
			'name_admin_bar'        => __( 'Message', EDV_TAG ),
			'archives'              => __( 'Messages', EDV_TAG ),
			'attributes'            => __( 'Attributs', EDV_TAG ),
			'parent_item_colon'     => __( 'Message parent:', EDV_TAG ),
			'all_items'             => __( 'Tous les messages', EDV_TAG ),
			'add_new_item'          => __( 'Ajouter un message', EDV_TAG ),
			'add_new'               => __( 'Ajouter', EDV_TAG ),
			'new_item'              => __( 'Nouveau message', EDV_TAG ),
			'edit_item'             => __( 'Modifier', EDV_TAG ),
			'update_item'           => __( 'Mettre à jour', EDV_TAG ),
			'view_item'             => __( 'Afficher', EDV_TAG ),
			'view_items'            => __( 'Voir les messages', EDV_TAG ),
			'search_items'          => __( 'Rechercher des messages', EDV_TAG ),
			'items_list'            => __( 'Liste des messages', EDV_TAG ),
			'items_list_navigation' => __( 'Navigation dans la liste des messages', EDV_TAG ),
			'filter_items_list'     => __( 'Filtrer la liste des messages', EDV_TAG ),
		);
		$capabilities = self::post_type_capabilities();
		$args = array(
			'label'                 => __( 'Message', EDV_TAG ),
			'description'           => __( 'Message', EDV_TAG ),
			'labels'                => $labels,
			'supports'              => array( 'title', 'thumbnail', 'revisions' ),//, 'author', 'editor' see edv_Admin_Post::init_PostType_Supports
			'taxonomies'            => array( edv_Post::taxonomy_edv_category
											, edv_Post::taxonomy_city
											, edv_Post::taxonomy_diffusion ),
			'hierarchical'          => false,
			'public'                => true,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'menu_icon'				=> 'dashicons-calendar-alt',
			'menu_position'         => 25,
			'show_in_admin_bar'     => true,
			'show_in_nav_menus'     => true,
			'can_export'            => true,
			'has_archive'           => true,
			'delete_with_user'		=> false,
			'exclude_from_search'   => false,
			'publicly_queryable'    => true,
			//'capabilities'			=> $capabilities,
			'capability_type'       => 'post'
		);
		register_post_type( edv_Post::post_type, $args );
		
		if(WP_POST_REVISIONS >= 0)
			add_filter( 'wp_revisions_to_keep', array(__CLASS__, 'wp_revisions_to_keep'), 10, 2);
		// add_filter( '_wp_post_revision_fields', array(__CLASS__, '_wp_post_revision_fields'), 10, 2);
	}
	public static function wp_revisions_to_keep( int $num, WP_Post $post ) {
		if($post->post_type === edv_Post::post_type)
			return -1;
		return $num;
	}	

	/**
	 * Register Custom Taxonomy
	 */
	public static function register_taxonomy_edv_category() {

		$labels = array(
			'name'                       => _x( 'Catégorie de message', 'Taxonomy General Name', EDV_TAG ),
			'singular_name'              => _x( 'Catégorie de message', 'Taxonomy Singular Name', EDV_TAG ),
			'menu_name'                  => __( 'Catégories de message', EDV_TAG ),
			'all_items'                  => __( 'Toutes les catégories de message', EDV_TAG ),
			'parent_item'                => __( 'Catégorie parente', EDV_TAG ),
			'parent_item_colon'          => __( 'Catégorie parente:', EDV_TAG ),
			'new_item_name'              => __( 'Nouvelle catégorie de message', EDV_TAG ),
			'add_new_item'               => __( 'Ajouter une nouvelle catégorie', EDV_TAG ),
			'edit_item'                  => __( 'Modifier', EDV_TAG ),
			'update_item'                => __( 'Mettre à jour', EDV_TAG ),
			'view_item'                  => __( 'Afficher', EDV_TAG ),
			'separate_items_with_commas' => __( 'Séparer les éléments par une virgule', EDV_TAG ),
			'add_or_remove_items'        => __( 'Ajouter ou supprimer des éléments', EDV_TAG ),
			'choose_from_most_used'      => __( 'Choisir le plus utilisé', EDV_TAG ),
			'popular_items'              => __( 'Catégories populaires', EDV_TAG ),
			'search_items'               => __( 'Rechercher', EDV_TAG ),
			'not_found'                  => __( 'Introuvable', EDV_TAG ),
			'no_terms'                   => __( 'Aucune catégorie', EDV_TAG ),
			'items_list'                 => __( 'Liste des catégories', EDV_TAG ),
			'items_list_navigation'      => __( 'Navigation parmi les catégories', EDV_TAG ),
		);
		$args = array(
			'labels'                     => $labels,
			'hierarchical'               => true,
			'public'                     => true,
			'show_ui'                    => true,
			'show_admin_column'          => true,
			'show_in_nav_menus'          => true,
			'show_tagcloud'              => true,
		);
		register_taxonomy( edv_Post::taxonomy_edv_category, array( edv_Post::post_type ), $args );

	}
	
	/**
	 * Register Custom Taxonomy
	 */
	public static function register_taxonomy_city() {

		$labels = array(
			'name'                       => _x( 'Commune', 'Taxonomy General Name', EDV_TAG ),
			'singular_name'              => _x( 'Commune', 'Taxonomy Singular Name', EDV_TAG ),
			'menu_name'                  => __( 'Communes', EDV_TAG ),
			'all_items'                  => __( 'Toutes les communes', EDV_TAG ),
			'parent_item'                => __( 'Secteur parent', EDV_TAG ),
			'parent_item_colon'          => __( 'Secteur parent:', EDV_TAG ),
			'new_item_name'              => __( 'Nouvelle commune', EDV_TAG ),
			'add_new_item'               => __( 'Ajouter une commune', EDV_TAG ),
			'edit_item'                  => __( 'Modifier', EDV_TAG ),
			'update_item'                => __( 'Mettre à jour', EDV_TAG ),
			'view_item'                  => __( 'Afficher', EDV_TAG ),
			'separate_items_with_commas' => __( 'Séparer les éléments par une virgule', EDV_TAG ),
			'add_or_remove_items'        => __( 'Ajouter ou supprimer des éléments', EDV_TAG ),
			'choose_from_most_used'      => __( 'Choisir la plus utilisée', EDV_TAG ),
			'popular_items'              => __( 'Communes les plus utilisées', EDV_TAG ),
			'search_items'               => __( 'Rechercher', EDV_TAG ),
			'not_found'                  => __( 'Introuvable', EDV_TAG ),
			'no_terms'                   => __( 'Aucune commune', EDV_TAG ),
			'items_list'                 => __( 'Liste des communes', EDV_TAG ),
			'items_list_navigation'      => __( 'Navigation parmi les communes', EDV_TAG ),
		);
		$args = array(
			'labels'                     => $labels,
			'hierarchical'               => true,
			'public'                     => true,
			'show_ui'                    => true,
			'show_admin_column'          => true,
			'show_in_nav_menus'          => true,
			'show_tagcloud'              => true,
		);
		register_taxonomy( edv_Post::taxonomy_city, array( edv_Post::post_type ), $args );

	}
	
	/**
	 * Register Custom Taxonomy
	 */
	public static function register_taxonomy_diffusion() {

		$labels = array(
			'name'                       => _x( 'Diffusion', 'Taxonomy General Name', EDV_TAG ),
			'singular_name'              => _x( 'Diffusion', 'Taxonomy Singular Name', EDV_TAG ),
			'menu_name'                  => __( 'Diffusions', EDV_TAG ),
			'all_items'                  => __( 'Toutes les diffusions', EDV_TAG ),
			'parent_item'                => __( 'Diffusion parente', EDV_TAG ),
			'parent_item_colon'          => __( 'Diffusion parente:', EDV_TAG ),
			'new_item_name'              => __( 'Nouvelle diffusion', EDV_TAG ),
			'add_new_item'               => __( 'Ajouter une diffusion', EDV_TAG ),
			'edit_item'                  => __( 'Modifier', EDV_TAG ),
			'update_item'                => __( 'Mettre à jour', EDV_TAG ),
			'view_item'                  => __( 'Afficher', EDV_TAG ),
			'separate_items_with_commas' => __( 'Séparer les éléments par une virgule', EDV_TAG ),
			'add_or_remove_items'        => __( 'Ajouter ou supprimer des éléments', EDV_TAG ),
			'choose_from_most_used'      => __( 'Choisir la plus utilisée', EDV_TAG ),
			'popular_items'              => __( 'Diffusions les plus communes', EDV_TAG ),
			'search_items'               => __( 'Rechercher', EDV_TAG ),
			'not_found'                  => __( 'Introuvable', EDV_TAG ),
			'no_terms'                   => __( 'Aucune diffusion', EDV_TAG ),
			'items_list'                 => __( 'Liste des diffusions', EDV_TAG ),
			'items_list_navigation'      => __( 'Navigation parmi les diffusions', EDV_TAG ),
		);
		$args = array(
			'labels'                     => $labels,
			'hierarchical'               => true,
			'public'                     => true,
			'show_ui'                    => true,
			'show_admin_column'          => true,
			'show_in_nav_menus'          => true,
			'show_tagcloud'              => true,
		);
		register_taxonomy( edv_Post::taxonomy_diffusion, array( edv_Post::post_type ), $args );

	}
	
	private static function post_type_capabilities(){
		return array(
			'create_edposts' => 'create_posts',
			'edit_edposts' => 'edit_posts',
			'edit_others_edposts' => 'edit_others_posts',
			'publish_edposts' => 'publish_posts',
		);
	}

	/**
	 *
	 */
	public static function register_user_role(){
		return;
		
		// $capabilities = array(
			// 'read' => true,
			// 'edit_posts' => true,
			// 'edit_edposts' => true,
			// 'wpcf7_read_contact_forms' => false,

			// 'publish_edposts' => true,
			// 'delete_posts' => true,
			// 'delete_published_posts' => true,
			// 'edit_published_posts' => true,
			// 'publish_posts' => true,
			// 'upload_files ' => true,
			// 'create_posts' => false,
			// 'create_edposts' => false,
		// );
		// add_role( edv_Post::post_type, __('Message', EDV_TAG ),  $capabilities);
	}

	/**
	 * Retourne tous les termes
	 */
	public static function get_all_types_edpost($array_keys_field = 'term_id'){
		return self::get_all_terms(edv_Post::taxonomy_edv_category, $array_keys_field);
	}
	public static function get_all_cities($array_keys_field = 'term_id'){
		return self::get_all_terms(edv_Post::taxonomy_city, $array_keys_field);
	}
	public static function get_all_diffusions($array_keys_field = 'term_id'){
		return self::get_all_terms(edv_Post::taxonomy_diffusion, $array_keys_field );
	}

	/**
	 * Retourne tous les termes
	 */
	public static function get_all_terms($taxonomy, $array_keys_field = 'term_id'){
		$terms = get_terms( array('hide_empty' => false, 'taxonomy' => $taxonomy) );
		if($array_keys_field){
			$_terms = [];
			foreach($terms as $term)
				$_terms[$term->$array_keys_field . ''] = $term;
			$terms = $_terms;
		}
		
		$meta_names = [];
		switch($taxonomy){
			case edv_Post::taxonomy_diffusion :
				$meta_names[] = 'default_checked';
				$meta_names[] = 'download_link';
				break;
		}
		foreach($meta_names as $meta_name){
			foreach($terms as $term)
				$term->$meta_name = get_term_meta($term->term_id, $meta_name, true);
		}
		return $terms;
	}
	
	/**
	 * Taxonomies
	 */
	public static function get_taxonomies ( $except = false ){
		$taxonomies = [];
		
		$tax_name = edv_Post::taxonomy_edv_category;
		if( ! $except || strpos($except, $tax_name ) === false)
			$taxonomies[$tax_name] = array(
				'name' => $tax_name,
				'input' => 'edp-categories',
				'filter' => 'categories',
				'label' => 'Catégorie',
				'plural' => 'Catégories',
				'all_label' => '(toutes)',
				'none_label' => '(sans catégorie)'
			);
		
		$tax_name = edv_Post::taxonomy_city;
		if( ! $except || strpos($except, $tax_name ) === false)
			$taxonomies[$tax_name] = array(
				'name' => $tax_name,
				'input' => 'edp-cities',
				'filter' => 'cities',
				'label' => 'Commune',
				'plural' => 'Commune',
				'all_label' => '(toutes)',
				'none_label' => '(sans commune)'
			);
		
		$tax_name = edv_Post::taxonomy_diffusion;
		if( ! $except || strpos($except, $tax_name ) === false)
			$taxonomies[$tax_name] = array(
				'name' => $tax_name,
				'input' => 'edp-diffusions',
				'filter' => 'diffusions',
				'label' => 'Diffusion',
				'plural' => 'Diffusions',
				'all_label' => '(toutes)',
				'none_label' => '(sans diffusion)'
			);
		
		return $taxonomies;
	}
	
	/**
	 *
	 */
	public static function is_diffusion_managed(){
		return edv::get_option('newsletter_diffusion_term_id') != -1;
	}
	
	/**
	 * Retourne les termes d'une taxonomie avec leurs alternatives syntaxiques pour un like.
	 * Utilisée pour chercher les communes dans la meta_value 'edp-localisation'.
	 */
	public static function get_terms_like($tax_name, $term_ids){
		$like = [];
		foreach( get_terms(array(
				'taxonomy' => $tax_name,
				'hide_empty' => false,
		)) as $term){
			if( in_array($term->term_id, $term_ids)){
				$like[] = $term->name;
				foreach(['-'=>' ', 'saint'=>'st', 'saint-'=>'st-', 'sainte-'=>'ste-'] as $search=>$replace){
					$alt_term = str_ireplace($search, $replace, $term->name);
					if($alt_term !== $term->name)
						$like[] = $alt_term;
				}
			}
		}
		return $like;
	}
}
