<?php

// Debug Bar support ----------------------------------------------------------]

require_once('kint.phar');
// require_once('kint-old.phar');
// include_once('kint-debug/init_phar.php');

include_once('debug-bar.php');
include_once('trait-output.php');

class zu_PlusDebug extends zukit_Addon {

	private $dbar = null;

	private $use_var_dump = false;
	private $location = null;
	private $location_priority = 0;

	private $logfile = 'debug.log';
	private $flywheel_path;
	private $content_path;

	use zu_PlusDebugOutput;

	protected function config() {
		return [
			'name'				=> 'zuplus_debug',
			'options'			=> [
				'debug_bar'			=> true,
				'use_kint'			=> true,
				'flywheel_log'		=> false,
				'debug_frontend'	=> false,
				'debug_rsjs'		=> false,
				'debug_caching'		=> false,
				'write_file'		=> false,
				'overwrite'			=> true,
				'convert_html'		=> true,
				'dump_method'		=> 'var_export',
				'avoid_ajax'		=> true,
				'debug_menus'		=> false,

				// 'ajax_log'			=> false,
			],
		];
	}

	protected function construct_more() {
		$this->location = $this->plugin->dir;
		$this->flywheel_path = str_replace('/app/public/', '/logs/php/', wp_normalize_path(ABSPATH));
		$this->content_path = wp_normalize_path(dirname(WP_CONTENT_DIR) . '/wp-content/');

		$this->init_kint();

		if($this->is_option('debug_bar')) {
			$this->dbar = zu_PlusDebugBar::instance($this->options);
			$this->dbar->link($this);
		}
		// remove previous logs
		if($this->is_option('overwrite')) {
			$handle = fopen($this->log_location(), 'w');
			if($handle !== false) fclose($handle);
			zu_PlusDebugBar::reset_logs();
		}
	}

	public function debug_info() {
		$stats = $this->log_stats();
		$use_kint = $this->is_option('use_kint');
		return [
			'kint_link'	=> !$use_kint ? null : [
					'label'		=> __('Contributors', 'zu-plus'),
					'value'		=> __('Kint for PHP', 'zu-plus'),
					'link'		=> 'https://kint-php.github.io/kint/',
					'depends' 	=> "$this->options_key.use_kint",
			],
			'kint_version'	=> !$use_kint ? null : [
					'label'		=> __('Kint for PHP version', 'zu-plus'),
					'value'		=> $this->kint_version,
					'depends' 	=> "$this->options_key.use_kint",
			],
			'logfile'		=> [
					'label'		=> __('Logfile', 'zu-plus'),
					'value'		=> $stats['file'],
					'depends' 	=> ['debug_mode', "$this->options_key.flywheel_log"],
			],
			'logsize'		=> [
					'label'		=> __('Logfile Size', 'zu-plus'),
					'value'		=> $stats['size'],
					'depends' 	=> 'debug_mode',
			],
		];
	}

	public function admin_init() {
		// zu_log($this->dbar);
		// $this->log($this->options, $this->location, $this->abs_path, $this->content_path);
	}

	public function is($key) {
		return $this->is_option($key);
	}

	public function admin_enqueue($hook) {
		// add kint styles if needed
		// prefix will be added to script name automatically
		if($this->is_option('use_kint')) $this->admin_enqueue_style('kint');
	}

	public function enqueue() {
		// add kint styles if needed on front-end
		// use 'admin_enqueue_style' because the KINT styles are located in 'admin' folder
		if($this->is_option('debug_frontend')) {
			if($this->is_option('use_kint')) $this->admin_enqueue_style('kint');
		}
	}

	// Debug logging ----------------------------------------------------------]

	public function expanded_log($params, $called_class) {

		if($this->is_option('avoid_ajax') && wp_doing_ajax()) return;

		if($this->is_option('use_kint')) {
			if($this->is_option('write_file')) {
				$log = $this->kint_log($params);
				$this->debug_log($log);
			}
			if($this->is_option('debug_bar')) {
				$log = $this->kint_log($params, true);
				$this->bar_log($log, true, null, $called_class);
			}
		} else {
			$data = $this->is_option('debug_bar') ? $this->bar_log($params, false, null, $called_class) : null;
			$this->plugin->log_with(is_null($data) ? 2 : $data, null, ...$params);
		}
    }

	// logging with context
	public function expanded_log_with_context($context, $params, $called_class) {

		if($this->is_option('avoid_ajax') && wp_doing_ajax()) return;

		if($this->is_option('use_kint')) {
			if($this->is_option('write_file')) {
				$label = $this->plugin->get_log_label($context);
				$log = $this->kint_log($params);
				$this->debug_log($label.$log);
			}
			if($this->is_option('debug_bar')) {
				array_unshift($params, $context);
				$log = $this->kint_log($params, true);
				$this->bar_log($log, true, $context, $called_class);
			}
		} else {
			$data = $this->is_option('debug_bar') ? $this->bar_log($params, false, $context, $called_class) : null;
			$this->plugin->log_with(is_null($data) ? 2 : $data, $context, ...$params);
		}
	}

	// Logfile management -----------------------------------------------------]

	public function debug_log($log) {
		if($this->is_option('avoid_ajax') && wp_doing_ajax()) return;
		if($this->is_option('write_file')) error_log($log, 3, $this->log_location());
	}

	public function dump($log, $keep_tags = false) {
		$dump_func = $this->get_option('dump_method', 'var_export');
		if($dump_func === 'print_r') return preg_replace('/\n$/m', '', print_r($log, true));
		if($dump_func === 'dump_var') return $this->dump_value($log, $keep_tags);
		return var_export($log, true);
	}

	public function log_location($filename = null) {
		$filename = $filename ?? $this->logfile;
		return ($this->is_option('flywheel_log') ? $this->flywheel_path : trailingslashit($this->location)).$filename;
	}

	public function clear_log($filename = null) {
		$file =  $this->log_location($filename ?? $this->logfile);
		unlink($file);
		$this->create_notice('info', sprintf(
			'Debug log was deleted at <strong>%1$s</strong>',
			$this->short_location($file)
		));
	}

	public function log_stats($filename = null) {
		$file = $this->log_location($filename ?? $this->logfile);
		$size = file_exists($file) ? filesize($file) : 0;
		return [
			'file'		=> $this->short_location($file),
			'size'		=> $this->snippets('format_bytes', $size, 2),
			'priority'	=> $this->location_priority,
		];
	}

	public function change_log_location($path, $priority = 1) {
		if(stripos($path, '.php') !== false) $path = dirname($path);
		if($priority > $this->location_priority) {
			$this->location = $path;
			$this->location_priority = $priority;
		}
	}

	private function short_location($file) {
		if($this->is_option('flywheel_log')) return preg_replace('/.+\/logs\/php\//', '/logs/php/', $file);
		else return str_replace($this->content_path, '/', $file);
	}
}

// Functions for use in code --------------------------------------------------]

if(!function_exists('zu_write_log')) {
	function zu_write_log($msg, $var = 'novar') {
		if(zuplus_nodebug()) return;
		if(zuplus_instance()->dbug->use_kint) {
			$info = $var;
			_dbug('ZU_LOG: '.$msg, $info);
		} else {
			zuplus_instance()->dbug->write_log($msg, $var);
		}
	}
}

if(!function_exists('_dbug_change_log_location')) {
	function _dbug_change_log_location($path, $priority = 1) {
		if(zuplus_nodebug()) return;
		zuplus_instance()->dbug->change_log_location($path, $priority);
	}
}

if(!function_exists('_dbug_log_if')) {
	function _dbug_log_if($condition, $msg, $var = 'novar', $bt = false) {
		if(zuplus_nodebug()) return;
		zuplus_instance()->dbug->write_log_if($condition, $msg, $var, $bt);
	}
}

if(!function_exists('_ajax_log')) {
	function _ajax_log($data) {
		if(zuplus_nodebug()) return;
		zuplus_instance()->dbug->write_ajax_log($data);
	}
}
