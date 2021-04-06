<?php

class zu_PlusDebugBar extends zukit_Singleton {

	private $parent = null;
	private $current_ip = '';
	private $css_logs_id = 'zu-custom-logs';
	private $convert_html_from_string = false;
	private $dump_method = 'var_export';
	private $use_kint = true;

	private static $cache_time = HOUR_IN_SECONDS;
	private static $cache_id = 'zu_debug_bar_dlogs';

	private $dlogs = [];

	function config_singleton($params) {
		$this->convert_html_from_string = $params['convert_html'] ?? $this->convert_html_from_string;
		$this->use_kint = $params['use_kint'] ?? $this->use_kint;
		$this->dump_method = $params['dump_method'] ?? $this->dump_method;
		self::$cache_time = $params['cache_time'] ?? self::$cache_time;
		add_filter('debug_bar_panels', [$this, 'debug_bar_panels'], 9000);
	}

	public function link($parent) {
		$this->parent = $parent;
	}

	public function debug_bar_panels($panels) {
		require_once('debug-bar-panel.php');
		$panel = new zu_PlusDebugBarPanel('Zu Plus');
		$panel->set_callback([$this, 'panel_callback']);
		$panels[] = $panel;
// zu_logc('*Object test', $panel);
		return $panels;
	}

	// show the contents of the page
	public function panel_callback() {
		$this->current_ip = $this->get_request_ip();
		$this->dlogs = self::get_logs();

		$logkind = zu_sprintf(
			'<em>%s</em> %s',
			empty($this->dlogs) ? 'No logs found.' : ($this->use_kint ? 'KINT' : 'Zu Plus'),
			empty($this->dlogs) ? '' : ' based'
		);

		zu_printf(
			'<div id="%s">
				<div class="qm-boxed">
					<div class="qm-notice">Debug Logs: %s</div>
				</div>',
			$this->css_logs_id,
			$logkind
		);
		if(!empty($this->dlogs)) $this->display_logs();
		print('</div');

		self::reset_logs();
	}

	public function save($log_name, $log_value = '$undefined', $log_refer = null, $log_class = null) {
		if($this->use_kint) {
			$log = $this->get_formatted_refer($log_name, $log_class);
			// deal with context ('$log_value' contains $context value)
			if($log_value !== null) $log = $this->get_formatted_context($log_value, $log);
			self::add_log($log);
			return;
		}
		$log = [];
		// deal with context
		if($log_value === '$undefined') {
			$log['name'] = null;
			$log['value'] = $this->get_formatted_context($log_name);
			$log['refer'] = $log_refer;
		} else {
			$log['time'] = time();
			$log['name'] = $log_name;
			$log['value'] = $log_value;
			$log['class'] = $log_class;
			$log['ip'] = $this->get_request_ip();
			$log['refer'] = $this->get_formatted_refer($log_refer, null, true);
			if($log_value === '$undefined' && is_null($log['refer'])) $log['value'] = $_REQUEST;
		}
		self::add_log($log);
	}

	private function display_logs() {
		if(empty($this->dlogs)) return;

		if($this->use_kint) {
			foreach($this->dlogs as $row) {
				// maybe there were 'Zu Plus' records
				if(is_array($row)) continue;
				print($row);
			}
			return;
		}

		zu_printf(
			'<table cellspacing="0">
				<thead>
					<tr>
						<th class="">Name</th>
						<th class="">Value</th>
						<th class="">From</th>
						<th class="">File:line</th>
						<th class="">Time</th>
						<th class="">IP</th>
					</tr>
				</thead>'
		);

		$prev_ref = null;
		$index = 0;
		foreach($this->dlogs as $row) {

			// maybe there were 'KINT' records
			if(!is_array($row)) continue;

			// deal with context
			if($row['name'] === null) {
				zu_printf(
					'<tr>
						<td class="qm-none" colspan="6">%1$s</td>
					</tr>',
					$row['value'],
				);
				$index = 0;
				continue;
			}

			$name = preg_replace('/\n+/', '', $row['name']);
			$print_value = $this->get_formatted_value($row['value']);
			$same_call = $row['refer'] === $prev_ref;

			zu_printf(
				'<tr class="%8$s">
					<td class="qm-ltr"><span class="__var">%4$s</span></td>
					<td class="qm-ltr __value">%5$s</td>
					<td class="qm-ltr __class">%7$s</td>
					<td class="qm-ltr __file">%3$s</td>
					<td class="qm-ltr __time">%1$s</td>
					<td class="qm-ltr __ip %6$s">%2$s</td>
				</tr>',
				$same_call ? '' : date('H:i:s d.m', $row['time']),
				$row['ip'],
				$same_call ? '' : $row['refer'],
				$name,
				$print_value,
				$this->get_display_class($row),
				$row['class'] ?? '',
				$index % 2 === 0 ? '' : 'qm-odd'
			);
			$prev_ref = $row['refer'];
			$index++;
		}
		print('</table>');
	}

	// NOTE: не уверен что нужна эта бодяга с IP - может удалить?
	private function get_display_class($row) {
		$class = $row['ip'] == $this->current_ip ? 'qm-current' : '';
		$class = stripos($row['refer'], '-ajax-') !== false ? 'qm-false' : $class;
		return $class;
	}

	private function get_formatted_context($context, $source = null) {
		if(is_null($source)) $source = sprintf('<dt>%s</dt>', $context);
		$is_error = substr($context, 0, 1) === '!';
		$is_warning = substr($context, 0, 1) === '?';
		$is_highlight = substr($context, 0, 1) === '*';
		$context_class = $is_error ? '__err' : ($is_warning ? '__warn' : ($is_highlight ? '__hlt' : '__dbg'));
		$source = str_replace($context, '#context#', $source);
		$source = preg_replace(
			'/<dt>.*?#context#["|\']*/m',
			sprintf(
				'<dt class="__context %s">%s',
				$context_class,
				preg_replace('/^[!|?|*]/', '', $context)
			),
			$source
		);
		return $source;
	}

	private function get_formatted_value($value) {
		$print_value = $this->parent->dump($value, true);
		// do nothing for 'dump_var' method
		if($this->dump_method === 'dump_var') return $print_value;

		$template = (is_array($value) || is_object($value)) ? '<pre>%1$s</pre>' : '%1$s';

		// if contains HTML - convert all applicable characters
		if($this->convert_html_from_string) {
			$print_value = (is_string($print_value) && $print_value !== strip_tags($print_value)) ?
				htmlentities($print_value) :
				$print_value;
		}

		// highlight array keys
		$array_regex = '/\'([^\']+)\'(\s*?=)(&gt;|>)/m';
		if($this->dump_method === 'print_r') $array_regex = str_replace('\'([^\']+)\'', '\[([^\]]+)\]', $array_regex);
		if(is_array($value) || is_object($value)) $print_value = preg_replace($array_regex, '<em>$1</em>$2$3', $print_value);

		// to keep RAW  translated field in output (qtranslate-xt)
		$print_value = preg_replace('/\[\:([^\]]*)\]/', '{$1}', $print_value);

		return sprintf($template, $print_value);
	}

	private function get_formatted_refer($source, $log_class, $source_line = false) {
		// for string with file and line
		if($source_line) {
			return empty($source) ? $this->get_request_refer() : preg_replace('/^(.*?)\[([^]]+)\]/', '..$2', $source);
		}
		// for KINT source
		// add some info about 'called' point
		$loginfo = sprintf(
			'$1<em class="__func">$2()</em>] → %s : <em class="__ip">%s</em>%s',
			date('H:i:s d.m', time()),
			$this->get_request_ip(),
			is_null($log_class) ? '' : sprintf(' : { <em class="__class">class %s</em> }', $log_class)
		);
		$source = preg_replace('/(Called from.*?)zukit_Plugin(->log[c|d]?)\(\)\]/m', $loginfo, $source);
		$source = preg_replace('/(Called from.*?)(zu_log[c|d]?)\(\)\]/m', $loginfo, $source);

		return $source;
	}

	// working with cache -----------------------------------------------------]

	private static function add_log($log) {
		$values = get_transient(self::$cache_id);
		$values = empty($values) ? [] : $values;
		$values[] = $log;
		set_transient(self::$cache_id, $values, self::$cache_time);
	}

	private static function get_logs() {
		$values = get_transient(self::$cache_id);
		return ($values === false) ? [] : $values;
	}

	public static function reset_logs() {
		return delete_transient(self::$cache_id);
	}

	private function get_request_ip() { return zu_get_server_value('REMOTE_ADDR'); }
	private function get_request_ajax() { return zu_get_server_value('AJAX'); }
	private function get_request_refer() { return zu_get_server_value('HTTP_REFERER'); }

	// for internal debugging only
	private function logd($info, $value) {
		$this->parent->logd($info, $value);
	}
}
