<?php
/**
 * Register provider post types and taxonomies.
 * ED200325
 */
class edv_Post_Types {
	private static $registred_post_types = [];
	
	public static function init() {
		self::init_includes();
	}

	public static function init_includes() {
		if(!class_exists('edv_Post_Abstract'))
			require_once( EDV_PLUGIN_DIR . '/public/class.edv-post-abstract.php' );
		if(!class_exists('edv_Post'))
			require_once( EDV_PLUGIN_DIR . '/public/class.edv-post.php' );
		require_once( EDV_PLUGIN_DIR . '/public/class.edv-post-post_type.php' );
		if(!class_exists('edv_Newsletter'))
			require_once( EDV_PLUGIN_DIR . '/public/class.edv-newsletter.php' );
		require_once( EDV_PLUGIN_DIR . '/public/class.edv-newsletter-post_type.php' );
		if(edv::maillog_enable()){
			if(!class_exists('edv_Maillog'))
				require_once( EDV_PLUGIN_DIR . '/public/class.edv-maillog.php' );
			require_once( EDV_PLUGIN_DIR . '/public/class.edv-maillog-post_type.php' );
		}
		if(!class_exists('edv_Covoiturage'))
			require_once( EDV_PLUGIN_DIR . '/public/class.edv-covoiturage.php' );
		require_once( EDV_PLUGIN_DIR . '/public/class.edv-covoiturage-post_type.php' );
		
		if(!class_exists('edv_Forum'))
			require_once( EDV_PLUGIN_DIR . '/public/class.edv-forum.php' );
		require_once( EDV_PLUGIN_DIR . '/public/class.edv-forum-post_type.php' );
	}

	/**
	 * Register post types and taxonomies.
	 */
	public static function register_post_types() {

		do_action( 'edv_register_post_types' );

		edv_Post_Post_type::register_post_type();
		edv_Post_Post_type::register_taxonomy_edv_category();
		edv_Post_Post_type::register_taxonomy_city();
		edv_Post_Post_type::register_taxonomy_diffusion();
		
		edv_Newsletter_Post_type::register_post_type();
		edv_Newsletter_Post_type::register_taxonomy_period();
		
		if(edv::maillog_enable()){
			edv_Maillog_Post_type::register_post_type();
		}

		edv_Covoiturage_Post_type::register_post_type();
		edv_Covoiturage_Post_type::register_taxonomy_city();
		edv_Covoiturage_Post_type::register_taxonomy_diffusion();
		
		edv_Forum_Post_type::register_post_type();
		
	    // clear the permalinks after the post type has been registered
	    flush_rewrite_rules();

		do_action( 'edv_after_register_post_types' ); 
	}

	/**
	 * Unregister post types and taxonomies.
	 */
	public static function unregister_post_types() {

		do_action( 'edv_unregister_post_types' );
		
		
		foreach( edv_Post_Post_type::get_taxonomies() as $tax_name => $taxonomy){
			if ( post_type_exists( $tax_name ) ) 
				unregister_post_type($tax_name);
		}

		unregister_post_type(edv_Post::post_type);
		unregister_post_type(edv_Newsletter::post_type);
		
		if(edv::maillog_enable()){
			unregister_post_type(edv_Maillog::post_type);
		}
		
		unregister_post_type(edv_Covoiturage::post_type);
		
		unregister_post_type(edv_Forum::post_type);
		
		// clear the permalinks to remove our post type's rules from the database
    	flush_rewrite_rules();

		do_action( 'edv_after_unregister_post_types' );
	}
	
	/**
	 * Fires on plugin activation
	 */
	public static function plugin_activation(){
		edv_Newsletter_Post_type::plugin_activation();
	}
}
