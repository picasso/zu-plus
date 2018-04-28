<?php
// * * $hook - the hook of the 'parent' (menu top-level page).
// * * $title - the browser window title of the page
// * * $menu - the page title as it appears in the menu
// * * $permissions - the capability a user requires to see the page
// * * $slug - a slug identifier for this page

class zuplus_Admin {

	protected $plugin;	
	
	protected $plugin_name;	
	protected $plugin_file;	
	protected $prefix;
	protected $prefix_default;
	protected $version;

	protected $hook;
	protected $title;
	protected $menu;
	protected $permissions;
	protected $slug;
	protected $template;
	
	protected $options;
	protected $options_defaults;
	protected $options_nosave;

	public $errors_id;
	public $options_id;

	public $hook_suffix = null;	
	public $form = null;	
	
	public $debug_value;

	public function __construct($options, $plugin) {
		
		$defaults = [
			'prefix' 					=>	'zuplus',
			'version' 				=>	'',
			'plugin_name' 		=> 	'',
			'plugin_file' 			=> 	'',
			'hook' 					=>	'options-general.php',
			'title' 					=>	'',
			'menu' 					=>	'',
			'permissions' 		=>	'manage_options',
			'slug' 					=>	'',
			'template' 				=>	'',
		
			'errors_id' 			=>	'',
			'options_id'			=>	'',
			'options_nosave'	=>	[],			
		];
		
		$options = array_merge($defaults, empty($options) ? [] : $options);
		
		$this->plugin = $plugin;
		$this->prefix = $options['prefix'];
		$this->prefix_default = $defaults['prefix'];
		
		$this->version = $options['version'];
		$this->plugin_name = $options['plugin_name'];
		$this->plugin_file = $options['plugin_file'];
		
		$this->title = empty($options['title']) ? sprintf('%2$s %1$s', __('Options', 'zu-plugin'), $this->plugin_name) : $options['title'];
		$this->menu = empty($options['menu']) ? $this->plugin_name : $options['menu'];
		$this->slug = empty($options['slug']) ? $this->prefix . '-settings' : $options['slug'];
		$this->hook = $options['hook'];
		$this->permissions = $options['permissions'];
		$this->template = $options['template'];

		$this->options_id = empty($options['options_id']) ? $this->prefix . '_options' : $options['options_id'];
		$this->errors_id = empty($options['errors_id']) ? $this->prefix . '_errors' : $options['errors_id'];

		$this->options_defaults = array_merge($this->options_defaults(), ['error' => 0]);
		$this->options_nosave = $options['options_nosave'];			// temporary fields which we do not want to save

		$option_value = function($name, $default = false) { return isset($_GET[$name]) && !empty($_GET[$name]) ? $_GET[$name] : $default; };
		
		$test_plugin = $option_value($this->plugin_prefix(true) . '_test');
		$clear_errors = $option_value($this->plugin_prefix(true) . '_clear_errors');

		//
		// Activation Hook ----------------------------------------------------------]
		//
		register_activation_hook($this->plugin_file, function() {

			if(!version_compare(PHP_VERSION, '7.0.0', '>=')) {
				add_action('update_option_active_plugins', function() { deactivate_plugins(plugin_basename($this->plugin_file));});
				wp_die(sprintf('%1$s required PHP at least 7.0.x. %1$s was deactivated. <a href="%2$s">Go Back</a>', $this->plugin_name, admin_url()));
			}

			if(!function_exists('zu')) {
				add_action('update_option_active_plugins', function() { deactivate_plugins(plugin_basename($this->plugin_file));});
				wp_die(sprintf('%1$s required %3$s be activated. %1$s was deactivated. <a href="%2$s">Go Back</a>', $this->plugin_name, admin_url(), ZUPLUS_NAME));
			}

			if(!get_option($this->options_id)) add_option($this->options_id, $this->options_defaults);
			if(!get_option($this->errors_id)) add_option($this->errors_id, []);
		});
		
		register_deactivation_hook($this->plugin_file, function() {
			delete_option($this->options_id);
			delete_option($this->errors_id);
			$this->plugin->clean_addons();
			$this->deactivation_clean();
// 			wp_clear_scheduled_hook('zuplus_cron');
		});
		add_filter('plugin_action_links_'.plugin_basename($this->plugin_file), [$this, 'admin_settings_link']);

		$this->get_options();
		if(!isset($this->options['error'])) $this->set_or_dismiss_error(0);
	
		add_action('admin_init', function() {
			register_setting($this->options_id, $this->options_id, [$this, 'validate_options']);
			register_setting($this->errors_id, $this->errors_id);		
			
			// Add admin class 
			if(function_exists('zu')) zu()->add_admin_body_class($this->prefix_default);
			else $this->report_error(['error' => sprintf('%1$s must be activated! Cannot continue.', ZUPLUS_NAME)]);
		});
		
		add_action('admin_menu', [$this, 'admin_menu']);
		add_action('wp_ajax_'.$this->prefix.'_option', [$this, 'ajax_turn_option']);
		add_action('admin_enqueue_scripts', [$this, 'admin_enqueue']);
		add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_fonts']);
		add_filter('custom_menu_order', [$this, 'admin_menu_modify']);

		//
		// Show errors, if there are ------------------------------------------------]
		//
		add_action('admin_notices', [$this, 'show_errors']);
		
		//
		// Add metaboxes to the page ------------------------------------------------]
		//
		add_action('add_meta_boxes', function() {
			$settings_page = add_query_arg(['page' => $this->slug], admin_url($this->hook));
			$this->meta_boxes_callback($settings_page);
		});
		
		//
		// Basic actions ------------------------------------------------------------]
		//
		if($test_plugin) $this->plugin_test();
		if($clear_errors) $this->empty_errors();

		//
		// Init additional classes --------------------------------------------------]
		//
		$this->construct_more();		
	}

	protected function construct_more() {
	}

	protected function deactivation_clean() {
	}

	//
	// Admin menu modify ---------------------------------------------------------]
	//
	protected function custom_admin_menu() {
		return [];
	}

	protected function custom_admin_submenu() {
		return [];
	}

	public function admin_menu_modify($menu_order) {
	    global $menu, $submenu;

		// clean positions from 41 to 49
		
		$this->submenu_move(41, 61, 9);

		// modify main menu items
		
	    $menu_modify = $this->custom_admin_menu();
  
		if(isset($menu_modify['reorder'])) {  
		    foreach($menu_modify['reorder'] as $menu_item) {
		    	$this->menu_reorder($menu_item['menu'], $this->get_new_index($menu_item));
		    }
		}

    	if(isset($menu_modify['rename'])) {
		    foreach($menu_modify['rename'] as $menu_item) {
		    	$index = $this->get_menu_index($menu_item['menu']);
		    	if($index > 0) $menu[$index][0] = $menu_item['new_name'];
		    }
		}
		
    	if(isset($menu_modify['remove'])) {
		    foreach($menu_modify['remove'] as $menu_item) {
		    	$index = $this->get_menu_index($menu_item['menu']);
		    	if($index > 0) {
			    	unset($menu[$index]);
					ksort($menu);
				}
		    }
		}
	    
		// modify submenu items
		
	    $submenu_modify = $this->custom_admin_submenu();
  
		if(isset($submenu_modify['reorder'])) {  
		    foreach($submenu_modify['reorder'] as $menu_item) {
		    	$submenu_parent = isset($menu_item['parent']) ? $menu_item['parent'] : 'options-general.php';
		    	$this->submenu_reorder($menu_item['menu'], $this->get_new_index($menu_item, $submenu_parent), $submenu_parent);
		    }
		}
		
    	if(isset($submenu_modify['rename'])) {
		    foreach($submenu_modify['rename'] as $menu_item) {
		    	$submenu_parent = isset($menu_item['parent']) ? $menu_item['parent'] : 'options-general.php';
		    	$index = $this->get_submenu_index($menu_item['menu'], $submenu_parent);
		    	if($index > 0) $submenu[$submenu_parent][$index][0] = $menu_item['new_name'];
		    }
		}
		
    	if(isset($submenu_modify['remove'])) {
		    foreach($submenu_modify['remove'] as $menu_item) {
		    	$submenu_parent = isset($menu_item['parent']) ? $menu_item['parent'] : 'options-general.php';
		    	$index = $this->get_submenu_index($menu_item['menu'], $submenu_parent);
		    	if($index > 0) {
			    	unset($submenu[$submenu_parent][$index]);
					ksort($submenu[$submenu_parent]);
				}
		    }
		}

		// add separators if needed

		if(isset($submenu_modify['separator'])) {
		    foreach($submenu_modify['separator'] as $menu_item) {
		    	$submenu_parent = isset($menu_item['parent']) ? $menu_item['parent'] : 'options-general.php';
		    	$index = $this->get_new_index($menu_item, $submenu_parent);
		    	if($index > 0 && !isset($submenu[$submenu_parent][$index])) {
			    	$submenu[$submenu_parent][$index] = ['','read', 'separator'.$index, '', 'wp-menu-separator'];
					ksort($submenu[$submenu_parent]);
				}
		    }
		}
	
	    return $menu_order;
	}

	private function get_new_index($menu_item, $submenu_parent = '') {
	    $new_index = isset($menu_item['new_index']) ? $menu_item['new_index'] : -1;	    
	    if($new_index < 0) {
		    $base_key = array_values(array_intersect(array_keys($menu_item), ['before_index', 'before_index2', 'after_index', 'after_index2'])); 
		    $base_menu = empty($base_key) ? '' : $menu_item[$base_key[0]];
		    $base_index = empty($submenu_parent) ? $this->get_menu_index($base_menu) : $this->get_submenu_index($base_menu, $submenu_parent);
		    if($base_index < 0) return (PHP_INT_MAX - 1);
		    $index_shift = intval(filter_var($base_key[0], FILTER_SANITIZE_NUMBER_INT)) ? : 1;
			$new_index = strpos($base_key[0], 'before') === false ? $base_index + $index_shift : $base_index - $index_shift;
	    }
		return $new_index;
	}

	private function get_menu_index($menu_item) {
		global $menu;
		
		$index = -1;
	    foreach($menu as $key => $details) {
	        if($details[2] == $menu_item) {
	            $index = $key;
	        }
	    }
	    return $index;
	}

	private function menu_reorder($menu_item, $new_index) {
		global $menu;
		
		$index = $this->get_menu_index($menu_item);
	    if($index > 0) {
		    $menu[$new_index] = $menu[$index];
		    unset($menu[$index]);
		    ksort($menu);				// Reorder the menu based on the keys in ascending order
	    }
	}

	private function get_submenu_index($menu_item, $submenu_parent = 'options-general.php') {
		global $submenu;
		
		$index = -1;
	    $subitems = isset($submenu[$submenu_parent]) ? $submenu[$submenu_parent] : [];			// Get submenu key location based on slug
	    foreach($subitems as $key => $details) {
	        if($details[2] == $menu_item) {
	            $index = $key;
	        }
	    }
	    return $index;
	}

	private function submenu_reorder($menu_item, $new_index, $submenu_parent) {
		global $submenu;
		
		$index = $this->get_submenu_index($menu_item, $submenu_parent);
	    if($index > 0) {
		    $submenu[$submenu_parent][$new_index] = $submenu[$submenu_parent][$index];
		    unset($submenu[$submenu_parent][$index]);
		    ksort($submenu[$submenu_parent]);				// Reorder the menu based on the keys in ascending order
	    }
	}

	private function submenu_move($from_index, $to_index, $count = 1, $submenu_parent = 'options-general.php') {
		global $submenu;
		
		$total_index_break = 220;	// maybe max items count in submenu
		$is_separator = true;
		
		while($count-- > 0) {
			if($to_index >= $total_index_break) break;

			if(isset($submenu[$submenu_parent][$from_index])) {
				$move_item = $submenu[$submenu_parent][$from_index];
				unset($submenu[$submenu_parent][$from_index]);
				while($to_index < $total_index_break) {
					if(isset($submenu[$submenu_parent][$to_index])) {
						$to_index++;
						continue;
					}
					if($is_separator) {
						$submenu[$submenu_parent][$to_index++] = ['','read', 'separator_moved', '', 'wp-menu-separator'];
						$is_separator = false;
						continue;
					}
					$submenu[$submenu_parent][$to_index++] = $move_item;
					break;
				}
			}
			$from_index++;
		}
				
	    ksort($submenu[$submenu_parent]);				// Reorder the menu based on the keys in ascending order
	}

	//
	// Config & Options ----------------------------------------------------------]
	//
	
	public function config_addon($more_params = []) {
		return $this->plugin->config_addon($more_params);
	}

	public function register_addon($addon) {
		return $this->plugin->register_addon($addon);
	}
	
	public function check_option($key, $check = true) { 
		return $this->plugin->check_option($key, $check);
	}

	public function plugin_prefix($default = false) {
		return $default ? $this->prefix_default : $this->prefix;
	}

	protected function plugin_root() {
		return plugin_dir_path($this->plugin_file);
	}
	
	public function get_options() { 
		$this->options = get_option($this->options_id, $this->options_defaults); 
		return $this->options; 
	}
	
	protected function update_options() { 
		update_option($this->options_id, $this->options); 
	}
	
	//
	// JS & CSS enqueue ----------------------------------------------------------]
	//
	
	protected function should_enqueue_css() {
		return false;
	}

	protected function should_enqueue_js() {
		return false;
	}
	
	public function admin_enqueue_fonts() {

		$font_families = [];
		$font_families[] = 'Open+Sans:300italic,400italic,600italic,700italic,800italic,400,300,600,700,800';
		$protocol = is_ssl() ? 'https' : 'http';
		$query_args = [
			'family' => implode( '%7C', $font_families ),
			'subset' =>  'cyrillic',
		];
		$fonts_url = add_query_arg($query_args, "$protocol://fonts.googleapis.com/css");

		wp_enqueue_style('open-sans-cyr', esc_url_raw($fonts_url), [], $this->version);
	}

	public function admin_enqueue() {		

		$data = [
			'ajaxurl'                => admin_url('admin-ajax.php'),
			'admin_nonce'     	=> $this->ajax_nonce(true),
			'screen_id'				=> $this->hook_suffix,
		];
		
		if($this->should_enqueue_css()) {
			
			$filename = 'css/'.$this->prefix.'-admin.css';
			$filepath = plugin_dir_path($this->plugin_file).$filename;
			if(file_exists($filepath)) {
				$version = filemtime($filepath);
				wp_enqueue_style($this->prefix.'-style', plugins_url($filename, $this->plugin_file), [], defined('ZUDEBUG') ? $version : $this->version);
			}
		}
		
		if($this->should_enqueue_js()) { 
			
			$filename = 'js/'.$this->prefix.'-admin.min.js';
			$filepath = plugin_dir_path($this->plugin_file).$filename;
			if(file_exists($filepath)) {
				$version = filemtime($filepath);
				wp_enqueue_script($this->prefix.'-script', plugins_url($filename, $this->plugin_file), ['jquery'], defined('ZUDEBUG') ? $version : $this->version, true);
				wp_localize_script($this->prefix.'-script', $this->prefix.'_custom', $data);
			}
		}
	}
	
	public function admin_settings_link($links) {
		$settings_link = sprintf('<a href="%1$s%2$s?page=%3$s">%4$s</a>', get_admin_url(), $this->hook, $this->slug, __('Settings', 'textdomain'));
		array_unshift($links, $settings_link);
		return $links;
	}
	
	//
	// Helpers -------------------------------------------------------------------]
	//

	public function ajax_nonce($create = false) { 
		$ajax_nonce = $this->prefix.'_ajax_nonce';
		return $create ? wp_create_nonce($ajax_nonce) : $ajax_nonce; 
	}

	public function validate_emails($input, $key) {
	
		$mails = explode(',', isset($input[$key]) ? $input[$key] : '');
		foreach($mails as $key => $value) {
			$mails[$key] = filter_var($value, FILTER_VALIDATE_EMAIL);
		}		
		
		return implode(',', array_filter($mails));
	}
	
	//
	// Should/Could be Redefined in Child Class ----------------------------------]
	//

	protected function options_defaults() { 
		return ['error' => 0]; 
	}

	public function validate_options($input) {
		
		$new_values = array_diff_key($input, array_flip($this->options_nosave));		// remove unwanted values
		
		//		if INT				- intval($input['?']);
		//		if BOOL 			- filter_var($input['?'], FILTER_VALIDATE_BOOLEAN);
		//		if Filename		- preg_replace('#[^A-z0-9-_\.\,\/]#', '', $input['?']);
		//		If Identificator	- preg_replace('#[^A-z0-9-_]#', '', $input['?']);
		
		// Suppose that all are BOOLEAN
		foreach($new_values as $key => $value) {
			$new_values[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
		}		
		
		if(isset($input['error'])) $new_values['error'] = $input['error'];							// do not validate error value
		
		return $new_values;
	}
	
	public function meta_boxes_callback($settings_page, $no_default_boxes = false) {

		if($this->hook_suffix == false) return false;
		
		$this->form = new zuplus_Form($this->plugin_root());
		$this->form->add_admin_meta_boxes($settings_page, $this, $no_default_boxes);
		
		$this->meta_boxes_more($settings_page, $no_default_boxes);
		
		return true;
	}

	public function meta_boxes_more($settings_page, $no_default_boxes) {
	}

	public function title_callback() {
		$title = sprintf('<span class="zuplus_red _bold">%1$s</span>', $this->plugin_name);
		return str_replace($this->plugin_name, $title, $this->title);		
	}

	public function form_callback() {
		return empty($this->form) ? '' : $this->form->form();		
	}

	public function body_callback() {
		return '';		
	}

	public function status_callback() {
		return '';		
	}

	public function include_admin_template() {
		$_prefix = $this->used_plugin_prefix();
		$_prefix_parent = $this->plugin_prefix(true);
		$_wrap_class = zu()->merge_classes(['wrap', $_prefix, $_prefix_parent]);
		if(empty($this->template)) {
			include_once(__ZUPLUS_ROOT__ . 'includes/zuplus-admin-page.php');
		} else {
			include_once($this->plugin_root() . $this->template);
		}
	}
	
	public function print_options($post) {
	}

	//
	// Wordpress Admin Page ------------------------------------------------------]
	//

	public function admin_menu() {	
		$this->hook_suffix = add_submenu_page(
			$this->hook, 
			$this->title, 
			$this->menu, 
			$this->permissions, 
			$this->slug, 
			[$this, 'render_admin_page']
		);

		if($this->hook_suffix == false) return false;

		add_action('load-'.$this->hook_suffix, [$this, 'admin_page_actions'], 9);
		add_action('admin_footer-'.$this->hook_suffix, [$this, 'admin_footer_scripts']);
	}

	public function admin_page_actions() {
		// * Actions to be taken prior to page loading. This is after headers have been set.
		// * call on load-$hook
		// * This calls the add_meta_boxes hooks, adds screen options and enqueues the postbox.js script.   

		do_action('add_meta_boxes_'.$this->hook_suffix, null);
		do_action('add_meta_boxes', $this->hook_suffix, null);

		// User can choose between 1 or 2 columns (default 2)
		add_screen_option('layout_columns', ['max' => 2, 'default' => 2]);

		// Enqueue WordPress' script for handling the meta boxes
		wp_enqueue_script('postbox'); 
	}

	public function admin_footer_scripts() {
	// 	Prints the jQuery script to initiliase the metaboxes
	// 	Called on admin_footer-
		print('<script> postboxes.add_postbox_toggles(pagenow);</script>');
	}

	protected function used_plugin_prefix() { 
		return $this->plugin_prefix(empty($this->template));
	}
	
	public function render_admin_page() {
		
		$prefix = $this->used_plugin_prefix();
		add_action($prefix.'_print_title', function() {
			echo $this->title_callback();
		});
		add_action($prefix.'_print_body', function() {
			$body_content = $this->body_callback();
			if(!empty($body_content)) printf('<div id="post-body-content">%1$s</div>', $body_content);		 
		});
		add_action($prefix.'_print_form', function() { 
			$this->form_callback(); 
		});
		add_filter($prefix.'_print_side_settings', [$this, 'plugin_status']);
		
		$this->include_admin_template();
	}

	//
	// AJAX & Tests --------------------------------------------------------------]
	//

	public function plugin_test() {
		return ['info'	=> sprintf('Plugin "%2$s" (%3$s) was tested on %1$s', date('H:i <b>d.m.y</b> ', $this->current_timestamp()), $this->plugin_name, $this->version)];
	}

	public function plugin_status() {
		return sprintf('
			<div class="plugin-logo">%3$s</div>
			<p><strong>%1$s</strong> - version %2$s</p>
			%4$s', 
			$this->plugin_name, 
			$this->version,
			zu()->insert_svg_from_file($this->plugin_root(), 'logo'),
			$this->status_callback()
		);
	}
	
	public function ajax_more($option_name, $ajax_value) {
		return [];
	}
	
	public function ajax_turn_option() {

		$option_name = (isset($_POST['option_name']) && !empty($_POST['option_name'])) ? $_POST['option_name'] : null;
		$ajax_value = (isset($_POST['ajax_value']) && !empty($_POST['ajax_value'])) ? $_POST['ajax_value'] : null;
		$result = [];

		if($option_name) {
			
			switch($option_name) {
				case 'zuplus_dismiss_error':
					$this->set_or_dismiss_error();
					break;

				case 'zuplus_clear_errors':
					$msg = $this->empty_errors();
					break;
					
				case 'zuplus_test':
					$msg = $this->plugin_test();
					break;
					
				default:
					$msg = $this->ajax_more($option_name, $ajax_value);
			}
			
			if(!empty($msg)) $result['result'] = $this->report_error($msg, true, $option_name);
			$result[$option_name] = 'ok';

			wp_send_json_success($result);
		}
	}

	//
	// Errors --------------------------------------------------------------------]
	//

	public function current_timestamp() { 
		return intval(current_time('timestamp')); 
	}

	public function report_error($msg, $ajax = false, $function = '', $class = '') {

		$errors = get_option($this->errors_id, []);
		
		$message = is_array($msg) ? (isset($msg['error']) ? $msg['error'] : '') :  $msg;
		
		if(!empty($message)) {
			$errors[] = [
				$message, 
				$this->current_timestamp(), 
				trim(sprintf('%s %s %s', strtoupper($function), (empty($class) ? '' : 'of class '), $class))
			];
			update_option($this->errors_id, $errors);
			$this->set_or_dismiss_error($msg);
		}
		
		return $this->get_notice($msg, $ajax);
	}

	public function get_notice($msg, $ajax = false) {
		
		// notice-error – error message displayed with a red border
		// notice-warning – warning message displayed with a yellow border
		// notice-success – success message displayed with a green border
		// notice-info – info message displayed with a blue border

		$msg_template = 'Something went wrong. Please look at <a href="#zuplus-errors-mb">ERRORS (%1$s)</a> section for more information.';
		$ajax_template = '<button type="button" class="notice-dismiss ajax-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>';
		$msg_text = is_array($msg) ? array_values($msg)[0] : sprintf($msg_template, count(get_option($this->errors_id, [])));
		$msg_type = is_array($msg) ? array_keys($msg)[0] : 'error'; 
		return sprintf('<div class="notice notice-%2$s is-dismissible" data-zuplus_prefix="%4$s"><p>%1$s</p>%3$s</div>', 
			$msg_text, 
			str_replace('ok', 'success', $msg_type),
			$ajax ? $ajax_template : '',
			$this->prefix
		);
	}

	public function show_errors() {
		if($this->options['error']) {
			echo $this->get_notice($this->options['error']);
		}
	}

	protected function set_or_dismiss_error($value = 0) {
		
		$this->get_options();
		$this->options['error'] = $value;
		$this->update_options();
	}

	protected function empty_errors() {
		$this->set_or_dismiss_error();
		update_option($this->errors_id, []);
		return ['info'	=> sprintf('All errors of "%2$s" have been successfully deleted on %1$s', date('H:i <b>d.m.y</b> ', $this->current_timestamp()), $this->plugin_name)];
	}
}

// Form Class -----------------------------------------------------------------]

class zuplus_Form {
	
	protected $root;
	protected $settings_page;
	protected $settings_id;
	protected $options_id;
	protected $errors_id;
	protected $options;
	protected $prefix;
	protected $parent_prefix;
	protected $items;

	protected $max_errors = 20;

	function __construct($root = null) {
		
		$this->root = empty($root) ? zuplus_get_my_dir() : untrailingslashit($root);
		$this->items = [];
	}

	public function name($options_name) {
		return sprintf('%1$s[%2$s]', $this->options_id, $options_name);
	}

	public function value($name, $default = false) {
		return isset($this->options[$name]) ? $this->options[$name] : $default;
	}

	public function add_value($name, $value) {
		$this->options[$name] = $value;
	}

	// Helpers --------------------------------------------------------------------]
	
	public function svg_from_file($name, $preserve_ratio = false) {
		return zu()->insert_svg_from_file($this->root, $name);	
	}
			
	public function form_item($name, $label, $option_desc = '', $option_type = 'checkbox', $option_array = null, $readonly = false) {
	
		$option_name = $this->name($name);
		$option_value = $this->value($name, $option_type == 'checkbox' ? false : '');
		
		$tr = '<tr valign="top"><td class="field_label%3$s"><laber for="">%1$s</label></td><td class="zu-field">%2$s</td></tr>';
		$option_check = '<input type="checkbox" name="%1$s" value="1" %2$s class="zu-input zu-checkbox %4$s" /><span class="field_desc desc-checkbox">%3$s</span>';
		$option_text = '<input type="text" name="%1$s" value="%2$s" class="zu-input zu-text %4$s" /><span class="field_desc desc-text">%3$s</span>';
		$option_text_readonly = '<input type="text" name="%1$s" value="%2$s" class="zu-input zu-text %4$s readonly" readonly /><span class="field_desc desc-text">%3$s</span>';
		$option_select = '<select name="%1$s" class="zu-input zu-select %4$s">%2$s</select><span class="field_desc desc-select">%3$s</span>';
		
		if($option_type == 'hidden') return sprintf('<input type="hidden" name="%1$s" value="%2$s" class="zu-input zu-hidden %3$s" />', $option_name, $option_value, $name);
		
		if($option_type == 'select') {
			$option_template = $option_select;
			$select_value = '';
			foreach ($option_array as $key => $value) {
				$select_value .= sprintf(
					'<option value="%1$s" %2$s>%3$s</option>',
					$key,
					($option_value == $key) ? 'selected' : '',
					$value
				);
			}
			$option_value = $select_value;
			
		} else {
			$option_template = ($option_type == 'checkbox') ? $option_check : ($readonly ? $option_text_readonly : $option_text);
			$option_value = ($option_type == 'checkbox') ? ($option_value ? 'checked' : '') : $option_value;
		}
		
		$label_class = ($option_type == 'text' && !empty($option_desc)) ? ' top' : '';
		$output = sprintf($tr, $label, sprintf($option_template, $option_name, $option_value, $option_desc, $name), $label_class);
		$this->items[] = $output;
		return $output;
	}
	
	public function checkbox($name, $label, $option_desc = '') { 
		return $this->form_item($name, $label, $option_desc); 
	}
	
	public function text($name, $label, $option_desc = '', $readonly = false) { 
		return $this->form_item($name, $label, $option_desc, 'text', null, $readonly); 
	}
	
	public function select($name, $label, $option_array, $option_desc = '') { 
		return $this->form_item($name, $label, $option_desc, 'select', $option_array); 
	}
	
	public function hidden($name) { 
		return $this->form_item($name, '', '', 'hidden'); 
	}
	
	public function button($label, $icon, $color = 'blue', $in_table = true, $class = '') {
	
		$form_fields = $this->options_id;
		$tr = '<tr valign="top"><td class="field_label"></td><td class="zu-field">%1$s</td></tr>';
		$basic_classes = ['button', 'button-primary', 'zu-dashicons', 'zu-button'];
		$output =	sprintf(
			'<button type="submit"%7$s name="%6$s_%1$s" class="%5$s zu-button-%4$s" value="%2$s">
				<span class="dashicons dashicons-%3$s"></span> %2$s
			</button>',
					$form_fields,
					$label,
					$icon,
					$color,
					zu()->merge_classes(array_merge($basic_classes, [$class, $in_table ? 'zu-button-right' : ''])),
					strtolower(str_replace(' ', '_', trim($label))),
					empty($form_fields) ? '' : sprintf(' form="%1$s-form"', $form_fields)
		);
	
		$output = $in_table ? sprintf($tr, $output) : $output;
		return $output;
	}
	
	public function button_side($label, $icon, $color = 'blue') {
		return $this->button($label, $icon, $color, false, 'zu-side-button');
	}

	public function button_link_with_help($button_option, $label, $icon, $color = 'blue', $text = '') {
		return $this->button_link($button_option, $label, $icon, $color, true, true, $text);
	}
	
	public function button_link($button_option, $label, $icon, $color = 'blue', $in_table = false, $side_button = true, $text = '') {
	
		$tr = '<tr valign="top"%3$s><td class="field_label"><span>%2$s</span></td><td class="zu-field">%1$s</td></tr>';
		$basic_classes = ['button', 'button-primary', 'zu-dashicons', 'zu-button', 'zuplus_ajax_option'];
		$output =	sprintf(
			'<a href="%1$s" class="%5$s zu-button-%4$s" data-zuplus_option="%6$s" data-zuplus_prefix="%7$s">
				<span class="dashicons dashicons-%3$s"></span> 
				<span class="zu-link-text">%2$s</span>
			</a>',
			add_query_arg($button_option, 1, $this->settings_page),
			$label,
			$icon,
			$color,
			zu()->merge_classes(array_merge($basic_classes, [$in_table ? 'zu-button-right' : '', $side_button ? 'zu-side-button' : ''])),
			$button_option,
			$this->prefix
		);
		
		$output = $in_table ? sprintf($tr, $output, $text, empty($text) ? '' : ' class="zu-button-with-help"') : $output;
		if($in_table) $this->items[] = $output;
		return $output;
	}
	
	public function fields($form_desc = '', $ajax_rel = '', $spinner = false) {
		
		$form_items = is_array($this->items) ? implode('', $this->items) : $this->items;
		$this->items = [];
		return sprintf(
				'<div class="form_desc">%1$s%4$s</div>
				<table class="form-table form-general"%3$s>%2$s</table>',
				$form_desc,
				$form_items,
				empty($ajax_rel) ? '' : sprintf(' data-ajaxrel="%1$s"', $ajax_rel),
				empty($spinner) ? '' : '<div class="zu-ajax-progress"></div><div class="zu-spinner"></div>'
			);
	}
	
	public function form() {
	
		printf('<form id="%1$s-form" method="post" action="options.php">', $this->options_id);
		settings_fields($this->options_id);
		wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false);
		wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false);
	}
	
	// Meta Box Configuration -----------------------------------------------------]

	public function add_meta_box($name, $title, $callback, $context = 'normal', $priority = 'high') {
		
		if(empty($this->settings_id)) return;
		
		add_meta_box(
			sprintf('%1$s-%2$s-mb', $this->prefix, $name), 
			$title, 
			$callback, 
			$this->settings_id, 
			$context,
			$priority
		);
	}
	
	public function add_admin_meta_boxes($settings_page, $admin, $no_default_boxes = false) {
		
		if(is_null($admin) || $admin->hook_suffix == false) return;
		
		$this->settings_page = $settings_page;
		$this->settings_id = $admin->hook_suffix;
		$this->options_id = $admin->options_id;
		$this->errors_id = $admin->errors_id;
		$this->options = $admin->get_options();
		
		$this->prefix = $admin->plugin_prefix();
		$this->parent_prefix = $admin->plugin_prefix(true);
		$this->args = [$this->options_id, $this->options, $this->errors_id];
		 
		if($no_default_boxes) return;
	
		$this->prefix = $this->parent_prefix;

		// Normal Boxes --------------------------------------------------------------]
		
		$this->add_meta_box('options', __('Options', 'zu-plugin'), [$admin, 'print_options']);
		
		// Advanced & Side Boxes -----------------------------------------------------]
		
		$errors_title = sprintf(__('Errors (last %s messagges)', 'zu-plugin'), $this->max_errors);
		$this->add_meta_box('errors', $errors_title, [$this, 'print_errors'], 'advanced', 'low');

		$this->add_meta_box('status', __('Plugin Status', 'zu-plugin'), [$this, 'print_status'], 'side');

		$this->add_meta_box('debug', __('Debug Actions', 'zu-plugin'), [$this, 'print_debug'], 'side', 'low');

		$this->prefix = $admin->plugin_prefix();
	}
	
	// Advanced Blocks ------------------------------------------------------------]
	
	public function print_save_mobile() {
		return sprintf('<div class="mobile_only">%1$s</div>', $this->button(__('Save Options', 'zu-plugin'), 'admin-tools', 'blue'));
	}
	
	public function print_errors($post) {
	
		$errors = get_option($this->errors_id);
		$lines = '<div class="form_desc">There\'re no errors.</div>';
		
		if(!empty($errors)) {
			
			$lines = sprintf(
				'<table class="table-list" cellspacing="0" cellpadding="0" border="0">
					<thead>
						<tr>
							<th class="index"></th>
							<th class="status"></th>
							<th class="function">Function</th>
							<th class="name">Error Description</th>
							<th class="time">Time</th>
						</tr>
					</thead>
					<tbody>'
			);
								
			$err_count = count($errors);							
			foreach(array_reverse($errors) as $key => $error) {
				$lines .= sprintf(
					'<tr class="view">
						<td class="index">%3$s</td>
						<td class="status"><div class="status-circle disabled"></div></td>
						<td class="function">%4$s</td>
						<td class="name">%2$s</td>
						<td class="time">%1$s</td>
					</tr>',
					date('H:i <b>d.m.y</b> ', intval($error[1])),
					$error[0],
					$err_count - $key,
					empty($error[2]) ? 'unknown' : $error[2]
				);
				if($key > ($this->max_errors-1)) break;
			}
			$lines .= '</tbody></table>';
		} 
	
		echo $lines;
	}
		
	// Side Blocks ----------------------------------------------------------------]
	
	public function print_status($post) {
		
		echo apply_filters('zuplus_print_side_settings', '');	
		echo $this->button_side(__('Save Options', 'zu-plugin'), 'admin-tools');
	}
	
	public function print_debug($post) {
		
		$output = apply_filters('zuplus_print_debug_values', '');	
		printf('<div class="form_desc">%1$s</div>', empty($output) ? '' : $output);

		echo apply_filters($this->prefix.'_print_debug_buttons', '');	
		
		echo $this->button_link('zuplus_clear_errors', __('Clear Errors', 'zu-plugin'), 'trash', 'red');
		echo $this->button_link('zuplus_test', __('Test', 'zu-plugin'), 'dashboard', 'green');
	}
}

/*
	$items .= zuplus_hidden($options, 'restart', 1);

	$items .= zuplus_text($options, 'lang', 'File to watch on Dropbox', 'File to check on Dropbox. <strong>Path should start with "/".</strong>');
	
	$items .= zuplus_select($options, 'interval', 'Check your Dropbox folder:', [	
		300 => 'Every five minutes', // 300
		600 => 'Every ten minutes', 
		1800 => 'Every thirty minutes', 
		3600 => 'Every hour', 
		7200 => 'Every two hours']
	);

	$users = get_users();
	$select_users = array();
	foreach($users as $user) $select_users[$user->ID] = $user->user_login;
	$items .= zuplus_select($options, 'author', 'Default Author', $select_users);

*/

	