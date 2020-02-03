<?php

// Functions & Classes --------------------------------------------------------]

require_once(__ZUPLUS_ROOT__ . 'includes/zuplus-functions.php');
require_once(__ZUPLUS_ROOT__ . 'includes/zuplus-admin.php');

// Basic Plugin Class ---------------------------------------------------------]

class zuplus_Plugin {

	public $admin;
	public $config;

	protected $prefix;
	protected $nonce;
	protected $options_id;
	protected $plugin_file;
	protected $version;
	protected $defaults = [];
	protected $addons = [];

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
			'prefix'			=> 	'zuplus',
			'admin'				=> 	'',
			'plugin_file'		=> 	__FILE__,
			'plugin_name'		=>	'',
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
		$this->config = $config;
	}

	protected function extend_config() {
		return [];
	}

	protected function extend_defaults() {
		return [];
	}

	protected function construct_more() {
	}

	public function create_addon($classname, $more_params = []) {

		$addon = new $classname($this->config_addon($more_params));
		$this->register_addon($addon);
		return $addon;
	}

	public function config_addon($more_params = []) {

		$params = [
			'options'			=> $this->options(),
			'version'			=> $this->version,
			'prefix'			=> $this->prefix,
			'plugin_file'		=> $this->plugin_file,
			'plugin'			=> $this,
		];

		return empty($more_params) ? $params : (is_array($more_params) ? array_merge($params, $more_params) : $params);
	}

	public function register_addon($addon) {

		if(in_array($addon, $this->addons))	return;
		$this->addons[] = $addon;
	}

	public function clean_addons() {

		foreach($this->addons as $addon) {
			$addon->clean();
		}
	}


	public function defaults() {

		if(empty($this->defaults)) {
			$defaults = [
				'ajaxurl'                	=> admin_url('admin-ajax.php'),
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

	public function update_options($options) {
		return update_option($this->options_id, $options);
	}

	public function check_option($key, $check = true) {
		return zu()->check_option($this->options(), $key, $check);
	}

	public function option_value($key, $default_value = '') {
		$options = $this->options();
		return isset($options[$key]) ? $options[$key] : $default_value;
	}

	public function current_timestamp() {
		return intval(current_time('timestamp'));
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


	private function enqueue_style_or_script($is_style, $file, $deps = [], $bottom = true) {

		$filename = $is_style ? sprintf('css/%1$s.css', $file) : sprintf('js/%1$s.min.js', $file);
		$handle = $is_style ? $file.'-style' : $file.'-script';
		$filepath = plugin_dir_path($this->plugin_file).$filename;
		$src = plugins_url($filename, $this->plugin_file);
		if(file_exists($filepath)) {
			$version = defined('ZUDEBUG') ? filemtime($filepath) : $this->version;
			if($is_style) wp_enqueue_style($handle, $src, $deps, $version);
			else wp_enqueue_script($handle, $src, $deps, $version, $bottom);
		}
		return $handle;
	}

	public function enqueue_style($file, $deps = []) {
		return $this->enqueue_style_or_script(true, $file, $deps);
	}

	public function enqueue_script($file, $deps = ['jquery'], $bottom = true) {
		return $this->enqueue_style_or_script(false, $file, $deps, $bottom);
	}

}

class zuplus_Addon {

	protected $options;
	protected $version;
	protected $prefix;
	protected $plugin_file;
	protected $config;
	protected $plugin;
	protected $form;

    public static $_defaults = [];

	function __construct($config) {

		$this->config = $config;
		$this->prefix = isset($config['prefix']) ? $config['prefix'] : 'zuplus';
		$this->version = isset($config['version']) ? $config['version'] : ZUPLUS_VERSION;
		$this->plugin_file = isset($config['plugin_file']) ? $config['plugin_file'] : __ZUPLUS_FILE__;
		$this->plugin = isset($config['plugin']) ? $config['plugin'] : null;
		$this->options = isset($config['options']) ? $config['options'] : [];
		$this->form = null;
		$this->construct_more();
	}

	static function defaults() {

		$class = get_called_class();
		return isset($class::$_defaults) ? $class::$_defaults : [];
	}

	static function novalidate() {
		return array_keys(array_filter(self::defaults(), function($val) { return !is_bool($val); }));
	}

	public function keys_values() {
		return [];
	}

	public function get_form() {
		if(empty($this->form)) $this->form = isset($this->plugin->admin) ? $this->plugin->admin->form : null;
		return $this->form;
	}

	public function get_form_value($key, $as_value = true) {

		$form_defaults = self::defaults();
		$form_values = $this->keys_values();

		$value = isset($form_defaults[$key]) ? $this->option_value($key, $form_defaults[$key]) : '';
		return $as_value ? $value : (isset($form_values[$key]) ? $form_values[$key] : '');
	}

	protected function construct_more() {
	}

	protected function clean() {
	}

	protected function current_timestamp() {
		return intval(current_time('timestamp'));
	}

	protected function update_options($options) {
		if(!is_null($this->plugin)) {
			$this->plugin->update_options($options);
			$this->options = $this->plugin->options();
		}
	}

	protected function check_option($key, $check = true) {
		return zu()->check_option($this->options, $key, $check);
	}

	protected function option_value($key, $default_value = '') {
		return isset($this->options[$key]) ? $this->options[$key] : $default_value;
	}

	protected function check_config($key, $check = true) {
		return zu()->check_option($this->config, $key, $check);
	}

	protected function get_config($key, $def_value = '') {
		return isset($this->config[$key]) ? $this->config[$key] : $def_value;
	}

	public function config_addon($more_params = []) {
		$params = $this->config;
		return empty($more_params) ? $params : (is_array($more_params) ? array_merge($params, $more_params) : $params);
	}

	protected function enqueue_style($file, $deps = []) {
		return is_null($this->plugin) ? null : $this->plugin->enqueue_style($file, $deps);
	}

	protected function enqueue_script($file, $deps = ['jquery'], $bottom = true) {
		return is_null($this->plugin) ? null : $this->plugin->enqueue_script($file, $deps, $bottom);
	}

	protected function print_option($option_key = '') {}
}
