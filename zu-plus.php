<?php
/*
Plugin Name: ZU+
Plugin URI: https://dmitryrudakov.ru/plugins/
GitHub Plugin URI: https://github.com/picasso/zu-plus
Description: This plugin encompasses ZU framework functionality.
Version: 1.3.8
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
// 
//			- extend class zuplus_Admin
//			- define your options in 'options_defaults'
// 		- modify 'validate_options' to process these options (for Boolean you can leave as is)
// 		- modify 'meta_boxes_callback' if need more boxes (generally Normal Boxes)
// 		- modify 'print_options' to output your basic options
//			- modify 'status_callback' to add something to Plugin Status
//			- modify 'ajax_more' if there are ajax actions
//

// define('ZUDEBUG', true);

// Prohibit direct script loading
defined('ABSPATH') || die('No direct script access allowed!');
define('ZUPLUS_VERSION', '1.3.8');
define('ZUPLUS_NAME', 'ZU+');
define('__ZUPLUS_ROOT__', plugin_dir_path(__FILE__)); 
define('__ZUPLUS_FILE__', __FILE__); 

// Do not include it in your file! --------------------------------------------]
require_once(__ZUPLUS_ROOT__ . 'includes/zuplus-plugin.php');
require_once(__ZUPLUS_ROOT__ . 'includes/debug/zuplus-debug.php');
require_once(__ZUPLUS_ROOT__ . 'includes/zuplus-duplicate-menu.php');
require_once(__ZUPLUS_ROOT__ . 'includes/zuplus-duplicate-page.php');

define('QM_HIDE_SELF', true);		// Hides the internal actions of Query Monitor in the output info from the plugin itself.
//
// define('GITHUB_UPDATER_OVERRIDE_DOT_ORG', true);
//

class ZU_Plugin extends zuplus_Plugin {

	public $dbug;

	protected function extend_config() {
		return  [
			'prefix'					=> 	'zuplus',
			'admin'					=> 	'ZU_Admin',
			'plugin_file'			=> 	__ZUPLUS_FILE__,
			'plugin_name'		=>	ZUPLUS_NAME,
			'version'				=> 	ZUPLUS_VERSION,
			'options_nosave'	=>	['log_location', 'source_menu', 'new_menu'],
		];
	}
	
	protected function construct_more() {
		
		$this->dbug = new ZU_Debug($this->config_addon()); 
		zu()->set_debug_cache($this->check_option('debug_cache'));
	}
}

class ZU_Admin extends zuplus_Admin {

	private $duppage;

	protected function construct_more() {
		
		if($this->check_option('dup_page')) {
			$this->duppage = new ZU_DuplicatePage($this->config_addon());
		}
	}
	
	//
	// Should/Could be Redefined in Child Class ----------------------------------]
	//
	
	// 	To modify menu and submenu you should pass array with optional keys  ['reorder', 'rename', 'remove', 'separator']
	//		If presented key should array of array with the following keys
	//		'menu'					- item-slug
	//		'new_index'			- new item position
	//		'after_index'			- item position will be after item with this slug
	//		'after_index2'		- item position will be after item with this slug + 1 (the space could be used for separator later)
	//		'before_index'		- item position will be before item with this slug
	//		'before_index2'		- item position will be before item with this slug - 1 (the space could be used for separator later)
	//		'new_name'			- new item name
	//		'parent'					- parent menu slug (if absent then  'options-general.php' will be used)

	protected function custom_admin_menu() {
		return [
			'reorder'	=>	[
				[
					'menu'				=> 	'upload.php',
					'before_index'	=>	'upload.php',
				],
				[
					'menu'				=> 	'edit.php',
					'after_index'		=>	'upload.php',
				],
				[
					'menu'				=> 	'genesis',
					'after_index'		=>	'options-general.php',
				],
			],
		];
	}

	protected function custom_admin_submenu() {
		global $_split_index;		
/*
		global $submenu;
		 _dbug_log('$submenu=', $submenu);
*/
		return [
			'reorder'	=>	[
				[
					'menu'					=> 	'zuplus-settings',
					'new_index'			=>	$_split_index + 12,
				],
				[
					'menu'					=> 	'options-permalink.php',
					'after_index'			=>	'options-discussion.php',
				],
			],
			'rename'	=>	[
				[
					'menu'			=> 	'ewww-image-optimizer-cloud/ewww-image-optimizer-cloud.php',
					'new_name'	=>	'EWWW Optimiser',
				],
			],
			'remove'	=>	[
				[
					'menu'			=>	'bbq_settings',
				],
				[
					'menu'			=>	'itsec-go-pro',
					'parent'			=>	'itsec',
				],				
			],
			'separator'	=>	[
				[
					'before_index'	=>	'zuplus-settings',
				],
			],
			
		];
	}

	protected function options_defaults() { 
		$zu_defaults = [
			'debug_log' 				=>	true,
			'ajax_log'					=>	false,
			'profiler'					=>	false,
			'debug_backtrace'		=>	false,
			'write_to_file'			=>	false,
			'beautify_html'			=>	true,
			'output_html'			=>	true,
			'debug_bar'				=>	true,
			'debug_cache'			=>	false,
			
			'dup_page'				=>	false,
		]; 
		
		return array_merge($zu_defaults, ZU_DuplicatePage::dup_defaults());
	}

	public function validate_options($input) {
		$new_values = parent::validate_options($input);
		if(isset($input['dup_status'])) $new_values['dup_status'] = $input['dup_status'];							// do not validate 'dup_status' value
		if(isset($input['dup_redirect'])) $new_values['dup_redirect'] = $input['dup_redirect'];						// do not validate 'dup_redirect' value
		if(isset($input['dup_suffix'])) $new_values['dup_suffix'] = $input['dup_suffix'];								// do not validate 'dup_suffix' value
	
		return $new_values;
	}

	protected function should_enqueue_css() {
		return true;
	}

	protected function should_enqueue_js() {
		return true;
	}
	
	public function meta_boxes_more($settings_page, $no_default_boxes) {

		// Add button to clear logs -------------------------------------------------]
		
		add_filter($this->prefix.'_print_debug_buttons', function() {
			return $this->form->button_link('zuplus_clear_log', __('Clear Debug Log', 'zu-plugin'), 'trash', 'blue');
		});

		// Custom Boxes -------------------------------------------------------------]
		
		$this->form->add_meta_box('log', __('Actual Log Location', 'zu-plugin'), [$this, 'print_log_location']);
		$this->form->add_meta_box('duplicate_menu', __('Duplicate Menu', 'zu-plugin'), [$this, 'print_duplicate_menu']);

		if($this->check_option('dup_page')) {
			$this->form->add_meta_box('duplicate_page', __('Duplicate Page', 'zu-plugin'), [$this, 'print_duplicate_page']);
		}

		$this->form->add_meta_box('all_actions', __('Theme Actions', 'zu-plugin'), [$this, 'print_all_actions'], 'advanced');
	}

	public function status_callback() {
		$stats = $this->plugin->dbug->log_stats();
		return sprintf('<p>Log size: <span>%1$s</span></p><p>Log priority: <span>%2$s</span></p>', $stats['size'], $stats['priority']);		
	}
	
	public function print_options($post) {


		$this->form->checkbox('ajax_log', 'Activate AJAX Logging', 'You should make <span>AJAX calls</span> from your JS.');
		$this->form->checkbox('profiler', 'Activate Profiler', 'You should call <span>_profiler_flag()</span> at each point of interest, passing a descriptive string.');

		$this->form->checkbox('debug_log', 'Activate Debug Logging', 'All calls to <span>_dbug_log()</span> functions will written to logfile.');

		$this->form->checkbox('debug_cache', 'Debug Caching', 'If checked, all calls to cache functions will be logged.');
		$this->form->checkbox('debug_bar', 'Use Debug Bar', 'Works only if <span>Query Monitor</span> is activated.');
		$this->form->checkbox('debug_backtrace', 'Always Include Backtrace', 'In some cases, this can <span>greatly slow down</span> the loading of the page and even lead to a fatal error.');
		$this->form->checkbox('write_to_file', 'Write log to file', 'If unchecked, only the information for <span>Debug Bar</span> will be saved.');
		$this->form->checkbox('output_html', 'Display HTML entities in Debug Bar', 'If checked, all characters which have HTML character entity equivalents are translated into these entities.');
		$this->form->checkbox('beautify_html', 'Beautify HTML in output', 'If unchecked, all HTML values will be saved without any modifications. Otherwise HTML beautifier will be used.');
		
		$this->form->checkbox('dup_page', 'Activate Duplicate Page', 'Allows duplicate Posts, Pages and Custom Posts using single click.');
		
	
		echo $this->form->fields('The plugin encompasses ZU framework functionality.');
		echo $this->form->print_save_mobile();
	}

	public function print_log_location($post) {

		$this->form->add_value('log_location', $this->plugin->dbug->log_location());
		$this->form->text('log_location', 'Log Location', '', true);

		echo $this->form->fields('It can be changed with the function <span>_dbug_change_log_location()</span>.');
	}

	public function print_duplicate_menu($post) {

        $nav_menus = wp_get_nav_menus();
		$desc = empty($nav_menus) ? 'You haven\'t created any Menus yet.' : 'Here you can easily duplicate WordPress Menus.';

		if(!empty($nav_menus)) {

			$select_values = [];	
			foreach($nav_menus as $_nav_menu) $select_values[$_nav_menu->term_id] = $_nav_menu->name;

			$this->form->add_value('source_menu', array_keys($select_values)[0]);
			$this->form->select('source_menu', 'Duplicate this menu:', $select_values);
			
			$this->form->add_value('new_menu', '?');
            $this->form->text('new_menu', 'And call it', 'The name will be assigned to duplicated menu.');
        
			$this->form->button_link('zuplus_duplicate_menu', __('Duplicate', 'zu-plugin'), 'images-alt2', 'red', true, false);
		}
		
		echo $this->form->fields($desc, 'zuplus_duplicate_menu', true); // second argument -> data-ajaxrel : used in js to serialize form
	}

	public function print_duplicate_page($post) {

		$this->form->select(
			'dup_status', 
			'Duplicate Post Status:', 
			$this->duppage->status_values(), 
			'Select any post status you want to assign for duplicate post.'
		);

		$this->form->select(
			'dup_redirect', 
			'Redirect to after click on link:', 
			$this->duppage->redirect_values(), 
			'Select any post redirection after click on <strong>"Duplicate This"</strong> link.'
		);
		
        $this->form->text('dup_suffix', 'Duplicate Post Suffix', 'Add a suffix for duplicate post as Copy, Clone etc. It will show after title.');
		
		echo $this->form->fields('Duplicate Page Settings.');
	}
	
	public function print_all_actions($post) {

		$this->form->button_link_with_help('zuplus_reset_cached', 
			__('Reset All Cached Shortcodes', 'zu-plugin'), 
			'dismiss', 
			'magenta', 
			'Clear all cached data referenced to shortcodes (<strong>gallery</strong> and <strong>select</strong>). Needs if something went wrong.'
		);

		echo $this->form->fields('Actions available for ZU Theme.', 'zuplus_reset_cached', true); // second argument -> data-ajaxrel : used in js to serialize form
	}
	
	public function ajax_more($option_name, $ajax_value) {
		if($option_name === 'zuplus_clear_log') return $this->plugin->dbug->clear_log();
		if($option_name === 'zuplus_duplicate_menu') return zuplus_ajax_duplicate_menu();

		if($option_name === 'zuplus_reset_cached') return $this->reset_cached();

		return [];					
	}
	
	public function reset_cached() {
		$count = zu()->purge_transients();		
		return ['info'	=> $count ? sprintf('All cached shortcodes (<span class="_bold">%1$s %2$s</span>) were reset.', $count, $count > 1 ? 'transients' : 'transient') : 'No cached shortcodes was found.'];
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
do_action('zuplus_loaded');