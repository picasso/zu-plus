<?php

// Debug Bar support ----------------------------------------------------------]

require_once('kint.phar');
// include_once('kint-debug/init_phar.php');

include_once('debug-bar.php');
include_once('trait-output.php');

class zu_PlusDebug extends zukit_Addon {

	private $dbar = null;

	private $use_var_dump = false;
	private $location = null;
	private $location_priority = 0;

	private $logfile = 'debug.log';
	private $abs_path;
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
				'overwrite_file'	=> false,
				'output_html'		=> true,

				// 'ajax_log'			=> false,
				// 'profiler'			=> false,
				// 'debug_backtrace'	=> false,
				// 'beautify_html'		=> true,
			],
		];
	}

	protected function construct_more() {
		$this->location = $this->plugin->dir;
		$this->abs_path = wp_normalize_path(ABSPATH);
		$this->flywheel_path = str_replace('/app/public/', '/logs/php/', $this->abs_path);
		$this->content_path = wp_normalize_path(dirname(WP_CONTENT_DIR) . '/wp-content/');

		$this->init_kint();

		if($this->is_option('debug_bar')) {
			$this->dbar = zu_PlusDebugBar::instance($this->options);
			$this->dbar->link($this->plugin);
		}
		if($this->is_option('overwrite_file')) {
			$handle = fopen($this->log_location(), 'w');
			fclose($handle);
			// $this->clear_log();
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
		// zu_logc('Kint\Renderer\RichRenderer::$theme', Kint::$return, Kint::$enabled_mode);
		//
		// $this->log($this->options, $this->location, $this->abs_path, $this->content_path);
	}

	public function is($key) {
		return $this->is_option($key);
	}

	public function is_debug_frontend() {
		return $this->is_option('debug_frontend');
	}
	public function is_use_kint() {
		return $this->is_option('use_kint');
	}

	public function admin_enqueue($hook) {
		if(in_array($hook, ['post.php', 'post-new.php'])) {
			$this->admin_enqueue_script('debug', [
				'data'		=> [
					'remove_autosave' => $this->is_option('remove_autosave'),
				],
			]);
		}
		// prefix will be added to script name automatically
		// $this->admin_enqueue_style('debug');
		// add kint styles if needed
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
		// $args = func_get_args();
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
			$data = $this->is_option('debug_bar') ? $this->bar_log($params) : null;
			$this->plugin->log_with(is_null($data) ? 2 : $data, null, ...$params);
		}
    }

	// logging with context
	public function expanded_log_with_context($context, $params, $called_class) {
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
			$data = $this->is_option('debug_bar') ? $this->bar_log($params, false, $context) : null;
			$this->plugin->log_with(is_null($data) ? 2 : $data, $context, ...$params);
			// $this->plugin->log_with(2, $context, ...$params);
		}
	}

	// Logfile management -----------------------------------------------------]

	public function debug_log($log) {
		if($this->is_option('write_file')) error_log($log, 3, $this->log_location());
	}

	public function log_location($filename = null) {
		$filename = $filename ?? $this->logfile;
		return ($this->is_option('flywheel_log') ? $this->flywheel_path : trailingslashit($this->location)).$filename;
	}

	private function short_location($file) {
		if($this->is_option('flywheel_log')) return preg_replace('/.+\/logs\/php\//', '/logs/php/', $file);
		else return str_replace($this->content_path, '/', $file);
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

	public function profiler_flag($flag_name) {
		// Call this at each point of interest, passing a descriptive string
		if($this->is_option('profiler') && !empty($this->dbar)) $this->dbar->set_profiler_flag($flag_name);
	}

	public function save_log($log_name, $log_value = '', $ip = null, $refer = null) {
    	if(!empty($this->dbar)) {
	    	$log_value = $log_value !== 'novar' ? $this->process_var($log_value, $this->is_option('output_html')) : '';
	    	$this->dbar->save_log($log_name, $log_value, $ip, $refer);
	    }
	}

	public function use_var_dump($dump = true) {
		$this->use_var_dump = $dump;
	}


	public function get_standard_dir($dir, $path_replace = null) {

		$dir = wp_normalize_path($dir);
		if(is_string($path_replace)) $dir = str_replace([$this->content_path, $this->abs_path], $path_replace, $dir);

		return $dir;
	}

	public function write_log_no_debug_bar($msg, $var='novar', $bt = false) {
		$this->write_log($msg, $var, $bt, false, true);
	}

	public function write_log_if($condition, $msg, $var = 'novar', $bt = false, $save_debug_bar = true) {
		if($condition) $this->write_log($msg, $var, $bt, $save_debug_bar);
	}

	// Debug methods ----------------------------------------------------------]

}

// Functions for use in code --------------------------------------------------]

if(!function_exists('_dbug')) {
	function _dbug() {
		if(zuplus_nodebug()) return;
		$args = func_get_args();
		if(zuplus_instance()->dbug->use_kint) {
			$log = call_user_func_array(['Kint', 'dump'], $args);
			zuplus_instance()->dbug->save_log($log);
		} else {
			$value = $args[0];
			zuplus_instance()->dbug->save_log('unknown', $value);
			// call_user_func_array([zuplus_instance()->dbug, 'save_log'], $args);
		}
	}
	Kint::$aliases[] = '_dbug';
}

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

if(!function_exists('_dbug_use_var_dump')) {
	function _dbug_use_var_dump($dump = true) {
		if(zuplus_nodebug()) return;
		zuplus_instance()->dbug->use_var_dump($dump);
	}
}

if(!function_exists('_dbug_change_log_location')) {
	function _dbug_change_log_location($path, $priority = 1) {
		if(zuplus_nodebug()) return;
		zuplus_instance()->dbug->change_log_location($path, $priority);
	}
}

if(!function_exists('_dbug_log')) {
	function _dbug_log($msg, $var = 'novar', $bt = false) {
		if(zuplus_nodebug()) return;
		zuplus_instance()->dbug->write_log($msg, $var, $bt);
	}
}

if(!function_exists('_dbug_log_only')) {
	function _dbug_log_only($msg, $var = 'novar', $bt = false) {
		if(zuplus_nodebug()) return;
		zuplus_instance()->dbug->write_log_no_debug_bar($msg, $var, $bt);
	}
}
// Use this function to output structured information. Arrays and objects are explored
// recursively with values indented to show structure.
if(!function_exists('_dbug_dump')) {
	function _dbug_dump($msg, $var = 'novar', $bt = false) {
		if(zuplus_nodebug()) return;
		zuplus_instance()->dbug->use_var_dump(true);
		zuplus_instance()->dbug->write_log($msg, $var, $bt);
		zuplus_instance()->dbug->use_var_dump(false);
	}
}

if(!function_exists('_dbug_trace')) {
	function _dbug_trace($msg, $full_trace = false) {
		if(zuplus_nodebug()) return;
		zuplus_instance()->dbug->write_trace($msg, $full_trace);
	}
}

if(!function_exists('_dbug_log_if')) {
	function _dbug_log_if($condition, $msg, $var = 'novar', $bt = false) {
		if(zuplus_nodebug()) return;
		zuplus_instance()->dbug->write_log_if($condition, $msg, $var, $bt);
	}
}

if(!function_exists('_profiler_flag')) {
	function _profiler_flag($flag_name) {
		if(zuplus_nodebug()) return;
		zuplus_instance()->dbug->profiler_flag($flag_name);
	}
}

if(!function_exists('_tbug_log')) {
	function _tbug_log($log_name, $log_value) {
		if(zuplus_nodebug()) return;
		zuplus_instance()->dbug->save_log($log_name, $log_value);
	}
}

if(!function_exists('_ajax_log')) {
	function _ajax_log($data) {
		if(zuplus_nodebug()) return;
		zuplus_instance()->dbug->write_ajax_log($data);
	}
}
