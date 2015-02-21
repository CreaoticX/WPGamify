<?php

/** Admin pages */
function wpgamify_admin() {
	add_menu_page('WPGamify', 'WPGamify', 'manage_options', 'cp_admin_manage', 'cp_admin_manage');
	add_submenu_page('cp_admin_manage', 'WPGamify - ' .__('Users','cp'), __('Users','cp'), 'manage_options', 'cp_admin_manage', 'cp_admin_manage');
	add_submenu_page('cp_admin_manage', 'WPGamify - ' .__('Award Points','cp'), __('Add Points','cp'), 'manage_options', 'cp_admin_add_points', 'cp_admin_add_points');
	add_submenu_page('cp_admin_manage', 'WPGamify - ' .__('Configure','cp'), __('Configure','cp'), 'manage_options', 'cp_admin_config', 'cp_admin_config');
	add_submenu_page('cp_admin_manage', 'WPGamify - ' .__('Point Logs','cp'), __('Logs','cp'), 'manage_options', 'cp_admin_logs', 'cp_admin_logs');
	add_submenu_page('cp_admin_manage', 'WPGamify - ' .__('Modules','cp'), __('Modules','cp'), 'manage_options', 'cp_admin_modules', 'cp_admin_modules');
	do_action('cp_admin_pages');
}

/** Include admin pages */
	require_once('cp_admin_manage.php');
	require_once('cp_admin_add_points.php');
	require_once('cp_admin_config.php');
	require_once('cp_admin_logs.php');
	require_once('cp_admin_modules.php');

function wpgamify_add_query_vars_filter( $vars ){
  $vars[] = "wpguser";
  return $vars;
}
add_filter( 'query_vars', 'wpgamify_add_query_vars_filter' );
	
        
/** Hook for admin pages */
add_action('admin_menu', 'wpgamify_admin');

