<?php
/**
 * @package EDVariables engine
 */
/*
 * Plugin Name: EDVariables engine
 * Plugin URI: https://github.com/edpostiables/wp_edv
 * Text Domain: edpostiables
 * Description: Gestionnaire de variables éditables par les visiteurs.
 Visitiable et testable : https://edv.fr
 Only in french language...
 - Plugins obligatoires :
	- WP Contact Form 7
- Plugins conseillés
	- Akismet Anti-Spam
	- ReCaptcha v2 for Contact Form 7
	- WP Mail Smtp - SMTP7
 * Author: Emmanuel Durand, edid@free.fr
 * Author URI: https://edv.fr
 * Tags: 
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Version: 0.0.1
*/

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'EDV_VERSION', '0.0.1' );
define( 'EDV_MINIMUM_WP_VERSION', '5.0' );

define( 'EDV_PLUGIN', __FILE__ );
define( 'EDV_PLUGIN_BASENAME', plugin_basename( EDV_PLUGIN ) );
define( 'EDV_PLUGIN_NAME', trim( str_replace ( '-', '', dirname( EDV_PLUGIN_BASENAME ) ), '/' ) );
define( 'EDV_PLUGIN_DIR', untrailingslashit( dirname( EDV_PLUGIN ) ) );
define( 'EDV_PLUGIN_MODULES_DIR', EDV_PLUGIN_DIR . '/modules' );

define( 'EDV_TAG', 'edv' ); 
define( 'EDV_EMAIL_DOMAIN', strtolower(EDV_PLUGIN_NAME) . '.replace.' . EDV_TAG ); //Sert à ce que les valeurs fournies par WPCF7 soient remplacées
define( 'EDV_MAILLOG_ENABLE', 'maillog_enable');
define( 'EDV_DEBUGLOG_ENABLE', 'debuglog_enable');
define( 'EDV_CONNECT_MENU_ENABLE', 'connect_menu_enable');
			
//argument de requête pour modification d'évènement. code généré par edpostiables::get_secret_code()
define( 'EDV_POST_SECRETCODE', 'codesecret' ); 
define( 'EDV_COVOIT_SECRETCODE', 'covsecret' ); 
define( 'EDV_ARG_POSTID', 'edpostid' ); 
define( 'EDV_ARG_NEWSLETTERID', 'edvnlid' ); 
define( 'EDV_ARG_COVOITURAGEID', 'covoitid' ); 
define( 'EDV_EMAIL4PHONE', 'email4phone' ); 
define( 'EDV_PAGE_META_FORUM', 'edvforum' ); 
define( 'EDV_FORUM_META_PAGE', 'comments-page' ); 
//répertoire des fichiers attachés aux emails des forums
define( 'EDV_FORUM_ATTACHMENT_PATH', false);//__DIR__ . '/attachments'); TODO

// see translate_level_to_role()
define( 'USER_LEVEL_ADMIN', 8 ); 
define( 'USER_LEVEL_EDITOR', 5 ); 
define( 'USER_LEVEL_AUTHOR', 2 ); 
define( 'USER_LEVEL_CONTRIBUTOR', 1 ); 
define( 'USER_LEVEL_SUBSCRIBER', 0 ); 
define( 'USER_LEVEL_NONE', 0 ); 

require_once( EDV_PLUGIN_DIR . '/includes/functions.php' );
require_once( EDV_PLUGIN_DIR . '/public/class.edv.php' );

//plugin_activation
register_activation_hook( __FILE__, array( EDV_TAG, 'plugin_activation' ) );
//plugin_deactivation
register_deactivation_hook( __FILE__, array( EDV_TAG, 'plugin_deactivation' ) );

add_action( 'admin_menu', 'edpostiables_admin_menu' );
function edpostiables_admin_menu(){
	require_once( EDV_PLUGIN_DIR . '/admin/class.edv-admin-menu.php' );
	edv_Admin_Menu::init();
}

add_action( 'init', array( 'edv', 'init' ) );
add_action( 'admin_init', array( 'edv', 'admin_init' ) );