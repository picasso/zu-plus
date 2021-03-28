<?php
// Includes all traits --------------------------------------------------------]

// include_once('traits/ajax.php');
// include_once('traits/ratio.php');
// include_once('traits/attachments.php');
// include_once('traits/location.php');

class ZU_Admin extends zuplus_Admin {

	public function meta_boxes_more($settings_page, $no_default_boxes) {

		// Custom Boxes -------------------------------------------------------------]

		$this->form->add_meta_box('all_actions', __('Theme Actions', 'zu-plugin'), [$this, 'print_all_actions']);

		if($this->check_option('debug_mode')) {

			$this->form->add_meta_box('debug_options', __('Debug Options', 'zu-plugin'), [$this->plugin->dbug, 'print_debug_options'], 'advanced', 'high');
			$this->form->add_meta_box('debug_log_location', __('Actual Log Location', 'zu-plugin'), [$this->plugin->dbug, 'print_log_location'], 'advanced', 'low');

			// Add button to clear logs ------------------------------------------------]

			add_filter($this->prefix.'_print_debug_buttons', function() {
				return $this->form->button_link('zuplus_clear_log', __('Clear Debug Log', 'zu-plugin'), 'trash', 'blue');
			});
		}

		if($this->check_option('dup_page')) {
			$this->form->add_meta_box('duplicate_page', __('Duplicate Page', 'zu-plugin'), [$this->duppage, 'print_duplicate_page'], 'advanced');
			$this->form->add_meta_box('duplicate_menu', __('Duplicate Menu', 'zu-plugin'), [$this, 'print_duplicate_menu']);
		}

		if($this->check_option('cookie_notice')) {
			$this->form->add_meta_box('cookie_notice', __('Cookie Notice', 'zu-plugin'), [$this->plugin->cnotice, 'print_cookie_metabox'], 'advanced');
		}
	}

	public function status_callback() {

		return $this->is_debug() ? $this->plugin->dbug->print_stats() : '';
	}

	public function print_options($post) {

		$this->form->checkbox('debug_mode', 'Activate Debug Mode', 'All debug functions like <span>_dbug_*()</span> will be activated. Otherwise all calls will be muted.');
		$this->form->checkbox('remove_autosave', 'Remove Autosave Notices', 'Removes Wordpress <span>autosave</span> and <span>backup</span> notices which could be very annoying. You should understand what you are doing.');
		$this->form->checkbox('dup_page', 'Activate Duplicate Page & Menu', 'Allows duplicate Menu, Posts, Pages and Custom Posts using single click.');
		$this->form->checkbox('cookie_notice', 'Activate Cookie Notice', 'Allows you to inform users that the site uses cookies and to comply with the EU GDPR regulations.');

		echo $this->form->fields('The plugin encompasses ZU framework functionality.');
		echo $this->form->print_save_mobile();
	}

	// public function print_all_actions($post) {
	//
	// 	$this->form->button_link_with_help('zuplus_reset_cached',
	// 		__('Reset All Cached Shortcodes', 'zu-plugin'),
	// 		'dismiss',
	// 		'magenta',
	// 		'Clear all cached data referenced to shortcodes (<strong>gallery</strong> and <strong>select</strong>). Needs if something went wrong.'
	// 	);
	//
	// 	$this->form->button_link_with_help('zuplus_revoke_cookie',
	// 		__('Revoke Cookie', 'zu-plugin'),
	// 		'hidden',
	// 		'gold',
	// 		'Set "expires" value on cookie for 1970 which leads to cookie <strong>deleting</strong>. Needs for debugging only.',
	// 		ZU_CookieNotice::cookie_name()
	// 	);
	//
	// 	echo $this->form->fields('Actions available for ZU Theme.', 'zuplus_reset_cached', true); // second argument -> data-ajaxrel : used in js to serialize form
	// }

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

			$this->form->button_link_with_help('zuplus_duplicate_menu',
				__('Duplicate Menu', 'zu-plugin'),
				'plus-alt',
				'magenta',
				'Select menu from dropbox and then give it a new name. A copy of selected menu will be created.'
			);
		}

		echo $this->form->fields($desc, 'zuplus_duplicate_menu', true); // second argument -> data-ajaxrel : used in js to serialize form
	}

	// public function ajax_more($option_name, $ajax_value) {
	// 	if($option_name === 'zuplus_clear_log') return $this->is_debug() ? $this->plugin->dbug->clear_log() : [];
	// 	if($option_name === 'zuplus_duplicate_menu') return zuplus_ajax_duplicate_menu();
	// 	if($option_name === 'zuplus_revoke_cookie') return ['info'	=> sprintf('Cookie "<strong>%1$s</strong>" was deleted', $ajax_value)];
	//
	// 	if($option_name === 'zuplus_reset_cached') return $this->reset_cached();
	//
	// 	return [];
	// }

	// public function reset_cached() {
	// 	$count = zu()->purge_transients();
	// 	return ['info'	=> $count ? sprintf('All cached shortcodes (<span class="_bold">%1$s %2$s</span>) were reset.', $count, $count > 1 ? 'transients' : 'transient') : 'No cached shortcodes found.'];
	// }
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

function zuplus_nodebug() {
	return !zuplus_instance()->is_debug();
}

function zuplus_options() {
	return zuplus_instance()->options();
}

// Start! --------------------------------------------------------------------]

zuplus_instance();
add_action('plugins_loaded', function() { do_action('zuplus_loaded'); });
