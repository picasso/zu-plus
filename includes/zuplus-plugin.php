<?php
// Includes all traits --------------------------------------------------------]

include_once('traits/ajax.php');
include_once('traits/duplicate-menu.php');
// include_once('traits/cache.php');

class zu_Plus extends zukit_Plugin  {

	// Plugin addons
	private $dbug = null;
	private $cnotice = null;
	private $dupost = null;

	// Ajax, Duplicate Menu and Cache and
	use zu_PlusAjax, zu_PlusDuplicateMenu; //, zu_PlusCache;

	protected function config() {
		return  [
			'prefix'			=> 'zuplus',
			'zukit'				=> true,

			'translations'		=> [
				'path'				=> 'lang',
				'domain'			=> 'zu-plus',
			],

			'appearance'		=> [
				'colors'			=> [
					'backdrop'	=> '#fffbf2',
					'header'	=> '#ffdf99',
					'title'		=> '#705824',
				],
			],

			'options'			=> [
				'debug_mode'		=> false,
				'remove_autosave'	=> false,
				'cookie_notice'		=> false,
				'dup_page'			=> false,
				'disable_cached'	=> false,
				'disable_admenu'	=> false,
			],

			'admin'				=> [
				'menu'          	=>	'Zu+',
			],

			// add menu data for the Settings Page
	        'settings_script'	=> [
	            'data'  => [
	                'menus'	=> $this->get_menus(),
	            ],
	        ],
		];
	}

	protected function construct_more() {
		// we need to register 'Debug Addon' earlier, otherwise its methods will not be available until 'init'
		if($this->is_option('debug_mode')) {
			$this->dbug = $this->register_addon(new zu_PlusDebug());
			// zu()->set_debug_cache($this->check_option('debug_cache'));
		}
		// reorder the plugin load list
		$this->load_first();
		// output in log the current order of items in Admin menus and submenus
		if($this->is_option('debug_mode') && $this->is_option('zuplus_debug_options.debug_menus')) {
			$this->toggle_menu_debug(true);
		}
		// output in log the current order of all acivated plugins
		if($this->is_option('debug_mode') && $this->is_option('zuplus_debug_options.debug_plugins')) {
			add_action('admin_init', function() {
				$plugins = get_option('active_plugins');
				zu_logc('*Activated Plugins Order', $plugins);
			});
		}
		// disable all changes in Admin menus and submenus
		if($this->is_option('disable_admenu')) {
			$this->toggle_menu_disable(true);
		}
		// disable Wordpress 'autosave' and 'backup' in Block Editor
		if($this->is_option('remove_autosave')) {
			add_action('enqueue_block_editor_assets', function() {
				$this->admin_enqueue_script('zuplus-remove-backups', [
					'deps' => ['wp-editor', zukit_Blocks::$zukit_handle]
				]);
			});
			// 'write_your_story' filter will be called before the moment when 'autosave' will be added to '$editor_settings'
			// this is a convenient time to intervene and remove 'autosave' (if it was found)
			add_filter('write_your_story', function($story, $post) {
				$autosave = wp_get_post_autosave($post->ID);
				if($autosave) {
					wp_delete_post_revision($autosave->ID);
				}
				return $story;
			}, 10, 2);
		}
	}

	protected function extend_info() {
		return array_merge([
			// 'memory'		=> [
			// 		'label'		=> __('Cached Shortcodes', 'zu-plus'),
			// 		'value'		=> $this->get_cached_memory($stats),
			// 		'depends' 	=> ['folders', 'disable_cache'],
			// ],
		], $this->dbug ? $this->dbug->debug_info() :
		// we use a fake element that will never be displayed since the 'value' is null
		// but will cause the hook to fire when the value of the 'debug_mode' option changes
		['fake' => ['value' => null, 'depends' => 'debug_mode']]);
	}

	protected function extend_metadata($metadata) {
		$metadata['description'] = str_replace('Zukit', '**Zukit**', $metadata['description']);
		return $metadata;
	}

	protected function extend_actions() {
		return [
			[
				'label'				=> __('Reset Cached Shortcodes', 'zu-plus'),
				'value'				=> 'zuplus_reset_cached',
				'icon'				=> 'backup',
				'color'				=> 'magenta',
				'help'				=> __('Clear all cached data referenced to shortcodes (**gallery** and **select**). '.
									'Needs if something went wrong.', 'zu-plus'),
				'depends'			=> '!disable_cached',
			],
			// [
			// 	'label'				=> __('Revoke Cookie', 'zu-plus'),
			// 	'value'				=> 'zuplus_revoke_cookie',
			// 	'icon'				=> 'food',
			// 	'color'				=> 'gold',
			// 	'help'				=> __('Set "expires" value on cookie for 1970 which leads to cookie **deleting**. '.
			// 								'*Needs for debugging only*.', 'zu-plus'),
			// 	// the button will be visible only if this option is 'true'
			// 	'depends'			=> 'cookie_notice',
			// ],
			[
				// an indication that we will use the slot for 'MoreActions'
				'hasMoreActions'	=> true,
			],
		];
	}

	// Actions & Add-ons ------------------------------------------------------]

	public function init() {
		// Cookie Notice Addon
		if($this->is_option('cookie_notice')) {
			// $this->cnotice = $this->register_addon(new zu_PlusCookieNotice());
		}

		// Some internal 'inits' ----------------------------------------------]

		// $this->init_??();

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
	}

	public function admin_init() {
		// Duplicate Page Addon
		if($this->is_option('dup_page')) {
			$this->dupost = $this->register_addon(new zu_PlusDuplicatePage());
		}
	}

	// Debug logging helpers --------------------------------------------------]

	public function is_debug() {
		return empty($this->dbug) ? false : true;
	}

	protected function dump_log($log) {
		return $this->is_debug() ? $this->dbug->dump($log) : '';
	}

	protected function file_log($log) {
		if($this->is_debug()) $this->dbug->debug_log($log);
		// if 'debug mode' is not activated, then all such calls should be muted
	}

	public function dlog($args, $called_class = null) {
		if($this->is_debug()) $this->dbug->expanded_log($args, $called_class);
		// if 'debug mode' is not activated, then all such calls should be muted
	}

	// logging with context
	public function dlogc($context, $args, $called_class = null) {
		if($this->is_debug()) $this->dbug->expanded_log_with_context($context, $args, $called_class);
		// if 'debug mode' is not activated, then all such calls should be muted
	}

	// log location management
	public function dlog_location($path, $priority = 1) {
		if($this->is_debug()) {
			if(is_null($path)) return $this->dbug->log_location();
			else return $this->dbug->change_log_location($path, $priority);
		}
		return null;
	}

	public function dlog_clean() {
		return $this->is_debug() ? $this->dbug->clear_log() : null;
	}

	// Custom menu position ---------------------------------------------------]

	protected function custom_admin_menu() {
		return [
			'reorder'	=>	[
				[
					'menu'		=> 'edit.php',
					'after'		=> 'upload.php',
				],
				[
					'menu'		=> 'genesis',
					'after'		=> 'options-general.php',
				],
			],
		];
	}

	protected function custom_admin_submenu() {
		return [
			'reorder'	=>	[
				[
					'menu'		=> 'zuplus-settings',
					'after'		=> 'zutranslate-settings',
				],
				[
					'onfail'	=> true,
					'menu'		=> 'zuplus-settings',
					'after'		=> 'zumedia-settings',
				],
				[
					'onfail'	=> true,
					'menu'		=> 'zuplus-settings',
					'after'		=> 'options-privacy.php',
				],
			],
			'separator'	=>	[
					'before'	=> 'zuplus-settings',
					'after'		=> 'zuplus-settings',
			],
		];
	}

	// Script enqueue ---------------------------------------------------------]

	protected function should_load_css($is_frontend, $hook) {
		// here we load only 'zuplus' for the Settings Page
		// 'zuplus-admin' which is needed for all pages is loaded via 'enqueue_more'
		// return false;
		return $is_frontend ? false : $this->ends_with_slug($hook);
	}

	protected function should_load_js($is_frontend, $hook) {
		return $is_frontend ? false : $this->ends_with_slug($hook);
	}

	protected function enqueue_more($is_frontend, $hook) {
		$frontend_allowed = !empty($this->dbug) && $this->dbug->is('debug_frontend');
		// $autosave_allowed = $this->is_option('remove_autosave') && in_array($hook, ['post.php', 'post-new.php']);

		if(!$is_frontend || $frontend_allowed) {
			$this->admin_enqueue_style('zuplus-admin');
		}

		// if(!$is_frontend && $autosave_allowed) {
		// 	$this->admin_enqueue_script('rm-autosave', ['deps'	=> 'jquery']);
		// }
	}

	// load Zu Plus first -----------------------------------------------------]

	// When activated this plugin will reorder the plugin load list
	private function load_first() {
		add_action('activated_plugin', function() {
			$zuplus_key = str_replace('/plugins/', '', $this->data['File']);
			$plugins = get_option('active_plugins');
			// _zu_log($plugins, $zuplus_key);
			if($plugins) {
				$index = array_search($zuplus_key, $plugins);
				if($index) {
					array_splice($plugins, $index, 1);
					// if the first position occupies a 'query-monitor',
					// then we leave it there and occupy the second position
					$position = strpos($plugins[0], 'query-monitor') !== false ? 1 : 0;
					// if the first/second position occupies a 'zu-debug',
					// then we leave it there and occupy the second/third position
					$position += strpos($plugins[$position], 'zu-debug') !== false ? 1 : 0;
					array_splice($plugins, $position, 0, $zuplus_key);
					// _zu_log('after shift', $plugins, $position);
					update_option('active_plugins', $plugins);
				}
			}
		});
	}
}

// Entry Point ----------------------------------------------------------------]

function zuplus($file = null) {
	return zu_Plus::instance($file);
}

// Additional Classes & Functions ---------------------------------------------]

require_once('debug/zuplus-debug.php');
require_once('addons/duplicate-page.php');
// require_once('addons/cookie-notice.php');
