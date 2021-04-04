<?php

class zu_PlusDebugBar extends zukit_Singleton {

	private $plugin = null;
	private $current_ip = '';
	private $convert_html_from_string = false;
	private $use_kint = true;
	private $cache_id = 'zu_debug_bar_dlogs';
	private $cache_time = HOUR_IN_SECONDS;

	private $dlogs = [];

	function config_singleton($params) {
		$this->convert_html_from_string = $params['output_html'] ?? $this->convert_html_from_string;
		$this->use_kint = $params['use_kint'] ?? $this->use_kint;
		$this->cache_time = $params['cache_time'] ?? $this->cache_time;
		add_filter('debug_bar_panels', [$this, 'debug_bar_panels'], 9000);
	}

	public function link($plugin) {
		$this->plugin = $plugin;
	}

	public function debug_bar_panels($panels) {
		require_once('debug-bar-panel.php');
		$panel = new zu_PlusDebugBarPanel('Zu Plus');
		$panel->set_callback([$this, 'panel_callback']);
		$panels[] = $panel;
		return $panels;
	}

	// Show the contents of the page
	public function panel_callback() {
		$this->current_ip = $this->get_request_ip();
		$this->dlogs = $this->get_logs();

		$logkind = zu_sprintf(
			'<span class="qm-nonselectsql zuplus_blue"><b>%s</b> %s</span>',
			empty($this->dlogs) ? 'No logs found.' : ($this->use_kint ? 'KINT' : 'Zu Plus'),
			empty($this->dlogs) ? '' : ' based'
		);
		zu_printf('<div class="clear"></div><h3 id="dr-custom-logs" class="qm-true">Debug Logs: %s</h3>', $logkind);
		if(!empty($this->dlogs)) $this->display_logs();

		$this->reset_logs();
	}

	public function save($log_name, $log_value = '$undefined', $log_refer = null, $log_ip = null) {
		if($this->use_kint) {
			// add some info about 'called' point
			$loginfo = sprintf(
				'$1<em class="__func">$2()</em>] → %s : <em class="__ip">%s</em>%s',
				date('d.m H:i:s', time()),
				$this->get_request_ip(),
				is_null($log_ip) ? '' : sprintf(' : { <em class="__class">class %s</em> }', $log_ip)
			);
			$log_name = preg_replace('/(Called from.*?)zukit_Plugin(->log[c|d]?)\(\)\]/m', $loginfo, $log_name);
			$log_name = preg_replace('/(Called from.*?)(zu_log[c|d]?)\(\)\]/m', $loginfo, $log_name);

			// deal with context ('$log_refer' contains $context value)
			if($log_value === '$context') {
				$is_error = substr($log_refer, 0, 1) === '!';
				$is_warning = substr($log_refer, 0, 1) === '?';
				$is_highlight = substr($log_refer, 0, 1) === '*';
				$context_class = $is_error ? '__err' : ($is_warning ? '__warn' : ($is_highlight ? '__hlt' : '__dbg'));			
				$log_name = str_replace($log_refer, '#context#', $log_name);
				$log_name = preg_replace(
					'/<dt>.*?#context#["|\']*/m',
					sprintf(
						'<dt class="__context %s">%s',
						$context_class,
						preg_replace('/^[!|?|*]/', '', $log_refer)
					),
					$log_name
				);
			}
			$this->add_log($log_name);
			return;
		}
		$log = [];
		// deal with context
		if($log_value === '$undefined') {
			$log['name'] = null;
			$log['value'] = $log_name;
			$log['refer'] = $log_refer;
		} else {
			$log['time'] = time();
			$log['name'] = $log_name;
			$log['value'] = $log_value;
			$log['ip'] = empty($log_ip) ? $this->get_request_ip() : $log_ip;
			$log['refer'] = empty($log_refer) ? $this->get_request_refer() : preg_replace('/^DEBUG:\s*/', '', $log_refer) ;
			if($log_value === '$undefined' && is_null($log['refer'])) $log['value'] = $_REQUEST;
		}

		$this->add_log($log);
	}


	private function display_logs() {
		if(empty($this->dlogs)) return;

		if($this->use_kint) {
			foreach($this->dlogs as $row) {
				// maybe there were Zu records
				if(is_array($row)) continue;
				print($row);
			}
			return;
		}

		zu_printf(
			'<table cellspacing="0">
				<thead>
					<tr>
						<th class="">Time</th>
						<th class="">Name</th>
						<th class="">Value</th>
						<th class="">File:line</th>
						<th class="">IP</th>
					</tr>
				</thead>'
		);

		$prev_ref = null;
		foreach($this->dlogs as $row) {

			// maybe there were KINT records
			if(!is_array($row)) continue;

			// deal with context
			if($row['name'] === null) {
				zu_printf(
					'<tr class="qm-warn">
						<td class="qm-none qm-nonselectsql" colspan="5"><p><strong>%1$s</strong></p></td>
					</tr>',
					$row['value'],
				);
				continue;
			}

			$name = preg_replace('/\n+/', '', $row['name']);
			$print_value = var_export($row['value'], true);
			$template = (is_array($row['value']) || is_object($row['value'])) ? '<pre>%1$s</pre>' : '%1$s';

			// if contains HTML - convert all applicable characters
			if($this->convert_html_from_string) {
				$print_value = (is_string($print_value) && $print_value !== strip_tags($print_value)) ?
					htmlentities($print_value) :
					$print_value;
			}

			$print_value = sprintf($template, $print_value);
			// to keep RAW  translated field in output (qtranslate-xt)
			$print_value = preg_replace('/\[\:([^\]]*)\]/', '{$1}', $print_value);

			$same_call = $row['refer'] === $prev_ref;

			zu_printf(
				'<tr>
					<td class="qm-ltr qm-false"><strong>%1$s</strong></td>
					<td class="qm-ltr qm-true">%4$s</td>
					<td class="qm-ltr qm-notice">%5$s</td>
					<td class="qm-ltr">%3$s</td>
					<td class="qm-ltr %6$s">%2$s</td>
				</tr>',
				$same_call ? '' : date('d.m H:i:s', $row['time']),
				$row['ip'],
				$same_call ? '&nbsp;&nbsp;- " - " -' : $row['refer'],
				$name,
				$print_value,
				$this->get_display_class($row)
			);
			$prev_ref = $row['refer'];
		}

		print('</table>');
	}

	private function get_display_class($row) {
		$class = $row['ip'] == $this->current_ip ? 'qm-current' : '';
		$class = stripos($row['refer'], '-ajax-') !== false ? 'qm-false' : $class;
		return $class;
	}

	private function add_log($log) {
		$values = get_transient($this->cache_id);
		$values = empty($values) ? [] : $values;
		$values[] = $log;
		set_transient($this->cache_id, $values, $this->cache_time);
	}

	private function get_logs() {
		$values = get_transient($this->cache_id);
		return ($values === false) ? [] : $values;
	}

	public function reset_logs() {
		return delete_transient($this->cache_id);
	}

	private function get_request_ip() { return zu_get_server_value('REMOTE_ADDR'); }
	private function get_request_ajax() { return zu_get_server_value('AJAX'); }
	private function get_request_refer() { return zu_get_server_value('HTTP_REFERER'); }

	// for internal debugging only
	private function logd($info, $value) {
		$this->plugin->logd($info, $value);
	}
}
