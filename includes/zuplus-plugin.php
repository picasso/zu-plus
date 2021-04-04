<?php
// Includes all traits --------------------------------------------------------]

include_once('traits/ajax.php');
// include_once('traits/cache.php');
include_once('traits/duplicate-menu.php');

class zu_Plus extends zukit_Plugin  {

	// Plugin addons
	private $dbug = null;
	private $cnotice = null;
	private $duppage = null;

	// Ajax, Duplicate Menu, Cache and ?
	use zu_PlusAjax, zu_PlusDuplicateMenu; //, zu_PlusCache;

	protected function config() {
		return  [
			'prefix'			=> 'zuplus',
			'zukit'				=> true,

			'translations'		=> [
				'path'				=> 'lang',
				'domain'			=> 'zu-plus',
			],

			// 'appearance'		=> [
			// 	'colors'			=> [
			// 		'backdrop'			=> '#f0f4fd',
			// 		'header'			=> '#b0c5fd',
			// 		'title'				=> '#283965',
			// 	],
			// ],

			'options'			=> [
				'debug_mode'		=> false,
				'remove_autosave'	=> false,
				'cookie_notice'		=> false,
				'dup_page'			=> false,
				'disable_cached'	=> false,
			],

			'admin'				=> [
				'menu'          	=>	'Zu+',
			],
		];
	}

	protected function construct_more() {
		$this->options();
		// we need to register Debug Addon earlier, otherwise its methods will not be available until 'init'
		if($this->is_option('debug_mode')) {
			$this->dbug = $this->register_addon(new zu_PlusDebug());
			// zu()->set_debug_cache($this->check_option('debug_cache'));
		}
	}

	protected function extend_info() {
		return array_merge([
			// 'memory'		=> [
			// 		'label'		=> __('Cached Shortcodes', 'zu-plus'),
			// 		'value'		=> $this->get_cached_memory($stats),
			// 		'depends' 	=> ['folders', 'disable_cache'],
			// ],
		], $this->dbug ? $this->dbug->debug_info() : []);
	}

	protected function extend_actions() {
		return [
			[
				'label'		=> __('Reset All Cached Shortcodes', 'zu-plus'),
				'value'		=> 'zuplus_reset_cached',
				'icon'		=> 'dismiss',
				'color'		=> 'magenta',
				'help'		=> __('Clear all cached data referenced to shortcodes (**gallery** and **select**). '.
									'Needs if something went wrong.', 'zu-plus'),
				'depends'	=> '!disable_cached',
			],
			[
				'label'		=> __('Revoke Cookie', 'zu-plus'),
				'value'		=> 'zuplus_revoke_cookie',
				'icon'		=> 'hidden',
				'color'		=> 'gold',
				'help'		=> __('Set "expires" value on cookie for 1970 which leads to cookie *deleting*. '.
									'Needs for debugging only.', 'zu-plus'),
				// the button will be visible only if this option is 'true'
				'depends'	=> 'cookie_notice',
				// ZU_CookieNotice::cookie_name()
			],
		];
	}

	protected function extend_debug_options() {
		return [];
		// return [
		// 	'show_id'	=> [
		// 		'label'		=> __('Display Attachment Id', 'zu-media'),
		// 		'value'		=> false,
		// 	],
		// ];
	}

	protected function extend_debug_actions() {
		return [];
		// return $this->folders ? [
		// 	[
		// 		'label'		=> __('Fix Orphaned Attachments', 'zu-media'),
		// 		'value'		=> 'zumedia_fix_orphaned',
		// 		'icon'		=> 'hammer',
		// 		'color'		=> 'blue',
		// 	],
		// 	[
		// 		'label'		=> __('Check Existed Terms', 'zu-media'),
		// 		'value'		=> 'zumedia_check_terms',
		// 		'icon'		=> 'warning',
		// 		'color'		=> 'gold',
		// 	],
		// ] : [];
	}

	// Actions & Add-ons ------------------------------------------------------]

	public function init() {

		// Cookie Notice Addon
		if($this->is_option('cookie_notice')) {
			// $this->cnotice = $this->register_addon(new zu_PlusCookieNotice());
		}

		// Some internal 'inits' ----------------------------------------------]

		// $this->init_cachekeys();
		// $this->init_baseurl();

		// не совсем понятно зачем это? скорее чтобы из плагина управлять опциями темы...
		// устарелое решение, но нужно разобраться прежде чем удалять

		// add_filter('zu_update_defaults', function($zu_defaults, $key = null) {
		//
		// 	if(empty($key)) {
		// 		$options = $this->options();
		// 		zu()->set_option($zu_defaults, 'refresh_mode', $this->check_option('debug_mode'));
		// 		zu()->set_option($zu_defaults, 'remove_autosave', $this->check_option('remove_autosave'));
		// 		zu()->set_option($zu_defaults, 'ajax_log', $this->check_option('ajax_log'));
		// 		zu()->set_option($zu_defaults, 'debug_mode', $this->check_option('debug_js'));
		//
		// 		if($this->check_option('cookie_notice')) {
		// 			zu()->set_option($zu_defaults, 'cookie_notice', true);
		// 			zu()->set_option($zu_defaults, 'cookie_options', $this->option_value('cookie_options', []));
		// 		}
		// 	}
		//
		// 	return $zu_defaults;
		//
		// }, 10, 2);

		$time = time();
		$data = $this->options;
		// d($time, $data);
		//
		// $ktest = new KintTest();
		zu_logc('*test message', $time, $data);

		// $this->log($this->uri, $this->version, $this->prefix);
		// $this->logc('Zu+ Options', $this->options);
		zu_log($this->uri, $this->version, $this->prefix);
		zu_logc('!Zu+ Options', $this->options);
	}

	public function admin_init() {

		// Duplicate Page Addon
		if($this->is_option('dup_page')) {
			// $this->duppage = $this->register_addon(new zu_PlusDuplicatePage());
		}
	}

	// Debug logging helpers --------------------------------------------------]

	public function is_debug() {
		return empty($this->dbug) ? false : true;
	}
	// public function with_kint() {
	// 	return empty($this->dbug) ? false : $this->dbug->is('use_kint');
	// }

	protected function file_log($log) {
		if($this->is_debug()) $this->dbug->debug_log($log);
		else parent::file_log($log);
	}

	public function dlog($args, $called_class = null) {
		if($this->is_debug()) $this->dbug->expanded_log($args, $called_class);
		else parent::log_with(0, null, ...$args);
	}

	// logging with context
	public function dlogc($context, $args, $called_class = null) {
		if($this->is_debug()) $this->dbug->expanded_log_with_context($context, $args, $called_class);
		else parent::log_with(0, $context, ...$args);
	}

	private function is_debug_frontend() {
		return empty($this->dbug) ? false : $this->dbug->is('debug_frontend');
	}

	// Custom menu position ---------------------------------------------------]

	protected function custom_admin_menu() {
		return [
			'reorder'	=>	[
				[
					'menu'			=> 	'upload.php',
					'before_index'	=>	'upload.php',
				],
				[
					'menu'			=> 	'edit.php',
					'after_index'	=>	'upload.php',
				],
				[
					'menu'			=> 	'genesis',
					'after_index'	=>	'options-general.php',
				],
			],
		];
	}

	protected function custom_admin_submenu() {
		return [
			'reorder'	=>	[
				[
					'menu'				=> 	'zuplus-settings',
					'new_index'			=>	$this->from_split_index(12),
				],
				[
					'menu'				=> 	'options-permalink.php',
					'after_index'		=>	'options-discussion.php',
				],
			],
			'rename'	=>	[
				// [
				// 	'menu'				=> 	'options-privacy.php',
				// 	'new_name'			=>	'Privacy',
				// ],
				[
					'menu'				=> 	'watermark-options',
					'new_name'			=>	'Watermark',
				],
				[
					'menu'				=> 	'ewww-image-optimizer-cloud/ewww-image-optimizer-cloud.php',
					'new_name'			=>	'EWWW Optimiser',
				],
			],
			'remove'	=>	[
				[
					'menu'				=>	'bbq_settings',
				],
				[
					'menu'				=>	'itsec-go-pro',
					'parent'			=>	'itsec',
				],
			],
			'separator'	=>	[
				[
					'before_index'		=>	'zuplus-settings',
				],
			],
		];
	}

	// Script enqueue ---------------------------------------------------------]

	protected function should_load_css($is_frontend, $hook) {
		return $is_frontend ? $this->is_debug_frontend() : $this->ends_with_slug($hook);
	}

	protected function should_load_js($is_frontend, $hook) {
		return $is_frontend ? $this->is_debug_frontend() : $this->ends_with_slug($hook);
	}

	// наверное устарело!
	// public function init() {
	// 	if($this->should_load_frontend()) {
	// 		$this->ready();
	// 	}
	// }

	// protected function enqueue_more($is_frontend, $hook) {
	// 	if($this->is_debug()) $this->dbug->enqueue_kint_css($this->plugin);
	// }

	// protected function admin_enqueue_more() {
	// 	if($this->is_debug()) {
	// 		$this->plugin->dbug->enqueue_kint_css($this->plugin);
	// 	}
	// }
	//
	// protected function enqueue_more() {
	// 	$this->dbug->enqueue_kint_css($this);
	// 	$this->enqueue_script($this->prefix.'-admin', [true]);
	// }
	// protected function admin_extend_localize_data() {
	// 	return ['remove_autosave' => $this->check_option('remove_autosave')];
	// }
}

// Entry Point ----------------------------------------------------------------]

function zuplus($file = null) {
	return zu_Plus::instance($file);
}

// Additional Classes & Functions ---------------------------------------------]

require_once('debug/zuplus-debug.php');
// require_once('addons/cookie-notice.php');
// require_once('addons/duplicate-page.php');
