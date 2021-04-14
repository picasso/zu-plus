<?php

// Ajax/REST API helpers ------------------------------------------------------]

trait zu_PlusAjax {

	public function ajax_more($action, $value) {
		if($action === 'zuplus_zukit_info') return $this->zukit_info();
		if($action === 'zuplus_duplicate_menu') return $this->duplicate_menu($value);
		// if($action === 'zuplus_revoke_cookie') return ['info'	=> sprintf('Cookie "<strong>%1$s</strong>" was deleted', $ajax_value)];

		if($action === 'zuplus_reset_cached') return $this->reset_cached();

		return null;
	}

	public function zukit_info() {
		$version = method_exists($this, 'zukit_ver') ? $this->zukit_ver() : null;
		$content_path = wp_normalize_path(dirname(WP_CONTENT_DIR) . '/wp-content');
		$from = $zukit_origin = str_replace($content_path, '<ROOT>', $this->zukit_dirname());

		return $this->create_notice('data', null, [
			'version'	=> $version,
			'from'		=> $from,
			'plugins'	=> $this->generate_zukit_table($version),
		]);
	}

	private function generate_zukit_table($active_version) {

		function asKind($wp, $zu, $is_modified, $icon) {
			$tooltip = $wp ?
				($is_modified ? __('Wordpress [modified]', 'zu-media') : __('Wordpress', 'zu-media')) :
				($zu ? __('Zu Media', 'zu-media') : __('Third Party', 'zu-media'));

			return [
				'tooltip'	=> $tooltip,
				'dashicon'	=> $wp ? ($is_modified ? 'wordpress' : 'wordpress-alt') : ($zu ? null : 'admin-plugins'),
				'svg'		=> $zu ? $icon : null,
				'style'		=> $is_modified,
			];
		}

		// define table columns and styles
		$table = new zukit_Table(['origin', 'logo', 'name', 'version', 'framework', 'settings'], true);

		$table->align(['origin', 'version'], 'center');
		$table->strong('name');
		$table->as_icon('logo');
		$table->shrink(['logo', 'version', 'settings']);
		$table->fixwidth(['origin', 'name', 'framework'], [null, '100px', '200px']);

		$rows = [];
		$instances = $this->instance_by_router();
		$zukit_origin = rtrim($this->zukit_dirname(), '/zukit');
		$zukit_version = preg_replace('/[^\d|.]+/', '', $active_version);

		foreach($instances as $router => $instance) {
			$info = $instance->info();
			// $info['author']
			// $info['description']
			$link = $instance->admin_settings_link(null, true);
			$zuver = $this->detect_zukit_version($instance->dir);
			$zuver_fixed = preg_replace('/[^\d|.]+/', '', $zuver);

			$table->markdowncell('origin', $zukit_origin === $instance->dir ? '*origin*' : '');

			$table->cell('name', $info['title']);
			$table->iconcell('logo', ['svg' => $info['icon']]);
			$table->cell('name', $info['title']);
			$table->markdowncell('version', sprintf('Version `%s`', $info['version']));
			$table->cell_with_params(
				'framework',
				sprintf('Zukit Version `%s`', $zuver), [
					'markdown',
					'className' => $zuver === $active_version ? 'active' :
						(version_compare($zuver_fixed, $zukit_version, '<=') ? 'less' : 'great'),
				]
			);
			$table->linkcell('settings', $link[0], $link[1]);

			$table->next_row();
		}

		return $table->get(false);
	}

	private function detect_zukit_version($dir) {
		$version = '???';
		$file = sprintf('%s/zukit/zukit-plugin.php', $dir);
		$tokens = [
			'/get_bloginfo\(\'version\'\)/m' => '1.1.5',
			'/\'actions\'\s*=>\s*\$this->extend_actions\(\)/m' => '1.1.4',
			'/\$this->blocks\s*=\s*new\s+zukit_Blocks;/m' => '1.1.3',
			'/public\s+function\s+force_frontend_enqueue\(/m' => '1.1.1',

		];
		if(file_exists($file)) {
			$content = file_get_contents($file);
			if($content !== false) {
				$version_regex = '/private\s+static\s+\$zukit_version\s*=\s*[\'|"]([^\'|"]+)/m';
				$version = preg_match($version_regex, $content, $matches) ? $matches[1] : null;
				if($version === null) {
					foreach($tokens as $regex => $ver) {
						if(!preg_match($regex, $content)) continue;
						$version = $ver;
						break;
					}
					if($version === null) $version = '1.1.0';
				}
			}
		}
		return $version;
	}
}
