<?php

// Functions & Classes --------------------------------------------------------]

require_once(__ZUPLUS_ROOT__ . 'includes/zuplus-functions.php');
require_once(__ZUPLUS_ROOT__ . 'includes/zuplus-admin.php');

// Basic Plugin Class ---------------------------------------------------------]

class zuplus_Plugin {

	protected $admin;

	protected $prefix;
	protected $nonce;
	protected $options_id;
	protected $plugin_file;
	protected $version;
	protected $defaults = [];

	final public static function instance() {
		
        static $instances = [];
        
		$calledClass = get_called_class();
		if(!isset($instances[$calledClass])) {
			 $instances[$calledClass] = new $calledClass();
		}
		return $instances[$calledClass];
	}

    final private function __clone() {
    }

	function __construct($config = []) {

		$config_default = [
			'prefix'				=> 	'zuplus',
			'admin'				=> 	'',
			'plugin_file'		=> 	__FILE__,
			'plugin_name'	=>	'',
			'version'			=> 	'x.x.x',
		];
		
		$config = array_merge($config_default, $this->extend_config(), $config);

		$this->prefix = $config['prefix'];
		$this->admin = $config['admin'];
		$this->plugin_file = $config['plugin_file'];
		$this->version = $config['version'];
		$this->nonce = isset($config['nonce']) ? $config['nonce'] : $this->prefix.'_ajax_nonce';
		$this->options_id = isset($config['options_id']) ? $config['options_id'] : $this->prefix.'_options';
		
		add_action('init', [$this, 'init']);

		if(is_admin() && !empty($this->admin)) {
			$config['nonce'] = $this->nonce;
			$config['options_id'] = $this->options_id;
			unset($config['admin']);
			$this->admin = new $this->admin($config, $this);
		}
		
		$this->construct_more();
	}
	
	protected function extend_config() {
		return [];
	}
	
	protected function extend_defaults() {
		return [];
	}

	protected function construct_more() {
	}
	
	public function config_addon() {
		
		return [
			'options'			=> $this->options(),
			'version'			=> $this->version,
			'prefix'				=> $this->prefix,
			'plugin_file'		=> $this->plugin_file,
		];
	}
	
	public function defaults() {
		
		if(empty($this->defaults)) {
			$defaults = [
				'ajaxurl'                			=> admin_url('admin-ajax.php'),
				$this->prefix.'_nonce'     	=> $this->ajax_nonce(),
			];
		
			$this->defaults = array_merge($defaults, $this->extend_defaults());
		}
		
		return $this->defaults;
	}

	public function default_value($key) {
		return isset($this->defaults[$key]) ? $this->defaults[$key] : [];
	}

	public function options() { 
		return get_option($this->options_id, []); 
	}

	public function check_option($key, $check = true) { 
		return zu()->check_option($this->options(), $key, $check);
	}
	
	public function option_value($key, $default_value = '') {
		$options = $this->options();
		return isset($options[$key]) ? $options[$key] : $default_value;
	}

	public function ajax_nonce($create = 'true') { 
		return $create ? wp_create_nonce($this->nonce) : $this->nonce; 
	}

	public function init() {
	}

	public function ready() {
		
		$this->frontend_enqueue();
	}

	public function should_load_css() {
		return false;
	}
	
	public function should_load_js() {
		return false;
	}
	
	public function frontend_enqueue() {					

		if($this->should_load_css()) zu()->add_style_from_file(plugin_dir_path($this->plugin_file) . 'css/'.$this->prefix.'.css');
		
		if($this->should_load_js()) {
			wp_enqueue_script($this->prefix.'-script', plugins_url('js/'.$this->prefix.'.min.js', $this->plugin_file), ['jquery'], $this->version, true);
			wp_localize_script($this->prefix.'-script', $this->prefix.'_custom', $this->defaults());
		}
	}
}

class zuplus_Addon {

	protected $options;
	protected $version;
	protected $prefix;
	protected $plugin_file;
	
	function __construct($config) {
		
		$this->prefix= isset($config['prefix']) ? $config['prefix'] : 'zuplus';
		$this->version= isset($config['version']) ? $config['version'] : ZUPLUS_VERSION;
		$this->plugin_file= isset($config['plugin_file']) ? $config['plugin_file'] : __ZUPLUS_FILE__;
		$this->options = isset($config['options']) ? $config['options'] : [];
		$this->construct_more();
	}
	
	protected function construct_more() {
	}
	
	protected function check_option($key, $check = true) {
		return zu()->check_option($this->options, $key, $check);
	}
	
	protected function enqueue_style($file, $deps = []) {
		
		$src = plugins_url(sprintf('css/%1$s.css', $file), $this->plugin_file);
		wp_enqueue_style($file.'-style', $src, $deps, $this->version);
	}

	protected function enqueue_script($file, $deps = ['jquery'], $bottom = true) {
		
		$src = plugins_url(sprintf('js/%1$s.min.js', $file), $this->plugin_file);
		$handle = $file.'-script';
		wp_enqueue_script($handle, $src, $deps, $this->version, $bottom);
		return $handle;
	}
}
