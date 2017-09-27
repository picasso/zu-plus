<?php
if(!defined('ABSPATH')) die();

class ZU_DebugBar {

	private static $_debug_bar_instance;
	private $_profiler_start;
	private $_profiler_stop;
	private $_profiler_timing = [];
	private $_profiler_names = [];
	private $_profiler = [];
	private $_current_ip = '';
	private $profiler_active;
	
	private $_dlogs = [];
	private $_all_users = [];

	public static function instance() {
		if(!isset(self::$_debug_bar_instance)) {
			$class_name = __CLASS__;
			self::$_debug_bar_instance = new $class_name;
		}
		return self::$_debug_bar_instance;
	}

	public static function get_server_value($name) {
		
		$get_ajax = preg_match('/AJAX/i', $name) ? true : false;
		$request =  isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
		$value = isset($_SERVER[$name]) ? $_SERVER[$name] : '';
		$is_ajax = stripos($request, 'admin-ajax.php') !== false ? true : false;
		
		if($name == 'HTTP_REFERER') { 
			
			if(empty($value) || $is_ajax)	{
				if(in_array('doing_wp_cron', array_keys($_REQUEST))) $new_value = 'doing_wp_cron';
				else if(isset($_REQUEST['data'])) $new_value = array_keys($_REQUEST['data'])[0];
				else if(isset($_REQUEST['action'])) $new_value = $_REQUEST['action'];
			}	
			
			if($is_ajax && empty($new_value)) return null;
			
			$value = $is_ajax ? $value : (empty($new_value) ? $request : '');
			$value = trim(sprintf('%1$s %2$s %3$s',  $value, $is_ajax ? '<strong>-ajax-</strong>' : '', empty($new_value) ? '' : $new_value));
			$value =  str_replace('https://truewine.ru', '', $value);
			$value =  str_replace('/wp-admin/', '', $value);
		}
		
		return $get_ajax ? $is_ajax : $value;
	}

	private function __clone() {} 														// Method to keep our instance from being cloned.
	private function __wakeup() {} 													// Method to keep our instance from being unserialized.

	function __construct($activate_profiler = true) {
		
		self::$_debug_bar_instance = $this;
		$this->_profiler_start = microtime(true);
		$this->profiler_active = $activate_profiler;
		add_filter('debug_bar_panels', [$this, 'debug_bar_panels'], 9000);
	}

	public function get_request_ip() { return self::get_server_value('REMOTE_ADDR'); }

	public function get_request_ajax() { return self::get_server_value('AJAX'); }

	public function get_request_refer() { return self::get_server_value('HTTP_REFERER'); }

	public function debug_bar_panels($panels) {
		require_once(dirname( __FILE__ ) . '/debug-bar-panel.php');
		$panel = new ZU_DebugBarPanel('ZU Debugs');
		$panel->set_callback([$this, 'panel_callback']);
		$panels[] = $panel;
		return $panels;
	}

	// Show the contents of the page
	public function panel_callback() {

		// Hack wp_footer: this callback is executed late into wp_footer, but not after, so
		// let's assume it is the last call in wp_footer and manually stop the timer, otherwise
		// we won't get a wp_footer entry in the output.
		
		$this->set_profiler_flag('stop');
		$this->_current_ip = $this->get_request_ip();
		
		$full_time  = microtime(true) - $this->_profiler_start;
		if($this->profiler_active) $this->save_log('Printing!', $this->convert_time($full_time));
		
		printf('<h3><span class="qm-nonselectsql">%s:</span> <strong>%s</strong></h3>', 'Total Profiler', $this->convert_time($full_time));

		$this->_profiler = $this->get_profiler();
		$this->_dlogs = $this->get_logs();
		
		if($this->profiler_active) {
			printf('<div class="clear"></div><h3 id="dr-custom-profiler" class="qm-warn">Custom Profiler%s</h3>', empty($this->_profiler) ? ': <span class="qm-nonselectsql zuplus_blue">No profiler data found.</span>' : '');
			if(!empty($this->_profiler)) $this->display_profiler();
		}
		
		printf('<div class="clear"></div><h3 id="dr-custom-logs" class="qm-true">Debug Logs%s</h3>', empty($this->_dlogs) ? ': <span class="qm-nonselectsql zuplus_blue">No logs found.</span>' : '');
		if(!empty($this->_dlogs)) $this->display_logs();
		
		$this->reset_logs();
		$this->reset_profiler();
	}

	// Call this at each point of interest, passing a descriptive string
	public function set_profiler_flag($flag_name, $flag_ip = null, $flag_refer = null) {
		
// 		if($this->get_request_ajax()) return;
	
		$check_time = microtime(true);
		
		$profile = [];
		$profile['to'] = $flag_name;
		$profile['ip'] = empty($flag_ip) ? $this->get_request_ip() : $flag_ip;
		$profile['refer'] = empty($flag_refer) ? $this->get_request_refer() : $flag_refer;
		$profile['full_time'] = $check_time - $this->_profiler_start;

		if(!empty($this->_profiler_timing)) {
			
			$profile['from'] = array_slice($this->_profiler_names, -1)[0];
			$profile['time'] = $check_time - array_slice($this->_profiler_timing, -1)[0];
		} else {
			$profile['from'] = 'start';
			$profile['time'] = $check_time - $this->_profiler_start;
		}
		
		$this->add_profile($profile);
	    $this->_profiler_timing[] = $check_time;
	    $this->_profiler_names[] = $flag_name;
	}

	public function save_log($log_name, $log_value = '', $log_ip = null, $log_refer = null) {
		
// 		if($this->get_request_ajax()) return;
		
		$log = [];
		$log['time'] = time();
		$log['name'] = $log_name;
		$log['value'] = $log_value;
		$log['ip'] = empty($log_ip) ? $this->get_request_ip() : $log_ip;
		$log['refer'] = empty($log_refer) ? $this->get_request_refer() : str_replace(PHP_EOL, '<br />', $log_refer) ;

		if(empty($log_value) && is_null($log['refer'])) $log['value'] = $_REQUEST;

		$this->add_log($log);
	}


	private function get_display_class($row) {
	
		$class = $row['ip'] == $this->_current_ip ? 'qm-current' : '';
		$class = stripos($row['refer'], '-ajax-') !== false ? 'qm-false' : $class;
		return $class;
	}

	// Call this when you're done and want to see the results
	private function display_profiler() {

		if(empty($this->_profiler)) return;

		printf('<table cellspacing="0">
					<thead>
						<tr>
							<th class="">IP</th>
							<th class="">Refer</th>
							<th class="">From</th>
							<th class="">To</th>
							<th class="">Time</th>
							<th class="">From Start</th>
						</tr>
					</thead>'
		);

		foreach($this->_profiler as $row) {
			
			printf(
				'<tr>
					<td class="qm-ltr %7$s">%4$s</td>
					<td class="qm-ltr %7$s">%5$s</td>
					<td class="qm-ltr %7$s">%1$s</td>
					<td class="qm-ltr %7$s">%2$s</td>
					<td class="qm-ltr %7$s">%3$s</td>
					<td class="qm-ltr %7$s">%6$s</td>
				</tr>', 
				$row['from'], 
				$row['to'], 
				$this->convert_time($row['time']),
				$row['ip'], 
				$row['refer'],
				$this->convert_time($row['full_time']),
				$this->get_display_class($row)
			);
		}

		printf('</table>');
	}

	private function convert_time($time) {
	
		$time *= 1000;
		$atts = 'ms';
		$class = '';
		
		if($time > 500) {
			$time /= 1000;
			$atts = '<strong>s</strong>';
			$class = 'qm-warn';
		}
	
		return sprintf('<span class="%3$s">%1$.2f%2$s</span>', $time, $atts, $class);
	}

	private function get_all_users() {

		if(empty($this->_all_users)) {
/*
			global $wpdb;
	
			$transients = $wpdb->get_results(
				"SELECT option_name AS name, option_value AS value FROM $wpdb->options WHERE option_name LIKE '_transient_%'"
			);
	
			array_walk($transients, array($this, '_format_transient'));
			unset($transients);
*/
		}

		return $this->_all_users;
	}

	private function display_logs() {

		if(empty($this->_dlogs)) return;

		printf('<table cellspacing="0">
					<thead>
						<tr>
							<th class="">Time</th>
							<th class="">IP</th>
							<th class="">Refer</th>
							<th class="">Name</th>
							<th class="">Value</th>
						</tr>
					</thead>'
		);
		
		foreach($this->_dlogs as $row) {

			$name = preg_replace('/\n+/', '', $row['name']);
			$name = trim(preg_replace('/=$/', '', $name));
			
			$template = (stripos($row['value'], 'array') !== false || stripos($row['value'], '::') !== false) ? '<pre>%1$s</pre>' : '%1$s';
			$value = sprintf($template, print_r($row['value'], true));
			
			printf(
				'<tr>
					<td class="qm-ltr qm-false"><strong>%1$s</strong></td>
					<td class="qm-ltr %6$s">%2$s</td>
					<td class="qm-ltr">%3$s</td>
					<td class="qm-ltr qm-true">%4$s</td>
					<td class="qm-ltr qm-warn">%5$s</td>
				</tr>', 
				date('d.m H:i:s', $row['time']),
				$row['ip'],
				$row['refer'],
				$name,
				$value,
				$this->get_display_class($row)
			);
		}

		printf('</table>');

/*
		$delete_link = sprintf(
			'<span><a class="delete" data-transient-type="%s" data-transient-name="$" title="%s" href="#">%s</a></span>',
			($site_transient ? 'site' : ''),
			__('Delete this transient (No undo!)', 'debug-bar-transients'),
			__('Delete', 'debug-bar-transients')
		);

		$switch_link = sprintf(
			'<span class="switch-value"><a title="%s" href="#">%s</a></span>',
			__('Switch between serialized and unserialized view', 'debug-bar-transients'),
			__('Switch value view', 'debug-bar-transients')
		);

		foreach($logs as $record => $data) {
			if(isset($data['value'])) {
				echo '<tr>';
			} else {
				echo '<tr class="transient-error">';
			}
			echo '<td>' . $transient . '<div class="row-actions">' . str_replace('$', $transient, $delete_link) . (isset($data['value']) ? ' | ' . $switch_link : '') . '</div></td>';
			if(isset($data['value'])) {
				echo '<td><pre class="serialized" title="' .  __('Click to expand', 'debug-bar-transients') . '">' . esc_html($data['value']) . '</pre><pre class="unserialized" title="' .  __('Click to expand') . '">' . esc_html(print_r(maybe_unserialize($data['value']), true)) . '</pre></td>';
			} else {
				echo '<td><p>' . __('Invalid transient - the transient name was probably truncated. Limit is 64 characters.', 'debug-bar-transients') . '</p></td>';
			}
			echo '<td>' . $this->_print_timeout($data)  . '</td>';
			echo '</tr>';
		}
*/
	}

	private function add_log($log) { $this->set_cache('_dlogs', $log); }
	private function add_profile($profile) { $this->set_cache('_profiler', $profile); }

	private function get_logs() { return $this->get_cache('_dlogs'); }
	private function get_profiler() { return $this->get_cache('_profiler'); }

	private function reset_logs() { return $this->delete_cache('_dlogs'); }
	private function reset_profiler() { return $this->delete_cache('_profiler'); }
	
	private function set_cache($cache_id, $value) {
		
		$values = get_transient('zu_debug_bar_'.$cache_id);
		$values = empty($values) ? [] : $values;
		$values[] = $value;
		set_transient('zu_debug_bar_'.$cache_id, $values, HOUR_IN_SECONDS);
	}

	private function get_cache($cache_id) {
		$values = get_transient('zu_debug_bar_'.$cache_id);
		return ($values === false) ? [] : $values;
	}

	private function delete_cache($cache_id) {
		delete_transient('zu_debug_bar_'.$cache_id);
	}
}
