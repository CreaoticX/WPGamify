<?php
/**
 * @package WPGamify
 */
/*
Plugin Name: WPGamify
Plugin URI: https://github.com/CreaoticX/WPGamify
Description: A Gamification platform of Wordpress that integrates existing WP Plugins into one Gamification Plugin
Version: 0.1.0
Author: Cory Fritsch
Author URI: https://github.com/CreaoticX
*/
//Prevents file from being accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

require_once 'config.inc.php';
require_once 'classes/ClassGamifyMisc.php';
require_once 'scripts_styles.php';
require_once 'wpbadger.php';
require_once 'wpbadgedisplay.php';
require_once 'wpgamify_mission.php';
require_once 'wpgamify_default_missions.php';
require_once 'wpgamify_admin.php';
require_once 'wpgamify_points_core.php';
require_once 'wpgamify_install.php';
require_once 'wpgamify_curl.php';

global $wpdb;

/** Define constants */
define('WPGAMIFY_VERSION', '1.0');
define('CP_DB', $wpdb->base_prefix . 'cp');
define('CP_PATH', WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)));

/** Set CubePoints Version **/
add_option('wpgamify_version', WPGAMIFY_VERSION);

/** Loads the plugin's translated strings */
load_plugin_textdomain('cp', false, dirname(plugin_basename(__FILE__)).'/languages');

/** Includes upgrade script */
require_once 'cp_upgrade.php';

/** Includes core functions */
require_once 'cp_core.php';

/** Includes plugin hooks */
require_once 'cp_hooks.php';

/** Includes plugin APIs */
require_once 'cp_api.php';

/** Includes widgets */
require_once 'cp_widgets.php';

/** Includes logs display */
require_once 'cp_logs.php';

/** Hook for plugin installation */
register_activation_hook( __FILE__ , 'cp_activate' );
function cp_activate(){
    $wpg_install = new WPGamify_Install();
    $wpg_install->install();
}

/** Include all modules in the modules folder */
add_action('plugins_loaded','cp_modules_include',2);

/** Checks if modules have been updated and run activation hook */
add_action('init', 'cp_modules_updateCheck');

?>