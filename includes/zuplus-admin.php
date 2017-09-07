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
	
	public function ajax_nonce($create = false) { 
		$ajax_nonce = $this->prefix.'_ajax_nonce';
		return $create ? wp_create_nonce($ajax_nonce) : $ajax_nonce; 
	}

	public function admin_enqueue() {		// admin

		$data = [
			'ajaxurl'                => admin_url('admin-ajax.php'),
			'admin_nonce'     	=> $this->ajax_nonce(true),
			'screen_id'				=> $this->hook_suffix,
		];
		
		wp_enqueue_style($this->prefix.'-style', plugins_url('css/'.$this->prefix.'-admin.css', $this->plugin_file), [], $this->version);		
		wp_enqueue_script($this->prefix.'-script', plugins_url('js/'.$this->prefix.'-admin.min.js', $this->plugin_file), ['jquery'], $this->version, true);
		wp_localize_script($this->prefix.'-script', $this->prefix.'_custom', $data);
	}
	
	public function admin_settings_link($links) {
		$settings_link = sprintf('<a href="%1$s%2$s?page=%3$s">%4$s</a>', get_admin_url(), $this->hook, $this->slug, __('Settings', 'textdomain'));
		array_unshift($links, $settings_link);
		return $links;
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

		$this->form = new zuplus_Form($this->plugin_root());
		$this->form->add_admin_meta_boxes($settings_page, $this, $no_default_boxes);
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
		$_prefix = $this->plugin_prefix(false);
		$_prefix_parent = $this->plugin_prefix(true);
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

	public function render_admin_page() {
		
		$prefix = $this->plugin_prefix(empty($this->template));
		
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
		return ['info'	=> sprintf('Plugin tested on %1$s', date('H:i <b>d.m.y</b> ', $this->current_timestamp()))];
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
	
	public function ajax_more($option_name) {
		return [];
	}
	
	public function ajax_turn_option() {

		$option_name = (isset($_POST['option_name']) && !empty($_POST['option_name'])) ? $_POST['option_name'] : null;
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
					$msg = $this->ajax_more($option_name);
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
		$msg_text = is_array($msg) ? array_values($msg)[0] : sprintf($msg_template, $msg);
		$msg_type = is_array($msg) ? array_keys($msg)[0] : 'error'; 
		return sprintf('<div class="notice notice-%2$s is-dismissible"><p>%1$s</p>%3$s</div>', 
			$msg_text, 
			str_replace('ok', 'success', $msg_type),
			$ajax ? $ajax_template : ''
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
		return ['info'	=> sprintf('All errors have been successfully deleted on %1$s', date('H:i <b>d.m.y</b> ', $this->current_timestamp()))];
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
		
		$tr = '<tr valign="top"><td class="field_label"><laber for="">%1$s</label></td><td class="zu-field">%2$s</td></tr>';
		$option_check = '<input type="checkbox" name="%1$s" value="1" %2$s class="zu-input zu-checkbox %4$s" /><span class="field_desc">%3$s</span>';
		$option_text = '<input type="text" name="%1$s" value="%2$s" class="zu-input zu-text %4$s" /><span class="field_desc">%3$s</span>';
		$option_text_readonly = '<input type="text" name="%1$s" value="%2$s" class="zu-input zu-text %4$s readonly" readonly /><span class="field_desc">%3$s</span>';
		$option_select = '<select name="%1$s" class="zu-input zu-select %4$s">%2$s</select><span class="field_desc">%3$s</span>';
		
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
		
		$output = sprintf($tr, $label, sprintf($option_template, $option_name, $option_value, $option_desc, $name));
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
	
	public function button_link($button_option, $label, $icon, $color = 'blue', $in_table = false, $side_button = true) {
	
		$tr = '<tr valign="top"><td class="field_label"></td><td class="zu-field">%1$s</td></tr>';
		$basic_classes = ['button', 'button-primary', 'zu-dashicons', 'zu-button', 'zuplus_ajax_option'];
		$output =	sprintf(
			'<a href="%1$s" class="%5$s zu-button-%4$s" data-zuplus_option="%6$s">
				<span class="dashicons dashicons-%3$s"></span> 
				<span class="zu-link-text">%2$s</span>
			</a>',
			add_query_arg($button_option, 1, $this->settings_page),
			$label,
			$icon,
			$color,
			zu()->merge_classes(array_merge($basic_classes, [$in_table ? 'zu-button-right' : '', $side_button ? 'zu-side-button' : ''])),
			$button_option
		);
		
		$output = $in_table ? sprintf($tr, $output) : $output;
		if($in_table) $this->items[] = $output;
		return $output;
	}
	
	public function fields($form_desc = '', $ajax_rel = '') {
		
		$form_items = is_array($this->items) ? implode('', $this->items) : $this->items;
		$this->items = [];
		return sprintf(
				'<div class="form_desc">%1$s</div>
				<table class="form-table form-general"%3$s>%2$s</table>',
				$form_desc,
				$form_items,
				empty($ajax_rel) ? '' : sprintf(' data-ajaxrel="%1$s"', $ajax_rel)
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
		
		echo apply_filters($this->prefix.'_print_side_settings', '');	
		echo $this->button_side(__('Save Options', 'zu-plugin'), 'admin-tools');
	}
	
	public function print_debug($post) {
		
		$output = apply_filters($this->prefix.'_print_debug_values', '');	
		printf('<div class="form_desc">%1$s</div>', empty($output) ? '' : $output);

		echo apply_filters($this->prefix.'_print_debug_buttons', '');	
		
		echo $this->button_link($this->parent_prefix.'_clear_errors', __('Clear Errors', 'zu-plugin'), 'trash', 'red');
		echo $this->button_link($this->parent_prefix.'_test', __('Test', 'zu-plugin'), 'dashboard', 'green');
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

	