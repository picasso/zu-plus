<?php

// Debug Bar support ----------------------------------------------------------]

include_once('debug-bar.php');

class ZU_Debug extends zuplus_Addon {

	private $alog;
	private $profiler;
	
	private $use_backtrace;
	private $write_to_file;
	private $beautify_html;
	private $output_html;
	
	private $dbug_bar;
	private $use_var_dump;
	private $location;
	private $location_priority;
	private $abs_path;
	private $content_path;

    public static $_defaults = [
		'ajax_log'					=>	false,
		'profiler'					=>	false,
		'debug_js'					=>	false,
		'debug_cache'			=>	false,
		'debug_bar'				=>	true,
		'debug_backtrace'		=>	false,
		'write_to_file'			=>	false,
		'output_html'			=>	true,
		'beautify_html'			=>	true,
    ];

	protected static $ignore_class = [
		'wpdb'           		=> true,
		'QueryMonitor'   	=> true,
	];
	protected static $ignore_method = [
		'ZU_Debug'          => [
			'get_backtrace'			=> true, 
			'write_log'					=> true,
		],
	];
	protected static $ignore_func = [
		'call_user_func_array' 	=> true,
		'call_user_func'       		=> true,
	];
	protected static $ignore_myself = [
		'zu_write_log'   							=> true,
		'_dbug_change_log_location'		=> true,
		'_dbug_log'           					=> true,
		'_dbug_log_only'   					=> true,
		'_dbug_dump'   						=> true,
		'_dbug_use_var_dump'  			=> true,
		'_dbug_log_if'							=> true,
		'_tbug_log'           						=> true,
		'_dbug_trace'							=> true,
		'_profiler_flag'      						=> true,
		'_ajax_log'           						=> true,
	];
	protected static $ignore_includes = [
		'include_once'       => true,
		'require_once'       => true,
		'include'              	=> true,
		'require'              	=> true,
	];
	protected static $show_args = [
		'do_action'            => 1,
		'apply_filters'        => 1,
	];
	
	protected function construct_more() {
		
		$this->alog = $this->check_option('ajax_log');		
		$this->profiler = $this->check_option('profiler');	
		$this->use_backtrace = $this->check_option('debug_backtrace');	
		$this->write_to_file = $this->check_option('write_to_file');
		$this->beautify_html = $this->check_option('beautify_html');
		$this->output_html = $this->check_option('output_html');
		
		$this->location = __ZUPLUS_ROOT__;
		$this->dbug_bar = null;
		$this->location_priority = 0;
		$this->use_var_dump = false;

		$this->abs_path = wp_normalize_path(ABSPATH);
		$this->content_path = wp_normalize_path(dirname(WP_CONTENT_DIR) . '/wp-content/');
		
		if($this->check_option('debug_bar')) $this->dbug_bar = new ZU_DebugBar($this->profiler, $this->output_html); 
	}

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
		if($this->profiler && !empty($this->dbug_bar)) $this->dbug_bar->set_profiler_flag($flag_name);
	}

	public function save_log($log_name, $log_value = '', $ip = null, $refer = null) {
    	if(!empty($this->dbug_bar)) {

	    	$log_value = $log_value !== 'novar' ? $this->process_var($log_value, $this->output_html) : '';
	    	$this->dbug_bar->save_log($log_name, $log_value, $ip, $refer);
	    }
	}

	public function string_backtrace() { 
	    ob_start(); 
	    debug_print_backtrace(); 
	    $trace = ob_get_contents(); 
	    ob_end_clean(); 
	
	    // Remove first item from backtrace as it's this function which 
	    // is redundant. 
	    $trace = preg_replace ('/^#0\s+' . __FUNCTION__ . "[^\n]*\n/", '', $trace, 1); 
	
	    // Remove arguments 
	    $trace = preg_replace ('/(\(.+?\)) called/', '() called', $trace);
	    
		preg_match_all('/#(\d+)([^\(]+)([^#]+)/i', $trace, $matches);
		foreach($matches[0] as $key => $replacement) {
			preg_match('/called\s+at\s+(\[[^\]]+\])/i', $replacement, $called);
			$called = empty($called[1]) ? '' : ' called at '.$called[1];
			
			$num = $matches[1][$key];
			$fname = $matches[2][$key];
			
			$trace_line = sprintf('#%1$s %2$s() %3$s %4$s', $num, $fname, $called, PHP_EOL);
			$trace = str_replace($replacement, $trace_line, $trace);
		}
	
	    return $trace; 
	} 	

	public function use_var_dump($dump = true) {
		$this->use_var_dump = $dump;
	}
	
	public function process_var($var, $try_beautify_html = true) {
		
		if($this->use_var_dump) {
			ob_start();
			var_dump($var);
			$output = ob_get_contents();
			ob_end_clean();
		} else {
			if($try_beautify_html && $this->beautify_html) {
				if(is_string($var) && $var != strip_tags($var)) $var = zu()->beautify_html($var, true);
				else if(is_array($var)) {
					foreach($var as $key => $value) {
						if(is_string($value) && $value != strip_tags($value)) $var[$key] = zu()->beautify_html($value, true);
					}	
				}
			}
			$output = var_export($var, true);
		}
		return $output;
	}
	
	public function get_backtrace() {
		
		$full_trace = debug_backtrace(false);
		$trace = array_map([$this, 'filter_trace'], $full_trace);
		$trace = array_values(array_filter($trace));

		if(empty($trace) && !empty($full_trace)) {
			$lowest = $full_trace[0];
			$file = $this->get_standard_dir($lowest['file'], '');
			$lowest['calling_file'] = $lowest['file'];
			$lowest['calling_line'] = $lowest['line'];
			$lowest['function'] = $file;
			$lowest['display'] = $file;
			unset($lowest['class'], $lowest['args'], $lowest['type']);
			$trace[0] = $lowest;
		}
		
		return $this->filter_trace_from_debug($trace);
	}

	public function filter_trace_from_debug($filtered_trace) {
		
		foreach($filtered_trace as $key => $trace) {
			if(isset(self::$ignore_myself[$trace['function']])) {
				$next = isset($filtered_trace[$key + 1]) ? $filtered_trace[$key + 1] : ['function' => ''];
				if(isset(self::$ignore_includes[$next['function']])) {
					$filtered_trace[$key]['display'] = $next['function'] . '(' . basename($filtered_trace[$key]['calling_file']) . ')';
				} else {
					if(!empty($next['function'])) $filtered_trace[$key]['display'] = $filtered_trace[$key + 1]['display'];
				}
			}
		}
		
		return array_values(array_filter($filtered_trace));
	}

	public function filter_trace($trace) {

		$return = $trace;

		if(isset($trace['class'])) {
			if(isset(self::$ignore_class[$trace['class']])) {
				$return = null;
			} else if(isset(self::$ignore_method[$trace['class']][$trace['function']])) {
				$return = null;
			} else {
				$return['display'] = $trace['class'] . $trace['type'] . $trace['function'] . '()';
			}
		} else {
			if(isset(self::$ignore_func[$trace['function']])) {
				$return = null;
			} else {
				if(isset(self::$show_args[$trace['function']])) {
					$args = [];
					$check_args = self::$show_args[$trace['function']];
					for($i = 0; $i < $check_args; $i++) {
						if(isset($trace['args'][$i])) {
							if(is_array($trace['args'][$i])) $args[] = 'array[' . count($trace['args'][$i]) . ']';
							else $args[] = '\'' . print_r($trace['args'][$i], true) . '\'';
						}
					}
					$return['display'] = $trace['function'] . '(' . implode(',', $args) . ')';
				} else {
					$return['display'] = $trace['function'] . '()';
				}
			}
		}

		if($return) {
			$return['calling_file'] = isset($trace['file']) ? $this->get_standard_dir($trace['file'], '') : '';
			$return['calling_line'] = isset($trace['line']) ? $trace['line'] : 0;
			unset($return['class'], $return['args'], $return['type']);
		}

		return $return;
	}

	public function get_standard_dir($dir, $path_replace = null) {

		$dir = wp_normalize_path($dir);
		if(is_string($path_replace)) $dir = str_replace([$this->content_path, $this->abs_path], $path_replace, $dir);

		return $dir;
	}

	public function get_full_backtrace($ignore_args = true) {
		$full_trace = debug_backtrace(false);
		$trace = array_map([$this, 'ignore'], $full_trace);
		$trace = array_values(array_filter($trace));
		return $trace;
	}
	
	public function ignore($trace) {
		unset($trace['args'], $trace['type']);
		if(isset($trace['file'])) $trace['file'] = $this->get_standard_dir($trace['file'], '');
		return $trace;
	}
	
	public function write_trace($msg, $full_trace = false, $ignore_args = true) {
		
		$trace = $full_trace ? $this->get_full_backtrace($ignore_args) : $this->get_backtrace();
		unset($trace['args'], $trace['type']);

		$f =  $this->log_location();
		$msg = sprintf('%4$s[%1$s]-------------------%2$s%4$s%3$s', 
			date('d.m H:i:s'), 
			str_replace('\n', PHP_EOL, $msg), 
			$this->process_var($trace), 
			PHP_EOL
		);
		
		if($this->write_to_file) error_log($msg.PHP_EOL, 3, $f);
	}
	
	public function write_log($msg, $var = 'novar', $bt = false, $save_debug_bar = true, $forced_write_file = false) {
		
		$trace = ($this->use_backtrace || $bt)  ? $this->get_backtrace() : [];
		$f =  $this->log_location();

		$ip = $ajax = $refer = '';
		$ip = $this->get_request_ip();
		$ajax = $this->get_request_ajax();
		$refer = zu()->translit(urldecode($this->get_request_refer()));
		
		$tracelog = [];
		foreach($trace as $traceline) {
			$t_display = isset($traceline['display']) ? $traceline['display'] : 'unknown';
			$t_file = isset($traceline['calling_file']) ? $traceline['calling_file'] : 'unknown';
			$t_line = isset($traceline['calling_line']) ? $traceline['calling_line'] : 'unknown';
			
			$t_file = empty($t_file) ? 'closure' : $t_file;
			$t_line = $t_line == '0' ? '?' : $t_line;
			
			$tracelog[] = sprintf('%1$s%4$s<span class="qm-info qm-supplemental">%2$s:%3$s</span>%4$s',  $t_display, $t_file, $t_line, '<br>');	
		}	
		
		$refer_html = empty($trace) ? $refer : sprintf('%1$s%3$s<span class="qm-info"><strong>from %2$s</strong></span><br>',  implode('', $tracelog), $refer, empty($tracelog) ? '' : '<br>');
		
		if($bt) {
			$refer = strip_tags(str_replace('<br>',  PHP_EOL, $refer_html));
		} else {
			$refer = str_replace('<strong>-ajax-</strong> ', 'AJAX:', urldecode($refer));
			if(empty($trace)) $refer = sprintf('		from %2$s%1$s', PHP_EOL, $refer);
			else $refer = sprintf('%1$s%4$s	%2$s:%3$s%4$s		from %5$s%4$s',  $trace[0]['display'], $trace[0]['calling_file'], $trace[0]['calling_line'], PHP_EOL, $refer);
		}
		
		if($save_debug_bar) $this->save_log($msg, $var, $ip, $refer_html);
			
		$msg = sprintf('%6$s[%1$s]	%4$s~%3$s%6$s%5$s			%2$s --------------------------------------------------]%6$s', 
			date('d.m H:i:s'), 
			str_replace('\n', PHP_EOL, $msg), 
			$ip, 
			$ajax ? 'A ' : 'N', 
			$refer, 
			PHP_EOL
		);
		
		if($var !== 'novar')
			$msg .= $this->process_var($var);
		if($bt) {
			$msg .= PHP_EOL.'backtrace:'.PHP_EOL.$this->string_backtrace();
		}
		
		if($this->write_to_file || $forced_write_file) error_log($msg.PHP_EOL, 3, $f);
	}

	public function write_log_no_debug_bar($msg, $var='novar', $bt = false) { 
		$this->write_log($msg, $var, $bt, false, true); 
	}

	public function write_log_if($condition, $msg, $var = 'novar', $bt = false, $save_debug_bar = true) {
		if($condition) $this->write_log($msg, $var, $bt, $save_debug_bar);
	}

	public function write_ajax_log($data) {
		
		if(!$this->alog) return;
		
		$f = $this->log_location('ajax.log');
		$ip = $this->get_request_ip();
		$refer = str_replace(zu()->base_url(null, null, true)['host'], '', $this->get_request_refer());
		$refer = preg_replace('/https?:\/\//i', '', $refer);
		$refer = str_replace('<strong>-ajax-</strong> ', 'ajax:', urldecode($refer));
			
		$msg = sprintf('[%1$s] %3$s -------------------[%2$s]%4$s', date('d.m H:i:s'), $ip, $refer, PHP_EOL);
		$msg .= $this->process_var($data);
		
		if($this->write_to_file) error_log($msg.PHP_EOL.PHP_EOL, 3, $f);
	}
	
	public function get_request_ip() { return ZU_DebugBar::get_server_value('REMOTE_ADDR'); }
	public function get_request_ajax() { return ZU_DebugBar::get_server_value('AJAX'); }
	public function get_request_refer() { return ZU_DebugBar::get_server_value('HTTP_REFERER'); }
	
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
		$form->checkbox('debug_js', 'Activate JS Debug info', 'Adds class <span>debug</span> to BODY and displays debug info for responsive elements.');

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

if(!function_exists('zu_write_log')) {
	function zu_write_log($msg, $var = 'novar') {
		if(zuplus_nodebug()) return;
		zuplus_instance()->dbug->write_log($msg, $var);
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
