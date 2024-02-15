<?php

/**
 * edv -> Forum
 * Custom post type for WordPress.
 * Custom user role for WordPress.
 * 
 * Définition du Post Type 'edvforum'
 * Définition du rôle utilisateur 'edvforum'
 *
 * Voir aussi edv_Admin_Forum
 */
class edv_Forum_Post_type {
	
	/**
	 * Forum post type.
	 */
	public static function register_post_type() {

		$labels = array(
			'name'                  => _x( 'Forums', 'Post Type General Name', EDV_TAG ),
			'singular_name'         => _x( 'Forum', 'Post Type Singular Name', EDV_TAG ),
			'menu_name'             => __( 'Forums', EDV_TAG ),
			'name_admin_bar'        => __( 'Forum', EDV_TAG ),
			'archives'              => __( 'Forums', EDV_TAG ),
			'attributes'            => __( 'Attributs', EDV_TAG ),
			'parent_item_colon'     => __( 'Forum parent:', EDV_TAG ),
			'all_items'             => __( 'Tous les forums', EDV_TAG ),
			'add_new_item'          => __( 'Ajouter un forum', EDV_TAG ),
			'add_new'               => __( 'Ajouter', EDV_TAG ),
			'new_item'              => __( 'Nouveau forum', EDV_TAG ),
			'edit_item'             => __( 'Modifier', EDV_TAG ),
			'update_item'           => __( 'Mettre à jour', EDV_TAG ),
			'view_item'             => __( 'Afficher', EDV_TAG ),
			'view_items'            => __( 'Voir les forums', EDV_TAG ),
			'search_items'          => __( 'Rechercher des forums', EDV_TAG ),
			'items_list'            => __( 'Liste de forums', EDV_TAG ),
			'items_list_navigation' => __( 'Navigation dans la liste de forums', EDV_TAG ),
			'filter_items_list'     => __( 'Filtrer la liste des forums', EDV_TAG ),
		);
		$capabilities = self::post_type_capabilities();
		$args = array(
			'label'                 => __( 'Forum', EDV_TAG ),
			'description'           => __( 'Forum dans l\'agenda partagé', EDV_TAG ),
			'labels'                => $labels,
			'supports'              => array( 'title', 'editor', 'thumbnail', 'revisions' ),//, 'author', 'editor' see edv_Admin_Forum::init_PostType_Supports
			'taxonomies'            => array(  ),
			'hierarchical'          => false,
			'public'                => true,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'menu_icon'				=> 'dashicons-buddicons-forums',
			'menu_position'         => 26,
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
		register_post_type( edv_Forum::post_type, $args );
		
	}
	
	private static function post_type_capabilities(){
		return array(
			'create_edvforums' => 'create_posts',
			'edit_edvforums' => 'edit_posts',
			'edit_others_edvforums' => 'edit_others_posts',
			'publish_edvforums' => 'publish_posts',
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
				// 'edit_edvforums' => true,
				// 'wpcf7_read_contact_forms' => false,

				// 'publish_edvforums' => true,
				// 'delete_posts' => true,
				// 'delete_published_posts' => true,
				// 'edit_published_posts' => true,
				// 'publish_posts' => true,
				// 'upload_files ' => true,
				// 'create_posts' => false,
				// 'create_edvforums' => false,
			// );
			// add_role( edv_Forum::post_type, __('Forum', EDV_TAG ),  $capabilities);
	}
}
