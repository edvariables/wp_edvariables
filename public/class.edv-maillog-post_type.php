<?php

/**
 * edv -> Maillog
 * Custom post type for WordPress.
 * Custom user role for WordPress.
 * 
 * Définition du Post Type 'edvmaillog'
 * Définition du rôle utilisateur 'edvmaillog'
 *
 * Voir aussi edv_Admin_Maillog
 */
class edv_Maillog_Post_type {
	
	/**
	 * Maillog post type.
	 */
	public static function register_post_type() {

		$labels = array(
			'name'                  => _x( 'Traces mail', 'Post Type General Name', EDV_TAG ),
			'singular_name'         => _x( 'Trace mail', 'Post Type Singular Name', EDV_TAG ),
			'menu_name'             => __( 'Traces mail', EDV_TAG ),
			'name_admin_bar'        => __( 'Trace mail', EDV_TAG ),
			'archives'              => __( 'Traces mail', EDV_TAG ),
			'attributes'            => __( 'Attributs', EDV_TAG ),
			'parent_item_colon'     => __( 'Trace mail parent:', EDV_TAG ),
			'all_items'             => __( 'Toutes les traces mail', EDV_TAG ),
			'add_new_item'          => __( 'Ajouter une trace mail', EDV_TAG ),
			'add_new'               => __( 'Ajouter', EDV_TAG ),
			'new_item'              => __( 'Nouvelle trace mail', EDV_TAG ),
			'edit_item'             => __( 'Modifier', EDV_TAG ),
			'update_item'           => __( 'Mettre à jour', EDV_TAG ),
			'view_item'             => __( 'Afficher', EDV_TAG ),
			'view_items'            => __( 'Voir les traces mail', EDV_TAG ),
			'search_items'          => __( 'Rechercher des traces mail', EDV_TAG ),
			'items_list'            => __( 'Liste de traces mail', EDV_TAG ),
			'items_list_navigation' => __( 'Navigation dans la liste de traces mail', EDV_TAG ),
			'filter_items_list'     => __( 'Filtrer la liste des traces mail', EDV_TAG ),
		);
		$capabilities = self::post_type_capabilities();
		$args = array(
			'label'                 => __( 'Trace mail', EDV_TAG ),
			'description'           => __( 'Trace mail de l\'agenda partagé', EDV_TAG ),
			'labels'                => $labels,
			'supports'              => array( 'title', 'editor', 'thumbnail', 'revisions' ),//, 'author', 'editor' see edv_Admin_Maillog::init_PostType_Supports
			'taxonomies'            => array(  ),
			'hierarchical'          => false,
			'public'                => true,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'menu_icon'				=> 'dashicons-database',
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
		register_post_type( edv_Maillog::post_type, $args );
		
	}
	
	private static function post_type_capabilities(){
		return array(
			'create_edvmaillogs' => 'create_posts',
			'edit_edvmaillogs' => 'edit_posts',
			'edit_others_edvmaillogs' => 'edit_others_posts',
			'publish_edvmaillogs' => 'publish_posts',
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
			// 'edit_edvmaillogs' => true,
			// 'wpcf7_read_contact_forms' => false,

			// 'publish_edvmaillogs' => true,
			// 'delete_posts' => true,
			// 'delete_published_posts' => true,
			// 'edit_published_posts' => true,
			// 'publish_posts' => true,
			// 'upload_files ' => true,
			// 'create_posts' => false,
			// 'create_edvmaillogs' => false,
		// );
		// add_role( edv_Maillog::post_type, __('Trace mail', EDV_TAG ),  $capabilities);
	}
}
