<?php
/*
Plugin Name: ZU+
Plugin URI: https://dmitryrudakov.ru/plugins/
GitHub Plugin URI: https://github.com/picasso/zu-plus
Description: This plugin encompasses ZU framework functionality.
Version: 0.5.5
Author: Dmitry Rudakov
Author URI: https://dmitryrudakov.ru/about/
Text Domain: zu-plugin
Domain Path: /lang/
*/

//	
// 	How adapt for a new plugin:
//
// 		- replace 'ZUPLUS_' for 'YOUR_'
// 		- replace 'zuplus_' for 'your_'
//			- replace 'zuplus-' for 'your-'
//			- extend class zuplus_Plugin
//			- provide config array in __construct() of extended class zuplus_Plugin
//			- extend class zuplus_Admin
//			- define your options in 'options_defaults'
// 		- modify 'validate_options' to process these options (for Boolean you can leave as is)
// 		- modify 'meta_boxes_callback' if need more boxes (generally Normal Boxes)
// 		- modify 'print_options' to output your basic options
//			- modify 'status_callback' to add something to Plugin Status
//			- modify 'ajax_more' if there are ajax actions
//

// Prohibit direct script loading
defined('ABSPATH') || die('No direct script access allowed!');
define('ZUPLUS_VERSION', '0.5.5');
define('ZUPLUS_NAME', 'ZU+');
define('__ZUPLUS_ROOT__', plugin_dir_path(__FILE__)); 
define('__ZUPLUS_FILE__', __FILE__); 

// Do not include it in your file! --------------------------------------------]
require_once(__ZUPLUS_ROOT__ . 'includes/zuplus-plugin.php');
require_once(__ZUPLUS_ROOT__ . 'includes/debug/zuplus-debug.php');

class ZU_Plugin extends zuplus_Plugin {

	public $dbug;

	function __construct() {
		
		$config = [
			'prefix'					=> 	'zuplus',
			'admin'					=> 	'ZU_Admin',
			'plugin_file'			=> 	__ZUPLUS_FILE__,
			'plugin_name'		=>	ZUPLUS_NAME,
			'version'				=> 	ZUPLUS_VERSION,
			'options_nosave'	=>	['log_location'],
		];
		parent::__construct($config);
		$this->dbug = new ZU_Debug($this->options()); 
	}
}

class ZU_Admin extends zuplus_Admin {
	
	//
	// Should/Could be Redefined in Child Class ----------------------------------]
	//

	protected function options_defaults() { 
		return [
			'debug_log' 		=>	true,
			'ajax_log'			=>	false,
			'profiler'			=>	false,
			'debug_bar'		=>	true,
			'zu_cache'			=>	false,
		]; 
	}

	public function meta_boxes_callback($settings_page, $no_default_boxes = false) {
	
		parent::meta_boxes_callback($settings_page, $no_default_boxes);

		// Add button to clear logs -------------------------------------------------]
		
		add_filter($this->prefix_default.'_print_debug_buttons', function() {
			return $this->form->button_link($this->prefix_default.'_clear_log', __('Clear Debug Log', 'zu-plugin'), 'trash', 'blue');
		});

		// Custom Boxes -------------------------------------------------------------]
		
		$this->form->add_meta_box('log', __('Actual Log Location', 'zu-plugin'), [$this, 'print_log_location']);
	}

	public function status_callback() {
		$stats = $this->plugin->dbug->log_stats();
		return sprintf('<p>Log size: <span>%1$s</span></p><p>Log priority: <span>%2$s</span></p>', $stats['size'], $stats['priority']);		
	}
	
	public function print_options($post) {

		$this->form->checkbox('debug_log', 'Activate Debug Logging', 'All calls to <span>_dbug_log()</span> functions will written to logfile.');

		$this->form->checkbox('zu_cache', 'Activate ZU Caching', 'You should include calls to cache functions to use it.');
		$this->form->checkbox('ajax_log', 'Activate AJAX Logging', 'You should make <span>AJAX calls</span> from your JS.');
		$this->form->checkbox('profiler', 'Activate Profiler', 'You should call <span>_profiler_flag()</span> at each point of interest, passing a descriptive string.');
		$this->form->checkbox('debug_bar', 'Use Debug Bar', 'Works only if <span>Query Monitor</span> is activated.');
		
	
		echo $this->form->fields('The plugin encompasses ZU framework functionality.');
		echo $this->form->print_save_mobile();
	}

	public function print_log_location($post) {

		$this->form->add_value('log_location', $this->plugin->dbug->log_location());
		$this->form->text('log_location', 'Log Location', '', true);

		echo $this->form->fields('It can be changed with the function <span>_dbug_change_log_location()</span>.');
	}
	
	public function ajax_more($option_name) {
		if($option_name === 'zuplus_clear_log') return $this->plugin->dbug->clear_log();
		return [];					
	}
}

// Helpers --------------------------------------------------------------------]

function zuplus_get_my_dir() {
	return untrailingslashit(__ZUPLUS_ROOT__);
}

function zuplus_get_my_url() {
	return untrailingslashit(plugin_dir_url(__FILE__));
}

function zuplus_instance() { 
	return ZU_Plugin::instance(); 
}

function zuplus_options() {
	return zuplus_instance()->options();
}

// Start! --------------------------------------------------------------------]

zuplus_instance();
