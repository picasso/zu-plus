<?php
/*
Plugin Name: Zu Plus
Plugin URI: https://github.com/picasso/zu-plus
GitHub Plugin URI: https://github.com/picasso/zu-plus
Description: Supports development with the Zukit framework and implements various debugging methods and other service functions.
Version: 2.0.3
Author: Dmitry Rudakov
Author URI: https://dmitryrudakov.com/about/
Text Domain: zu-plus
Domain Path: /lang/
Requires at least: 5.3.0
Requires PHP: 7.4.1
*/

// Prohibit direct script loading
defined('ABSPATH') || die('No direct script access allowed!');
// Exit early if a WordPress heartbeat comes
if(wp_doing_ajax() && isset($_POST['action']) && ($_POST['action'] === 'heartbeat')) return;
// Let's not load plugin during cron events
if(wp_doing_cron()) return;

// Start! ---------------------------------------------------------------------]

// add_action('plugins_loaded', function() { 	// DEBUG ONLY

require_once('zukit/load.php');

// compatibility check for Zukit
if(Zukit::is_compatible(__FILE__, array('min_php' => '7.4.1') )) {
	require_once('includes/zuplus-plugin.php');
	zuplus(__FILE__);
}

// });

// Hides the internal actions of Query Monitor in the output info from the plugin itself
define('QM_HIDE_SELF', true);
