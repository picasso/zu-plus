<?php
// include_once('/nas/content/live/photosafari/wp-content/plugins/zu-plus/zuplus-debug-sys.php');
// include_once('/nas/content/live/dmitryrudakov/wp-content/plugins/zu-plus/zuplus-debug-sys.php');
	
class ZU_Debug_Sys {

	private $location;
	private $location_priority;
	private $abs_path;
	private $content_path;

	protected static $ignore_class = [
		'wpdb'           		=> true,
		'QueryMonitor'   	=> true,
	];
	protected static $ignore_method = [
		'ZU_Debug'          => [
			'get_backtrace'			=> true, 
			'write_log'					=> true,
		],
		'ZU_Debug_Sys'    => [
			'get_backtrace'			=> true, 
			'write_log'					=> true,
		],
	];
	protected static $ignore_func = [
		'call_user_func_array' 	=> true,
		'call_user_func'       		=> true,
	];
	protected static $ignore_myself = [
		'_sdbug_log'         => true,
		'_dbug_log'           => true,
		'_dbug_log_if'		=> true,
		'_tbug_log'           	=> true,
		'_dbug_trace'		=> true,
		'_profiler_flag'      	=> true,
		'_ajax_log'           	=> true,
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
	
	function __construct($config) {
		
		$this->location = dirname(__FILE__);
		$this->location_priority = 0;
		$this->abs_path = $this->normalize_path(ABSPATH);
		$this->content_path = $this->normalize_path(dirname(WP_CONTENT_DIR) . '/wp-content/');
	}

	public function trailingslashit($string) {
		return rtrim( $string, '/\\' ) . '/';
	}
	
	public function normalize_path( $path ) {
		$path = str_replace( '\\', '/', $path );
		$path = preg_replace( '|(?<=.)/+|', '/', $path );
		if(':' === substr($path, 1, 1)) {
			$path = ucfirst( $path );
		}
		return $path;
	}

	public function clear_log($filename = 'debug.log') {
		$f =  $this->log_location($filename);
		unlink($f);
	}
		
	public function log_location($filename = 'debug.log') {
		return $this->trailingslashit($this->location).$filename;
	}

	public function change_log_location($path, $priority = 1) {
		if(stripos($path, '.php') !== false) $path = dirname($path);
		if($priority > $this->location_priority) { 
			$this->location = $this->trailingslashit($path);
			$this->location_priority = $priority;
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

	public function process_var($var) {		
		return var_export($var, true);
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

		$dir = $this->normalize_path($dir);
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
		
		error_log($msg.PHP_EOL, 3, $f);
	}
	
	public function write_log($msg, $var = 'novar', $bt = false) {
		
		$trace = $this->get_backtrace();
		$f =  $this->log_location();

		$tracelog = [];
		foreach($trace as $traceline) {
			$t_display = isset($traceline['display']) ? $traceline['display'] : 'unknown';
			$t_file = isset($traceline['calling_file']) ? $traceline['calling_file'] : 'unknown';
			$t_line = isset($traceline['calling_line']) ? $traceline['calling_line'] : 'unknown';
			
			$t_file = empty($t_file) ? 'closure' : $t_file;
			$t_line = $t_line == '0' ? '?' : $t_line;
			
			$tracelog[] = sprintf('%1$s%4$s		%2$s:%3$s%4$s',  $t_display, $t_file, $t_line, PHP_EOL);	
		}	
		
		if($bt) $refer = implode('', $tracelog);
		else $refer = sprintf('%1$s%4$s	%2$s:%3$s%4$s',  $trace[0]['display'], $trace[0]['calling_file'], $trace[0]['calling_line'], PHP_EOL);
		
		$msg = sprintf('%4$s[%1$s]		%3$s-- %2$s -------------------%4$s', 
			date('d.m H:i:s'), 
			str_replace('\n', PHP_EOL, $msg), 
			$refer, 
			PHP_EOL
		);
		
		if($var !== 'novar')
			$msg .= $this->process_var($var);
		if($bt) {
			$msg .= PHP_EOL.'backtrace:'.PHP_EOL.$this->string_backtrace();
		}
		
		error_log($msg.PHP_EOL, 3, $f);
	}
} 

function setup_debug() {
	global $_sys_debug;
	
	$_sys_debug = new ZU_Debug_Sys();
}
setup_debug();

if(!function_exists('_sdbug_change_log_location')) {
	function _sdbug_change_log_location($path, $priority = 1) {
		global $_sys_debug;
		if($_sys_debug) $_sys_debug->change_log_location($path, $priority);
	}
}

if(!function_exists('_sdbug_log')) {
	function _sdbug_log($msg, $var = 'novar', $bt = false) {
		global $_sys_debug;
		if($_sys_debug) $_sys_debug->write_log($msg, $var, $bt);
	}
}

if(!function_exists('_sdbug_trace')) {
	function _sdbug_trace($msg, $full_trace = false) {
				global $_sys_debug;
		if($_sys_debug) $_sys_debug->write_trace($msg, $full_trace);
	}
}
