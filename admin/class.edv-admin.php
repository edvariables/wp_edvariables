<?php

class edv_Admin {

	public static function init() {
		self::init_includes();
		self::init_hooks();

		do_action( 'edv-admin_init' );
	}

	public static function init_includes() {	

		require_once( EDV_PLUGIN_DIR . '/admin/class.edv-admin-menu.php' );
		edv_Admin_Menu::init();

		require_once( EDV_PLUGIN_DIR . '/admin/class.edv-admin-user.php' );
		add_action( 'edv-admin_init', array( 'edv_Admin_User', 'init' ) );

		if( is_multisite()){
			require_once( EDV_PLUGIN_DIR . '/admin/class.edv-admin-multisite.php' );
			add_action( 'edv-admin_init', array( 'edv_Admin_Multisite', 'init' ) );
		}
		require_once( EDV_PLUGIN_DIR . '/admin/class.edv-admin-post.php' );
		add_action( 'edv-admin_init', array( 'edv_Admin_Post', 'init' ) );

		require_once( EDV_PLUGIN_DIR . '/admin/class.edv-admin-newsletter.php' );
		add_action( 'edv-admin_init', array( 'edv_Admin_Newsletter', 'init' ) );

		if(edv::maillog_enable()){
			require_once( EDV_PLUGIN_DIR . '/admin/class.edv-admin-maillog.php' );
			add_action( 'edv-admin_init', array( 'edv_Admin_Maillog', 'init' ) );
		}
		require_once( EDV_PLUGIN_DIR . '/admin/class.edv-admin-covoiturage.php' );
		add_action( 'edv-admin_init', array( 'edv_Admin_Covoiturage', 'init' ) );
		
		require_once( EDV_PLUGIN_DIR . '/admin/class.edv-admin-edit-post-type.php' );

		require_once( EDV_PLUGIN_DIR . '/admin/class.edv-admin-edit-post.php' );
		add_action( 'edv-admin_init', array( 'edv_Admin_Edit_Post', 'init' ) );

		require_once( EDV_PLUGIN_DIR . '/admin/class.edv-admin-edit-covoiturage.php' );
		add_action( 'edv-admin_init', array( 'edv_Admin_Edit_Covoiturage', 'init' ) );

		require_once( EDV_PLUGIN_DIR . '/admin/class.edv-admin-edit-diffusion.php' );
		add_action( 'edv-admin_init', array( 'edv_Admin_Edit_Diffusion', 'init' ) );

		require_once( EDV_PLUGIN_DIR . '/admin/class.edv-admin-edit-newsletter.php' );
		add_action( 'edv-admin_init', array( 'edv_Admin_Edit_Newsletter', 'init' ) );

		require_once( EDV_PLUGIN_DIR . '/admin/class.edv-admin-forum.php' );
		add_action( 'edv-admin_init', array( 'edv_Admin_Forum', 'init' ) );

		require_once( EDV_PLUGIN_DIR . '/admin/class.edv-admin-edit-forum.php' );
		add_action( 'edv-admin_init', array( 'edv_Admin_Edit_Forum', 'init' ) );

		require_once( EDV_PLUGIN_DIR . '/admin/class.edv-admin-edit-forum-comment.php' );
		add_action( 'edv-admin_init', array( 'edv_Admin_Edit_Forum_Comment', 'init' ) );

		if(edv::maillog_enable()){
			require_once( EDV_PLUGIN_DIR . '/admin/class.edv-admin-edit-maillog.php' );
			add_action( 'edv-admin_init', array( 'edv_Admin_Edit_Maillog', 'init' ) );
		}
	}

	public static function init_hooks() {

	    add_action( 'admin_enqueue_scripts', array(__CLASS__, 'register_plugin_styles') );
		add_action( 'admin_enqueue_scripts', array(__CLASS__, 'register_plugin_js') ); 

        add_action( 'admin_notices', array(__CLASS__,'show_admin_notices') );
		
		add_action( 'pre_update_option_' . EDV_TAG, array(__CLASS__,'on_pre_update_option'), 10, 3 );
		add_action( 'update_option_' . EDV_TAG, array(__CLASS__,'on_updated_option'), 10, 3 );
		
		if(class_exists('WPCF7_ContactForm')){
			add_action( 'wpcf7_admin_notices', array( __CLASS__, 'wpcf7_admin_notices' ), 10, 3 ); //edit
		}
		
		add_action( 'wp_ajax_'.EDV_TAG.'_admin_action', array(__CLASS__, 'on_wp_ajax_admin_action_cb') );
	}

	/**
	 * Registers a stylesheet.
	 */
	public static function register_plugin_styles() {
	    wp_register_style( EDV_TAG, plugins_url( EDV_PLUGIN_NAME . '/admin/css/edv-admin.css' ), array(), EDV_VERSION , 'all'  );
	    wp_enqueue_style( EDV_TAG);
	    wp_register_style( EDV_TAG . '_ui', plugins_url( EDV_PLUGIN_NAME . '/includes/css/edv-ui.css' ), array(), EDV_VERSION , 'all' );
	    wp_enqueue_style( EDV_TAG . '_ui');
	}

	/**
	 * Registers js files.
	 */
	public static function register_plugin_js() {
		wp_enqueue_script(array( 'jquery', 'jquery-ui-tabs' ));
		
	    wp_register_script( EDV_TAG . '-tools', plugins_url( EDV_PLUGIN_NAME . '/includes/js/edv-tools.js' ), array(), EDV_VERSION , 'all' );
		wp_localize_script( EDV_TAG . '-tools', 'edv_ajax', array( 
			'ajax_url' => admin_url('admin-ajax.php')
			, 'check_nonce' => wp_create_nonce('edv-admin-nonce')
			, 'is_admin' => true )
		);
	    wp_enqueue_script( EDV_TAG . '-tools' );
	}

	/**
	 * admin_notices tag
	 */
	private static function admin_notices_tag(){
		return EDV_TAG . '_ADMIN_NOTICES_' . get_current_user_id();
	}
	/**
	 *
	 * $type : success, warning, error
	 */
	public static function add_admin_notice( $msg, $type = 'success', $is_html = false){
		if( ! is_admin())
			return;
		
		$notices = get_transient(self::admin_notices_tag());
		if( ! is_array($notices))
			$notices = array();
		if( is_array($msg))
			$msg = implode("\r\n", $msg);
		$notices[] = array(
			'message' => $is_html ? $msg : esc_html($msg),
			'type' => $type,
		);
		$result = set_transient(self::admin_notices_tag(), $notices);
		
		return $result;
		
	}
	public static function show_admin_notices(){
		$notices = get_transient(self::admin_notices_tag());
		if(is_array($notices)){
			foreach($notices as $notice){
				$class = 'notice notice-' . $notice['type'];
	    		$message = __( $notice['message'], EDV_TAG );
	    		if( is_wp_error($message)) {
					$message = $message->get_error_messages(); 
				}
				printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message ); 
			}
		}
		self::clear_admin_notices();
	}

	public static function clear_admin_notices(){
		delete_transient(self::admin_notices_tag());
	}
	
	
	/**
	 * Affiche une notification dans l'administration
	 * Gère avec ou sans block-editor.
	 */
	public static function add_admin_notice_now($message, $attrs){
		$current_screen = get_current_screen();
		$is_block_editor = method_exists( $current_screen, 'is_block_editor' ) && $current_screen->is_block_editor();
		if($is_block_editor){
?><script>( function( wp ) {
    wp.data.dispatch('core/notices').createNotice(
        '<?php echo $attrs['type']?>',
        "<?php echo esc_attr(strip_tags($message))?>",
		{
            isDismissible: true
            <?php if(isset($attrs['actions'])){
			?>, actions: [
                {
                    url: '<?php echo $attrs['actions']['url']?>',
                    label: '<?php echo (empty($attrs['actions']['label']) ? 'Afficher' : $attrs['actions']['label'])?>'
                }
            ]<?php }?>
        }
    );
} )( window.wp );
</script><?php
		}
		else
			wp_admin_notice($message, $attrs);
	}
	/**
	* Hook de mise à jour d'option
	*/
	public static function on_updated_option( $old_values, $values, $option ) {
		if( $option !== EDV_TAG )
			return;
		
		static $static_updating;
		if( ! empty($static_updating))
			return;		
		$static_updating = true;
		
		//Import d'un fichier de messages
		$option_key = 'edpost_import_ics';
		if( array_key_exists($option_key . '-confirm', $values) ){
			if( count($_FILES)
				&& array_key_exists( EDV_TAG, $_FILES)
				&& array_key_exists( 'name', $_FILES[EDV_TAG])
				&& array_key_exists( $option_key, $_FILES[EDV_TAG]['tmp_name'])
			){
				$fileName = $_FILES[EDV_TAG]['tmp_name'][$option_key];
				if($fileName){
					$original_file_name = $_FILES[EDV_TAG]['name'][$option_key];
					if(array_key_exists($option_key . '-post_status', $values)){
						$post_status = $values[$option_key . '-post_status'];
					}
					else
						$post_status = 'publish';
					if( ! array_key_exists($option_key . '-confirm', $_POST[EDV_TAG])){
						self::set_import_report(sprintf('<div class="error notice"><p><strong>%s</strong></p></div>', 
								__('Vous n\'avez pas confirmé l\'importation.', EDV_TAG)));
						var_dump($_POST);
					}
					else{
						require_once(EDV_PLUGIN_DIR . '/public/class.edv-edposts-import.php');
						edv_Posts_Import::import_ics($fileName, $post_status, $original_file_name);
					}
				}
			}
		}
		
		//Import d'un site
		$option_key = 'site_import';
		if( array_key_exists($option_key . '-confirm', $_POST[EDV_TAG])){
			$source_id = edv::get_option($option_key . '-source');
			edv_Admin_Multisite::import_site($source_id);
		}
		
		$static_updating = false;
	}
	/**
	* Hook avant mise à jour d'option
	*/
	public static function on_pre_update_option( $values, $old_values, $option ) {
		//clear confirmation and force a random value to hook update_option
		foreach(['site_import', 'edpost_import_ics'] as $option_key){
			if(array_key_exists($option_key . '-confirm', $values)){
				$values[$option_key . '-confirm'] = rand();
			}
		}
		return $values;
	}
	
	//import
	public static function set_import_report($logs){
		if( is_array($logs)){
			self::add_admin_notice( implode("\r\n", $logs), 'success', true);
		}
		else
			self::add_admin_notice($logs, 'success', true);
	}
	public static function get_import_report($clear = false){
		self::show_admin_notices();
	}
	
	public static function wpcf7_admin_notices($tag, $action, $contact_form){
		if( ! is_a($contact_form, 'WPCF7_ContactForm')){
			return;
		}
		foreach(['edpost_edit_form_id'
				, 'admin_message_contact_form_id'
				, 'edpost_message_contact_form_id'
				, 'contact_form_id'
				, 'newsletter_subscribe_form_id'] as $option){
			if($contact_form->id() == edv::get_option($option)){
				$label = edv::get_option_label($option);
				break;
			}
		}
		if(isset($label) && $label){
			?><br><div class="notice notice-info dashicons-before dashicons-warning">&nbsp;Ce formulaire est utilisé par l'Agenda partagé pour son paramètre "<?=$label?>".</div><?php
		}
	}
	
	/**
	* Logs
	*/
	//file
	/* public static function get_log_file($log_name){
		return sys_get_temp_dir() . '/' . EDV_TAG . '-'.$log_name.'.log';
	}
	//save
	public static function save_log($logs, $log_name){
		$f = self::get_log_file($log_name);
		if($logs === null){
			if(file_exists($f))
				unlink($f);
			return;
		}
		if(is_array($logs))
			$logs = implode("\r\n", $logs);
		file_put_contents($f, $logs);
	}
	//get
	public static function get_log($log_name, $clear = false){
		$f = self::get_log_file($log_name);
		if(!file_exists($f)) return;
		$logs = file_get_contents($f);
		if($clear)
			self::set_import_report(null);
		return $logs;
	} */
	
	/**
	*/
	public static function check_nonce(){
		if( ! isset($_POST['_nonce']))
			return false;
		return wp_verify_nonce( $_POST['_nonce'], 'edv-admin-nonce' );
	}
	
	/**
	 * Action required from Ajax query
	 * 
	 */
	public static function on_wp_ajax_admin_action_cb() {
		if( ! self::check_nonce())
			wp_die();
			
		$ajax_response = '0';
		if(!array_key_exists("method", $_POST)){
			wp_die();
		}
		$method = $_POST['method'];
		$data = empty($_POST['data']) ? [] : $_POST['data'];
		if(array_key_exists("user_id", $_POST)){
			try{
				//cherche une fonction du nom "user_action_{method}"
				$function = array('edv_Admin_User', sprintf('user_action_%s', $method));
				$ajax_response = call_user_func( $function, $_POST['user_id']);
			}
			catch( Exception $e ){
				$ajax_response = sprintf('Erreur dans l\'exécution de la fonction :%s', var_export($e, true));
			}
		}
		elseif(array_key_exists("post_type", $data)){
			try{
				switch($data['post_type']){
					case edv_Post::post_type :
						$class = 'edv_Admin_Post';
						break;
					default:
						$method = $data['post_type'] . '_' . $method;
						$class = __CLASS__;
						break;
				}
				// cherche une fonction du nom "{$post_type}_action_{method}"
				$function = array($class, sprintf('on_wp_ajax_action_%s', $method));
				$ajax_response = call_user_func( $function, $data );
			}
			catch( Exception $e ){
				$ajax_response = sprintf('Erreur dans l\'exécution de la fonction :%s', var_export($e, true));
			}
		}
		echo $ajax_response;
		
		// Make your array as json
		//wp_send_json($ajax_response);
	 
		// Don't forget to stop execution afterward.
		wp_die();
	}
}
?>