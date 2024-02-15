<?php

/**
 * edv -> Newsletter
 * Custom post type for WordPress.
 * Custom user role for WordPress.
 * 
 * Définition du Post Type 'edvnl'
 * Définition du rôle utilisateur 'edvnl'
 *
 * Voir aussi edv_Admin_Newsletter
 */
class edv_Newsletter_Post_type {
	
	/**
	 * Newsletter post type.
	 */
	public static function register_post_type() {

		$labels = array(
			'name'                  => _x( 'Lettres-info', 'Post Type General Name', EDV_TAG ),
			'singular_name'         => _x( 'Lettre-info', 'Post Type Singular Name', EDV_TAG ),
			'menu_name'             => __( 'Lettres-info', EDV_TAG ),
			'name_admin_bar'        => __( 'Lettre-info', EDV_TAG ),
			'archives'              => __( 'Lettres-info', EDV_TAG ),
			'attributes'            => __( 'Attributs', EDV_TAG ),
			'parent_item_colon'     => __( 'Lettre-info parent:', EDV_TAG ),
			'all_items'             => __( 'Toutes les lettres-info', EDV_TAG ),
			'add_new_item'          => __( 'Ajouter une lettre-info', EDV_TAG ),
			'add_new'               => __( 'Ajouter', EDV_TAG ),
			'new_item'              => __( 'Nouvelle lettre-info', EDV_TAG ),
			'edit_item'             => __( 'Modifier', EDV_TAG ),
			'update_item'           => __( 'Mettre à jour', EDV_TAG ),
			'view_item'             => __( 'Afficher', EDV_TAG ),
			'view_items'            => __( 'Voir les lettres-info', EDV_TAG ),
			'search_items'          => __( 'Rechercher des lettres-info', EDV_TAG ),
			'items_list'            => __( 'Liste de lettres-info', EDV_TAG ),
			'items_list_navigation' => __( 'Navigation dans la liste de lettres-info', EDV_TAG ),
			'filter_items_list'     => __( 'Filtrer la liste des lettres-info', EDV_TAG ),
		);
		$capabilities = self::post_type_capabilities();
		$args = array(
			'label'                 => __( 'Lettre-info', EDV_TAG ),
			'description'           => __( 'Lettre-info de l\'agenda partagé', EDV_TAG ),
			'labels'                => $labels,
			'supports'              => array( 'title', 'editor', 'thumbnail', 'revisions' ),//, 'author', 'editor' see edv_Admin_Newsletter::init_PostType_Supports
			'taxonomies'            => array(  ),
			'hierarchical'          => false,
			'public'                => true,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'menu_icon'				=> 'dashicons-email-alt',
			'menu_position'         => 29,
			'show_in_admin_bar'     => true,
			'show_in_nav_menus'     => true,
			'can_export'            => true,
			'has_archive'           => true,
			'delete_with_user'		=> false,
			'exclude_from_search'   => true,
			'publicly_queryable'    => true,
			//'capabilities'			=> $capabilities,
			'capability_type'       => 'page'
		);
		register_post_type( edv_Newsletter::post_type, $args );
		
	}
	
	private static function post_type_capabilities(){
		return array(
			'create_edvnls' => 'create_posts',
			'edit_edvnls' => 'edit_posts',
			'edit_others_edvnls' => 'edit_others_posts',
			'publish_edvnls' => 'publish_posts',
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
				// 'edit_edvnls' => true,
				// 'wpcf7_read_contact_forms' => false,

				// 'publish_edvnls' => true,
				// 'delete_posts' => true,
				// 'delete_published_posts' => true,
				// 'edit_published_posts' => true,
				// 'publish_posts' => true,
				// 'upload_files ' => true,
				// 'create_posts' => false,
				// 'create_edvnls' => false,
			// );
			// add_role( edv_Newsletter::post_type, __('Lettre-info', EDV_TAG ),  $capabilities);
	}
	
	/**
	 * Register Custom Taxonomy
	 */
	public static function register_taxonomy_period() {

		$labels = array(
			'name'                       => _x( 'Période', 'Taxonomy General Name', EDV_TAG ),
			'singular_name'              => _x( 'Période', 'Taxonomy Singular Name', EDV_TAG ),
			'menu_name'                  => __( 'Périodes', EDV_TAG ),
			'all_items'                  => __( 'Toutes les périodes', EDV_TAG ),
			'parent_item'                => __( 'Secteur parent', EDV_TAG ),
			'parent_item_colon'          => __( 'Secteur parent:', EDV_TAG ),
			'new_item_name'              => __( 'Nouvelle période', EDV_TAG ),
			'add_new_item'               => __( 'Ajouter une période', EDV_TAG ),
			'edit_item'                  => __( 'Modifier', EDV_TAG ),
			'update_item'                => __( 'Mettre à jour', EDV_TAG ),
			'view_item'                  => __( 'Afficher', EDV_TAG ),
			'separate_items_with_commas' => __( 'Séparer les éléments par une virgule', EDV_TAG ),
			'add_or_remove_items'        => __( 'Ajouter ou supprimer des éléments', EDV_TAG ),
			'choose_from_most_used'      => __( 'Choisir la plus utilisée', EDV_TAG ),
			'popular_items'              => __( 'Périodes les plus utilisées', EDV_TAG ),
			'search_items'               => __( 'Rechercher', EDV_TAG ),
			'not_found'                  => __( 'Introuvable', EDV_TAG ),
			'no_terms'                   => __( 'Aucune période', EDV_TAG ),
			'items_list'                 => __( 'Liste des périodes', EDV_TAG ),
			'items_list_navigation'      => __( 'Navigation parmi les périodes', EDV_TAG ),
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
		register_taxonomy( edv_Newsletter::taxonomy_period, array( edv_Newsletter::post_type ), $args );

	}
	
	public static function plugin_activation(){
		self::init_taxonomy();
		
		edv_Newsletter::init_cron();
	}
	
	/** initialise les périodes **/
	public static function init_taxonomy(){
		
		$terms = array(
			'none'=>'Aucun abonnement',
			'm'=>'Tous les mois',
			'2w'=>'Tous les quinze jours',
			'w'=>'Toutes les semaines',
			'd'=>'Tous les jours',
		);
		
		$existings = [];
		foreach(get_terms(edv_Newsletter::taxonomy_period) as $existing){
			if( array_key_exists( $existing->slug, $terms) )
				$existings[$existing->slug] = $existing->name;
		}
		// debug_log(__CLASS__ . ' plugin_activation', array_diff($terms, $existings));
		foreach( array_diff($terms, $existings) as $new_slug => $new_name)
			wp_insert_term($new_name, edv_Newsletter::taxonomy_period
				, array(
					'slug' => (string)$new_slug
				)
			);
	}
}
