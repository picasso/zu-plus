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
		$from = Zukit::keypath($this->zukit_dirname(), '<ROOT>', null);
		return $this->create_notice('data', null, [
			'version'	=> $version,
			'from'		=> $from,
			'plugins'	=> $this->generate_zukit_table($version),
		]);
	}

	private function generate_zukit_table($active_version) {
		$origin_marker = '*framework origin*';

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
		$table = new zukit_Table(['origin', 'logo', 'name', 'version', 'lastest', 'framework', 'settings']);

		$table->align(['origin', 'version'], 'center');
		$table->strong('name');
		$table->as_icon('logo');
		$table->fit_content(['origin', 'logo', 'version', 'lastest', 'framework', 'settings']);

		$rows = [];
		$instances = $this->instance_by_router();
		$zukit_origin = rtrim($this->zukit_dirname(), '/zukit');
		$zukit_version = preg_replace('/[^\d|.]+/', '', $active_version);

		$origin_found = false;
		foreach($instances as $router => $instance) {
			$info = $instance->info();

			$link = $instance->admin_settings_link(null, true);
			$zuver = $this->detect_zukit_version($instance->dir);
			$zuver_fixed = preg_replace('/[^\d|.]+/', '', $zuver);

			$origin_found = $origin_found || $zukit_origin === $instance->dir;
			$table->markdown_cell('origin', $zukit_origin === $instance->dir ? $origin_marker : '');
			$table->cell('name', $info['title']);
			$table->icon_cell('logo', ['svg' => $info['icon'] ?? $this->get_default_icon()]);
			$table->dynamic_cell('version',[
					'markdown'	=> true
				],
				sprintf('Version `%s`', $info['version'])
			);
			$table->dynamic_cell('lastest', [
				'markdown'	=> true,
				'github'	=> $info['github'],
				'current'	=> $info['version'],
				'linked'	=> 'version',
			]);
			$table->dynamic_cell('framework', [
					'markdown'	=> true,
					'current'	=> $zuver,
				],
				sprintf('Zukit Version `%s`', $zuver),
				$zuver === $active_version ? 'active' : ''
			);
			$table->link_cell('settings', $link[0], $link[1]);

			$table->next_row();
		}

		// if origin is not found, we have a special case ('Zu Debug' plugin?)
		if($origin_found === false) {
			$origin_fullpath = Zukit::get_file($zukit_origin);
	// _zu_log($path_parts, $path_parts2, $origin_fullpath, dirname($zukit_origin));
			$data = Zukit::get_file_metadata($origin_fullpath);
			if(!empty($data['Name']) && !empty($data['Version'])) {
				$table->markdown_cell('origin', $origin_marker);
				$table->cell('name', $data['Name']);
				$table->icon_cell('logo', ['svg' => $this->get_default_icon()]);
				$table->dynamic_cell('version',[
						'markdown'	=> true
					],
					sprintf('Version `%s`', $data['Version'])
				);
				$table->dynamic_cell('lastest', [
					'markdown'	=> true,
					'github'	=> $data['GitHubURI'],
					'current'	=> $data['Version'],
					'linked'	=> 'version',
				]);
				$table->dynamic_cell('framework', [
						'markdown'	=> true,
						'current'	=> $zukit_version,
					],
					sprintf('Zukit Version `%s`', $this->zukit_ver()), // $this->zukit_ver()
					'active'
				);
				$table->next_row();
			}
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

	private function get_default_icon() {
		return (
			'<svg version="1.1" xmlns="http://www.w3.org/2000/svg" x="0" y="0" width="100" height="80" viewBox="0,0,100,80">
				<path d="M100,10 C100,4.48 95.52,0 90,0 L10,0 C4.48,0 0,4.48 0,10 L0,70 C0,75.52 4.48,80 10,80 L90,
				80 C95.52,80 100,75.52 100,70 L100,10 z M90,70 L10,70 L10,10 L90.015,10 L90,70 z" fill="#444444"/>
			</svg>'
		);
	}
}
