<?php
//Prevents file from being accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;


add_action( 'wp_enqueue_scripts', 'gamify_enqueue_styles_scripts',  1000);
function gamify_enqueue_styles_scripts() {
    wp_enqueue_style( 'gamify-badge-css', plugins_url() . '/gamify/css/openbadges-displayer.min.css' );
    wp_enqueue_script("gamify-badge-js", plugins_url().'/gamify/js/openbadges-displayer.min.js','',"1.0",true);
    wp_enqueue_script('openbadges', 'https://backpack.openbadges.org/issuer.js', array('jquery'), null);
    wp_enqueue_style( 'wpgamify-styles', plugins_url('css/styles.css', __FILE__) );
}


//Tried using admin_enqueue_scripts but it wasn't working
add_action('init','gamify_enqueue_admin_styles_scripts');
function gamify_enqueue_admin_styles_scripts() {
    /** Register autocomplete script and stylesheet for admin pages */
    wp_enqueue_script("gamify-jquery-ui-autocomplete",plugins_url() . '/gamify/js/jquery-ui.min.js', array('jquery'),NULL,FALSE);
    wp_enqueue_style( 'jquery-ui-autocomplete-css', plugins_url() . '/gamify/css/jquery-ui.min.css' );
    wp_enqueue_style( 'jquery-ui-autocomplete-structure-css', plugins_url() . '/gamify/css/jquery-ui.structure.min.css' );
    wp_enqueue_style( 'jquery-ui-autocomplete-theme-css', plugins_url() . '/gamify/css/jquery-ui.theme.min.css' );
    wp_enqueue_style( 'wpgamify-styles', plugins_url('css/styles.css', __FILE__) );
}

add_action('admin_enqueue_scripts', 'cp_admin_register_scripts');
function cp_admin_register_scripts() {
	/** Register datatables script and stylesheet for admin pages */
	wp_register_script('datatables',
		   CP_PATH . 'assets/datatables/js/jquery.dataTables.min.js',
		   array('jquery'),
		   '1.7.4' );
	wp_register_style('datatables', CP_PATH . 'assets/datatables/css/style.css');
	
}

/** Enqueue datatables */
function cp_datatables_script(){
	wp_enqueue_script('jquery');
	wp_enqueue_script('datatables');
}
function cp_datatables_style(){
	wp_enqueue_style('datatables');
}
add_action('admin_print_scripts-toplevel_page_cp_admin_manage', 'cp_datatables_script');
add_action('admin_print_styles-toplevel_page_cp_admin_manage', 'cp_datatables_style');
add_action('admin_print_scripts-cubepoints_page_cp_admin_logs', 'cp_datatables_script');
add_action('admin_print_styles-cubepoints_page_cp_admin_logs', 'cp_datatables_style');
add_action('admin_print_scripts-cubepoints_page_cp_admin_modules', 'cp_datatables_script');
add_action('admin_print_styles-cubepoints_page_cp_admin_modules', 'cp_datatables_style');

?>