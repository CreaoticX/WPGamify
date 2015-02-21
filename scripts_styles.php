<?php
//Prevents file from being accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'init', 'gamify_enqueue_styles_scripts',  1000);
function gamify_enqueue_styles_scripts() {
    wp_enqueue_style( 'gamify-badge-css', plugins_url() . '/gamify/css/openbadges-displayer.min.css' );
    wp_enqueue_script("gamify-badge-js", plugins_url().'/gamify/js/openbadges-displayer.min.js','',"1.0",true);
}

add_action('init','gamify_enqueue_admin_styles_scripts');
function gamify_enqueue_admin_styles_scripts() {
    /** Register autocomplete script and stylesheet for admin pages */
    wp_enqueue_script("gamify-jquery-ui-autocomplete",plugins_url() . '/gamify/js/jquery-ui.min.js', array('jquery'),NULL,FALSE);
    wp_enqueue_style( 'jquery-ui-autocomplete-css', plugins_url() . '/gamify/css/jquery-ui.min.css' );
    wp_enqueue_style( 'jquery-ui-autocomplete-structure-css', plugins_url() . '/gamify/css/jquery-ui.structure.min.css' );
    wp_enqueue_style( 'jquery-ui-autocomplete-theme-css', plugins_url() . '/gamify/css/jquery-ui.theme.min.css' );
    wp_enqueue_style( 'wpgamify-styles',plugins_url() . '/gamify/css/styles.css');
}


?>