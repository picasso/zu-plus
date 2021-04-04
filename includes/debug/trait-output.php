<?php

// Debug Output helpers -------------------------------------------------------]

trait zu_PlusDebugOutput {

	private $kint_version = '3.3';

	private function init_kint() {
		Kint::$return = true;
		Kint::$aliases[] = 'zu_log';
		Kint::$aliases[] = 'zu_logc';
		Kint::$aliases[] = ['zukit_Plugin', 'log'];
		Kint::$aliases[] = ['zukit_Plugin', 'logc'];
		Kint::$enabled_mode = $this->is_option('use_kint');
	}

	private function kint_log($args, $rich_mode = false) {
		$stash = Kint::$enabled_mode;
		Kint::$enabled_mode = $rich_mode ? Kint::MODE_RICH : Kint::MODE_TEXT;
		$log = call_user_func_array(['Kint', 'dump'], $args);
		Kint::$enabled_mode = $stash;
		return $log;
	}

	public function savelog($val) {
		$this->dbar->save($val);
	}

	private function bar_log($params, $kint_log = false, $context = null, $called_class = null) {
		if($kint_log) $this->dbar->save($params, '$context', $context, $called_class);
		else {
			if($context) {
				$this->dbar->save($context);
			}
			$data = $this->plugin->get_log_data($params, 2);
			foreach($data['args'] as $var) {
				$this->dbar->save($var['name'], $var['value'], $data['log_line']);
			}
			return $data;
		}
		return null;
	}

	public function get_request_ip() { return zu_get_server_value('REMOTE_ADDR'); }
	public function get_request_ajax() { return zu_get_server_value('AJAX'); }
	public function get_request_refer() { return zu_get_server_value('HTTP_REFERER'); }
}

function zu_get_server_value($name) {
	global $_zu_debug_site_url;

	if(empty($_debug_site_url)) $_zu_debug_site_url = get_bloginfo('url');

	$get_ajax = preg_match('/AJAX/i', $name) ? true : false;
	$request = $_SERVER['REQUEST_URI'] ?? '';
	$value = $_SERVER[$name] ?? '';
	$is_ajax = stripos($request, 'admin-ajax.php') !== false;

	if($name == 'HTTP_REFERER') {

		if(empty($value) || $is_ajax)	{
			if(in_array('doing_wp_cron', array_keys($_REQUEST))) $new_value = 'doing_wp_cron';
			else if(isset($_REQUEST['data'])) $new_value = array_keys($_REQUEST['data'])[0];
			else if(isset($_REQUEST['action'])) $new_value = $_REQUEST['action'];
		}

		if($is_ajax && empty($new_value)) return null;

		$value = $is_ajax ? $value : (empty($new_value) ? $request : '');
		$value = trim(sprintf('%1$s %2$s %3$s',  $value, $is_ajax ? '<strong>-ajax-</strong>' : '', empty($new_value) ? '' : $new_value));
		$value =  str_replace($_zu_debug_site_url, '', $value);
	}

	return $get_ajax ? $is_ajax : $value;
}


Kint::$aliases[] = ['self', 'dbg'];
Kint::$aliases[] = ['KintTest', 'dbg'];
Kint::$aliases[] = ['KintTestPlus', 'dbg'];

class KintTest {
	public static function dbg(...$vars) {
		Kint::$return = true;
		$stash = Kint::$enabled_mode;
		Kint::$enabled_mode = Kint::MODE_RICH;
		$log = Kint::dump(...$vars);
		Kint::$enabled_mode = $stash;
		return $log;
	}

	public function test($call) {

		$time = time();
		$data = [
		    'debug_mode' => true,
		    'remove_autosave' => false,
		    'cookie_notice' => false,
		    'dup_page' => false,
		    'disable_cached' => false,
		    '_debug' => [
		        'refresh' => false,
		    ],
		];

		$log = self::dbg($time, $data);
		call_user_func_array($call, [$log]);
	}
}

class KintTestPlus extends KintTest {
	public static function dbg(...$vars) {
		Kint::$return = true;
		$stash = Kint::$enabled_mode;
		Kint::$enabled_mode = Kint::MODE_RICH;
		$log = Kint::dump('KintTestPlus $log', ...$vars);
		Kint::$enabled_mode = $stash;
		return $log;
	}

	public function test2($call) {

		$time = time();
		$data = [
		    'debug_mode' => true,
		    'remove_autosave' => false,
		    'cookie_notice' => false,
		    'dup_page' => false,
		    'disable_cached' => false,
		    '_debug' => [
		        'refresh' => false,
		    ],
		];

		$log = KintTestPlus::dbg($time, $data);
		call_user_func_array($call, [$log]);
	}
}



trait zu_PlusDebugOutput_obsolete {

	private $alog;
	private $profiler;

	private $use_backtrace;
	private $write_to_file;
	private $beautify_html;
	private $output_html;
	public $use_kint;

	private $dbar;
	private $use_var_dump;
	private $location;
	private $location_priority;
	private $abs_path;
	private $content_path;

	protected static $ignore_class = [
		'wpdb'           	=> true,
		'QueryMonitor'   	=> true,
	];
	protected static $ignore_method = [
		'ZU_Debug'          => [
			'get_backtrace'			=> true,
			'write_log'				=> true,
		],
	];
	protected static $ignore_func = [
		'call_user_func_array' 	=> true,
		'call_user_func'       	=> true,
	];
	protected static $ignore_myself = [
		'zu_write_log'   				=> true,
		'_dbug_change_log_location'		=> true,
		'_dbug_log'           			=> true,
		'_dbug_log_only'   				=> true,
		'_dbug_dump'   					=> true,
		'_dbug_use_var_dump'  			=> true,
		'_dbug_log_if'					=> true,
		'_tbug_log'           			=> true,
		'_dbug_trace'					=> true,
		'_profiler_flag'      			=> true,
		'_ajax_log'           			=> true,
	];
	protected static $ignore_includes = [
		'include_once'       => true,
		'require_once'       => true,
		'include'            => true,
		'require'            => true,
	];
	protected static $show_args = [
		'do_action'            => 1,
		'apply_filters'        => 1,
	];

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

		if($save_debug_bar) $this->save($msg, $var, $ip, $refer_html);

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

}
