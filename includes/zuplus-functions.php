<?php

class ZU_PlusFunctions {

	private static $_zufunc_instance;

	private $theme_version = null;
	private $random_attachment_id = null;
	private $advanced_style = [];
	private $admin_style = [];
	private $fonts = [];
	private $copy_string = '';
	private $cache_time = HOUR_IN_SECONDS * 12; 	// cache them for 12 hours (recommended)
	private $debug_cache = true;

	public static function instance() {
		if(!isset(self::$_zufunc_instance)) {
			$class_name = __CLASS__;
			self::$_zufunc_instance = new $class_name;
		}
		return self::$_zufunc_instance;
	}

	// Cache functions -----------------------------------------------------------]

	public function get_theme_version() {

		if(!empty($this->theme_version)) return $this->theme_version;

		$theme_info = wp_get_theme();
		$this->theme_version = $theme_info->display('Version');
		return $this->theme_version;
	}

	public function array_md5($array, $only_json = false) {

		// https://stackoverflow.com/questions/2254220/php-best-way-to-md5-multi-dimensional-array
	    // since we're inside a function (which uses a copied array, not
	    // a referenced array), you shouldn't need to copy the array
	    array_multisort($array);
	    return $only_json ? json_encode($array) : md5(json_encode($array));
	}

	public function set_debug_cache($value) {

		$this->debug_cache = $value;
	}

	public function create_cachekey($prefix, $array = [], $string = '') {

		$array = is_array($array) ? $array : [];
		$array['zuplus_version'] = sprintf('%1$s_%2$s', ZUPLUS_VERSION, $this->get_theme_version());

		if(!empty($string)) $array[$prefix.'_md5_strings'] = explode(' ', trim($string));

		if($this->debug_cache) {
			zu_write_log('Create Cachekey=', ['cache_id' => sprintf('zu_%1$s_%2$s', $prefix, $this->array_md5($array)), 'array' => $array]);
		}

		return sprintf('zu_%1$s_%2$s', $prefix, $this->array_md5($array));
	}

	public function get_cached($cache_id) {

		$cached = get_transient($cache_id);

		if($this->debug_cache) zu_write_log('Get Cachekey=', ['cache_id' => $cache_id, 'cached' => $cached === false ? 'NOT FOUND' : 'found: ~'.$this->get_cached_size($cached)]);

		return $cached === false ? '' : $cached;
	}

	public function get_cached_size($cached, $formated = true) {
		$size = is_string($cached) ? strlen($cached) : strlen(serialize($cached));
		return $formated ? $this->format_bytes($size) : $size;
	}

	public function set_cached($cache_id, $data, $format = false) {

		if($format == 'html') $data = $this->minify_html($data);
		if($format == 'css') $data = $this->minify_css($data);

		if($this->debug_cache) zu_write_log('Set Cached=', ['cache_id' => $cache_id, 'size' => $this->get_cached_size($data)]);

		set_transient($cache_id, $data, $this->cache_time);
	}

	public function purge_transients($prefix = 'zu_') {
		global $wpdb;

		// Purge all the transients associated with our prefix

		$prefix = esc_sql($prefix);
		$options = $wpdb -> options;

		$t  = esc_sql("_transient_timeout_$prefix%");
		$sql = $wpdb->prepare("SELECT option_name FROM $options WHERE option_name LIKE '%s'", $t);

		$transients = $wpdb->get_col($sql);
		$count = 0;
		foreach($transients as $transient) {
			$key = str_replace('_transient_timeout_', '', $transient);  		// Strip away the WordPress prefix in order to arrive at the transient key.
			if(delete_transient($key)) {													// Now that we have the key, use WordPress core to the delete the transient.
				$count++;
				if($this->debug_cache) zu_write_log('Deleted Cached=', $key);
			}
		}

		wp_cache_flush();																		// Sometimes transients are not in the DB, so we have to do this too
		return $count;
	}

	// Color functions -----------------------------------------------------------]

	public function get_svgcurve($look = 'upright', $height = 100, $class = '', $html_id = '') {

	// 	to use 'custom curve' you need add_filter('zu_custom_curve', 'your_function', 10, 2);
	// 	args: $curve,  $height

		$height = intval(str_replace('px', '', $height));

		switch($look) {

			case 'downleftinverse':
				$path = sprintf('M100,0 L100,%1$s L0,%1$s L0,0 L100,0 z M100,0 L0,0 C20,135 50,0 100,0 z', $height);
				break;

		    case 'upright':
		    	$path = sprintf('M0 %1$s C 50 0 80 -%2$s 100 %1$s Z', $height, intval($height/3));
				break;

		    case 'upleft':
		    	$path = sprintf('M0 %1$s C 20 -%2$s 30 0 100 %1$s Z', $height, intval($height/3));
				break;

		    case 'downleft':
		    	$path = sprintf('M0 0 C 20 %1$s 50 0 100 0 Z', $height * 2);
				break;

		    case 'downright':
		    	$path = sprintf('M0 0 C 50 0 80 %1$s 100 0 Z', $height * 2);
				break;

		    case 'lessdownleft':
		    	$path = sprintf('M0 0 C 20 %1$s 50 0 100 0 Z', intval($height * 1.3));
				break;

		    case 'lessdownright':
		    	$path = sprintf('M0 0 C 50 0 80 %1$s 100 0 Z', intval($height * 1.3));
				break;

		    case 'custom':
		    	$path = apply_filters('zu_custom_curve', '', $height);
				break;

		    default;
		    	$path = sprintf('M0 %1$s C 50 0 80 -%2$s 100 %1$s Z', $height, intval($height/3));
				break;
		}

		$curve = sprintf(
			'<div %2$s class="_curve %5$s" style="height: %4$spx">
				<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="100%%" height="%1$spx"
					style="position:relative; padding:0; margin:0; fill: currentColor; stroke: currentColor; top:0;"
					viewBox="0 0 100 %1$s" preserveAspectRatio="none">
					<path d="%3$s"></path>
				 </svg>
			 </div>',
			$height,
			empty($html_id) ? '' : sprintf('id="%1$s"', $html_id),
			$path,
			($height - 1),
			$class
		);
		return $curve;
	}

	public function set_copyright($copy_string) {
		$this->copy_string = $copy_string;
	}

	public function get_copyright() {
		return $this->copy_string;
	}

}

class ZU_PlusRepeaters {

	private $root;
	private $folder;

	function __construct($root = null, $folder = null) {

		$this->root = empty($root) ? zuplus_get_my_dir() : untrailingslashit($root);
		$this->folder = empty($folder) ? 'repeaters' : str_replace('/', '', $folder);
	}

	// Repeaters functions -------------------------------------------------------]

	private function get_repeater_path($name) {
		return sprintf('%1$s/%2$s/%3$s.php', $this->root, $this->folder, $name);
	}

	public function get_repeater_output($repeater, $args = [], $classes = '') {

		$include = $this->get_repeater_path($repeater);
		if(!file_exists($include)) $include = $this->get_repeater_path('default');
		if(!file_exists($include)) return '';

		$_template = $repeater;
		$_classes = $classes;
		$_args = $args;
		extract(zu()->array_prefix_keys($args, '_'));		// Import variables into the current symbol table from an array

		ob_start();
		include($include); 											// Include repeater template
		$output = ob_get_contents();
		ob_end_clean();

		return $output;
	}
}
