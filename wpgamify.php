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
require_once 'cubepoints.php';
require_once 'wpgamifymission.php';
require_once 'wpgamifydefaultmissions.php';
