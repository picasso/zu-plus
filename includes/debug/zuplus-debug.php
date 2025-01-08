<?php

// Debug Bar support ------------------------------------------------------------------------------]

require_once('kint.phar');
// для отладки
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
				'instant_caching'	=> true,
				'debug_menus'		=> false,
				'debug_plugins'		=> false,
				'classname_only'	=> false,
				// 'ajax_log'			=> false,
			],
		];
	}

	protected function construct_more() {
		$this->location = $this->plugin->dir;
		$this->flywheel_path = str_replace('/app/public/', '/logs/php/', wp_normalize_path(ABSPATH));
		$this->content_path = wp_normalize_path(dirname(WP_CONTENT_DIR) . '/wp-content/');

		$this->init_kint();

		if ($this->is_option('debug_bar')) {
			$this->dbar = zu_PlusDebugBar::instance($this->options);
			$this->dbar->link($this);
		}

		// remove previous logs if 'overwrite' is true
		// skip ajax and REST calls or the log can be unintentionally cleared before reading
		if ($this->is_option('overwrite') && !wp_doing_ajax() && !$this->plugin->doing_rest()) {
			$this->clear_file($this->log_location());
			zu_PlusDebugBar::reset_logs();
		}
	}

	public function debug_info() {
		$stats = $this->log_stats();
		$use_kint = $this->is_option('use_kint');
		return [
			'kint_link'	=> [
				'label'		=> __('Used Tools', 'zu-plus'),
				'value'		=> $use_kint ? __('Kint for PHP', 'zu-plus') : null,
				'link'		=> 'https://kint-php.github.io/kint/',
				'depends' 	=> "$this->options_key.use_kint",
			],
			'kint_version'	=> [
				'label'		=> __('Kint for PHP version', 'zu-plus'),
				'value'		=> $use_kint ? $this->kint_version : null,
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
		// $this->logc('?Debug Bar option', $this->is_option('debug_bar'));
		// zu_log($this->dbar);
		// $this->log($this->options, $this->location, $this->abs_path, $this->content_path);
	}

	public function is($key) {
		return $this->is_option($key);
	}

	public function admin_enqueue($hook) {
		// add kint styles if needed
		// prefix will be added to script name automatically
		if ($this->is_option('use_kint')) $this->admin_enqueue_style('kint');
	}

	public function enqueue() {
		// add kint styles if needed on front-end
		// use 'admin_enqueue_style' because the KINT styles are located in 'admin' folder
		if ($this->is_option('debug_frontend')) {
			if ($this->is_option('use_kint')) $this->admin_enqueue_style('kint');
		}
	}

	// Debug logging ------------------------------------------------------------------------------]

	public function expanded_log($params, $called_class) {

		if ($this->is_option('avoid_ajax') && wp_doing_ajax()) return;
		if ($this->is_option('classname_only')) $params = $this->stub_class_instance($params);

		if ($this->is_option('use_kint')) {
			if ($this->is_option('write_file')) {
				$log = $this->kint_log($params);
				$this->debug_log($log);
			}
			if ($this->is_option('debug_bar')) {
				$log = $this->kint_log($params, true);
				$this->bar_log($log, true, null, $called_class);
			}
		} else {
			$data = $this->is_option('debug_bar') ? $this->bar_log($params, false, null, $called_class) : null;
			$this->plugin->log_with(is_null($data) ? $this->log_lineshift() : $data, null, ...$params);
		}
	}

	// logging with context
	public function expanded_log_with_context($context, $params, $called_class) {

		if ($this->is_option('avoid_ajax') && wp_doing_ajax()) return;
		if ($this->is_option('classname_only')) $params = $this->stub_class_instance($params);

		if ($this->is_option('use_kint')) {
			if ($this->is_option('write_file')) {
				$label = $this->plugin->get_log_label($context);
				// in order not to modify $params, create a copy of it before modifying
				$params_with_context = array_merge([], $params);
				array_unshift($params_with_context, '!context!');
				$log = $this->kint_log($params_with_context);
				$this->debug_log($label . $log);
			}
			if ($this->is_option('debug_bar')) {
				$context = htmlentities($context);
				array_unshift($params, $context);
				$log = $this->kint_log($params, true);
				$this->bar_log($log, true, $context, $called_class);
			}
		} else {
			$data = $this->is_option('debug_bar') ? $this->bar_log($params, false, $context, $called_class) : null;
			$this->plugin->log_with(is_null($data) ? $this->log_lineshift() : $data, $context, ...$params);
		}
	}

	// Logfile management -------------------------------------------------------------------------]

	public function debug_log($log) {
		if ($this->is_option('avoid_ajax') && wp_doing_ajax()) return;
		if ($this->is_option('classname_only')) $log = $this->fix_class_instance($log);
		if ($this->is_option('write_file')) error_log($log, 3, $this->log_location());
	}

	public function dump($log, $keep_tags = false) {
		$dump_func = $this->get_option('dump_method', 'var_export');
		if ($dump_func === 'print_r') return preg_replace('/\n$/m', '', print_r($log, true));
		if ($dump_func === 'dump_var') return $this->dump_value($log, $keep_tags);
		return var_export($log, true);
	}

	public function log_location($filename = null) {
		$filename = $filename ?? $this->logfile;
		return ($this->is_option('flywheel_log') ? $this->flywheel_path : trailingslashit($this->location)) . $filename;
	}

	public function clear_log($filename = null) {
		$file =  $this->log_location($filename ?? $this->logfile);
		$size = file_exists($file) ? filesize($file) : 0;
		if ($this->clear_file($file)) {
			// unlink($file);
			return $this->create_notice('success', sprintf(
				htmlentities('**Debug log** [*%2$s*] was deleted at `<ROOT>%1$s`'),
				$this->short_location($file),
				$this->snippets('format_bytes', $size, 1)
			));
		}
		return null;
	}

	public function log_stats($filename = null) {
		$file = $this->log_location($filename ?? $this->logfile);
		$size = file_exists($file) ? filesize($file) : 0;
		return [
			'file'		=> sprintf('`%s`', $this->short_location($file)),
			'size'		=> $this->snippets('format_bytes', $size, 2, false, '**%s** %s'),
			'priority'	=> $this->location_priority,
		];
	}

	public function change_log_location($path, $priority = 1) {
		if (stripos($path, '.php') !== false) $path = dirname($path);
		if ($priority > $this->location_priority) {
			$this->location = $path;
			$this->location_priority = $priority;
		}
		return $this->log_location();
	}

	private function short_location($file) {
		if ($this->is_option('flywheel_log')) return preg_replace('/.+\/logs\/php\//', '/logs/php/', $file);
		else return str_replace($this->content_path, '/', $file);
	}

	private function clear_file($file) {
		$handle = fopen($file, 'w');
		if ($handle !== false) fclose($handle);
		return $handle !== false;
	}
}
