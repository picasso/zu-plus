<?php

// Debug Output helpers -------------------------------------------------------]

trait zu_PlusDebugOutput {

	private $kint_version = '3.3';

	private function init_kint() {
		Kint::$return = true;
		Kint::$aliases[] = 'zu_log';
		Kint::$aliases[] = 'zu_logc';
		Kint::$aliases[] = 'zu_log_if';
		Kint::$aliases[] = ['zukit_Plugin', 'log'];
		Kint::$aliases[] = ['zukit_Plugin', 'logc'];
		Kint::$enabled_mode = $this->is_option('use_kint');
	}

	private function kint_log($args, $rich_mode = false) {
		$stash = Kint::$enabled_mode;
		Kint::$enabled_mode = $rich_mode ? Kint::MODE_RICH : Kint::MODE_TEXT;
		$log = call_user_func_array(['Kint', 'dump'], $args);
		if($args[0] === '!context!') {
			$hit_regex = '/┌─[\S|\s]*?!context![\'|\"]\n/m';
			$log = preg_replace($hit_regex, '', $log);
		}
		if($args[0] === '!condition hit!') {
			$hit_regex = '/┌─[\S|\s]*?!condition hit![\'|\"]/m';
			$log = preg_replace($hit_regex, '* * * conditionally logged * * *', $log);
		}
		// fix KINT JS to overcome 'important' priority
		if($rich_mode) {
			$js_regex = '/style\.display\s*=\s*["|\']block["|\']/m';
			$js_replace = 'style.setProperty("display", "block", "important")';
			$log = preg_replace($js_regex, $js_replace, $log);
			$log = preg_replace(
				str_replace('block', 'none', $js_regex),
				str_replace('block', 'none', $js_replace),
				$log
			);
		}
		Kint::$enabled_mode = $stash;
		return $log.PHP_EOL;
	}

	private function log_lineshift() {
		// if someone has registered an additional shift
		// plus two lines, which were introduced by this add-on
		return $this->plugin->debug_line_shift(null) + 2;
	}

	private function bar_log($params, $kint_log = false, $context = null, $called_class = null) {
		if($kint_log) $this->dbar->save($params, $context, null, $called_class);
		else {
			if($context) {
				$this->dbar->save($context);
			}
			$data = $this->plugin->get_log_data($params, $this->log_lineshift(), $context);
			foreach($data['args'] as $var) {
				$this->dbar->save($var['name'], $var['value'], $data['log_line'], $called_class);
			}
			return $data;
		}
		return null;
	}

	private function dump_value($var, $keep_tags = false) {
		$remove_refline_regex = '/\n*.*?\.php\s*:\s*\d+\s*:(?:<\/small>)*\n*/m';
		ob_start();
		var_dump($var);
		$output = ob_get_contents();
		ob_end_clean();

		if($keep_tags) return preg_replace($remove_refline_regex, '', $output);

		$text_output = html_entity_decode(strip_tags($output));
		$text_output = preg_replace($remove_refline_regex, '', $text_output);
		return preg_replace('/\n$/m', '', $text_output);
	}

	// public function write_ajax_log($data) {
	//
	// 	if(!$this->alog) return;
	//
	// 	$f = $this->log_location('ajax.log');
	// 	$ip = $this->get_request_ip();
	// 	$refer = str_replace(zu()->base_url(null, null, true)['host'], '', $this->get_request_refer());
	// 	$refer = preg_replace('/https?:\/\//i', '', $refer);
	// 	$refer = str_replace('<strong>-ajax-</strong> ', 'ajax:', urldecode($refer));
	//
	// 	$msg = sprintf('[%1$s] %3$s -------------------[%2$s]%4$s', date('d.m H:i:s'), $ip, $refer, PHP_EOL);
	// 	$msg .= $this->process_var($data);
	//
	// 	if($this->write_to_file) error_log($msg.PHP_EOL.PHP_EOL, 3, $f);
	// }


	// public function get_request_ip() { return zu_get_server_value('REMOTE_ADDR'); }
	// public function get_request_ajax() { return zu_get_server_value('AJAX'); }
	// public function get_request_refer() { return zu_get_server_value('HTTP_REFERER'); }
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
		$value = str_replace($_zu_debug_site_url, '', $value);
	}

	return $get_ajax ? $is_ajax : $value;
}
