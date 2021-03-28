<?php

// Debug Bar support ----------------------------------------------------------]

require_once('kint.phar');
// Kint::$return = true;
include_once('debug-bar.php');

class zu_PlusDebug extends zukit_Addon {

	private $dbug_bar = null;

	private $use_var_dump = false;
	private $location = 0;
	private $location_priority = 0;
	private $abs_path;
	private $content_path;

	protected function config() {
		return [
			'name'				=> 'zuplus_debug',
			'options'			=> [
				'ajax_log'			=>	false,
				'profiler'			=>	false,
				'debug_js'			=>	false,
				'debug_cache'		=>	false,
				'debug_bar'			=>	true,
				'debug_backtrace'	=>	false,
				'write_to_file'		=>	false,
				'output_html'		=>	true,
				'beautify_html'		=>	true,
				'use_kint'			=>  false,
				'debug_frontend'	=> 	false,
			],
		];
	}

	protected function construct_more() {
		$this->location = $this->plugin->dir;
		$this->abs_path = wp_normalize_path(ABSPATH);
		$this->content_path = wp_normalize_path(dirname(WP_CONTENT_DIR) . '/wp-content/');
	}

	public function admin_init() {

		if($this->is_option('use_kint')) {
			Kint::$enabled_mode = true;
			// Kint\Renderer\RichRenderer::$theme = 'aante-light.css';
			Kint::$return = true;
		} else {
			Kint::$enabled_mode = false;
		}

		if($this->is_option('debug_bar')) {
			$this->dbug_bar = new ZU_DebugBar(
				$this->is_option('profiler'),
				$this->is_option('output_html'),
				$this->is_option('use_kint')
			);
		}
	}

	public function is_debug_frontend() {
		return $this->is_option('debug_frontend');
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
		if($this->is_option('debug_frontend') {
			if($this->is_option('use_kint')) $this->admin_enqueue_style('kint');
		}
	}

	// Debug logging ----------------------------------------------------------]

	public function clear_log($filename = 'debug.log') {
		$f =  $this->log_location($filename);
		unlink($f);
		return ['info'	=> sprintf('Log was deleted at <strong>%1$s</strong>', $f)];
	}

	public function log_stats($filename = 'debug.log') {

		$f = $this->log_location($filename);
		$size = file_exists($f) ? filesize($f) : 0;
		return ['size'	=> zu()->format_bytes($size, 2), 'priority' => $this->location_priority];
	}

	public function log_location($filename = 'debug.log') {
		return trailingslashit($this->location).$filename;
	}

	public function change_log_location($path, $priority = 1) {
		if(stripos($path, '.php') !== false) $path = dirname($path);
		if($priority > $this->location_priority) {
			$this->location = trailingslashit($path);
			$this->location_priority = $priority;
		}
	}

	public function profiler_flag($flag_name) {
		// Call this at each point of interest, passing a descriptive string
		if($this->is_option('profiler') && !empty($this->dbug_bar)) $this->dbug_bar->set_profiler_flag($flag_name);
	}

	public function save_log($log_name, $log_value = '', $ip = null, $refer = null) {
    	if(!empty($this->dbug_bar)) {
	    	$log_value = $log_value !== 'novar' ? $this->process_var($log_value, $this->is_option('output_html')) : '';
	    	$this->dbug_bar->save_log($log_name, $log_value, $ip, $refer);
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

	// Admin functions -----------------------------------------------------------]

	public function print_log_location($post) {

		$form = $this->get_form();
		if(empty($form)) return;

		$form->add_value('log_location', $this->log_location());
		$form->text('log_location', 'Log Location', '', true);

		echo $form->fields('It can be changed with the function <span>_dbug_change_log_location()</span>.');
	}

	public function print_stats() {

		$stats = $this->log_stats();
		return sprintf('<p>Log size: <span>%1$s</span></p><p>Log priority: <span>%2$s</span></p>', $stats['size'], $stats['priority']);
	}

	public function print_debug_options($post) {

		$form = $this->get_form();
		if(empty($form)) return;

		$form->checkbox('debug_bar', 'Use Debug Bar', 'Works only if <span>Query Monitor</span> is activated.');
		$form->checkbox('use_kint', 'Use KINT', '<span>Kint for PHP</span> is a tool designed to present debugging data in the best way possible graphically.');
		$form->checkbox('debug_frontend', 'Support on Front End', 'Enable debugging JS & CSS on the front side. Commonly used with KINT.');
		$form->checkbox('debug_js', 'Activate Responsive JS Debug info', 'Adds class <span>debug</span> to BODY and displays debug info for responsive elements.');

		$form->checkbox('debug_cache', 'Debug Caching', 'If checked, all calls to cache functions will be logged.');
		$form->checkbox('debug_backtrace', 'Always Include Backtrace', 'In some cases, this can <span>greatly slow down</span> the loading of the page and even lead to a fatal error.');
		$form->checkbox('write_to_file', 'Write log to file', 'If unchecked, only the information for <span>Debug Bar</span> will be saved.');
		$form->checkbox('output_html', 'Display HTML entities in Debug Bar', 'If checked, all characters which have HTML character entity equivalents are translated into these entities.');
		$form->checkbox('beautify_html', 'Beautify HTML in output', 'If unchecked, all HTML values will be saved without any modifications. Otherwise HTML beautifier will be used.');

		$form->checkbox('ajax_log', 'Activate AJAX Logging', 'You should make <span>AJAX calls</span> from your JS.');
		$form->checkbox('profiler', 'Activate Profiler', 'You should call <span>_profiler_flag()</span> at each point of interest, passing a descriptive string.');

		echo $form->fields('Debug Mode Settings.');
	}

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

if(!function_exists('_dbug_dump')) {	// Use this function to output structured information. Arrays and objects are explored recursively with values indented to show structure.
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
