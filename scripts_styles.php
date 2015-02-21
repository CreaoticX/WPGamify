<?php
//Prevents file from being accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'wp_enqueue_scripts', 'gamify_enqueue_styles_scripts',  1000);
function gamify_enqueue_styles_scripts() {
    wp_enqueue_style( 'gamify-badge-css', plugins_url() . '/gamify/css/openbadges-displayer.min.css' );
    wp_enqueue_script("gamify-badge-js", plugins_url().'/gamify/js/openbadges-displayer.min.js','',"1.0",true);
}

?>