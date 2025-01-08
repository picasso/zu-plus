<?php
// From Cookie Notice Plugin
// based on version: 1.2.43
// Plugin URI: http://www.dfactory.eu/plugins/cookie-notice/
// Description: Cookie Notice allows you to inform users that the site uses cookies and to comply with the EU cookie law GDPR regulations.
// Author: dFactory
// Modified: Dmitry Rudakov on 10.06.2018

class zu_PlusCookieNotice extends zukit_Addon {

	private $refuse_code = '';
	private $refuse_code_head = '';

	protected function config() {
		return [
			'name'				=> 'zuplus_cookie_notice',
			'options'			=> [
				'cname'				=> 'zu_notice_accepted',
				'anim'				=> 'fade',
			],
		];
	}

	public static $_defaults = [
		'cookie_options'		=>	['cname' => 'zu_notice_accepted', 'anim' => 'fade'],
		'cookie_title'			=>	'',
		'cookie_message'		=>	'',
		'cookie_accept'			=>	'',
	];

	protected function construct_more() {

		self::$_defaults['cookie_title'] = __('Cookie Policy', 'zu-plugin');
		self::$_defaults['cookie_message'] = __('We use cookies for performance and analytics purposes. To find out more, review our @privacy policy@. Once you press the button, the dialogue box will disappear.', 'zu-plugin');
		self::$_defaults['cookie_accept'] = __('Got It', 'zu-plugin');

		// 		add_action('wp_head', [$this, 'print_header_scripts']);
		// 		add_action('wp_print_footer_scripts', [$this, 'print_footer_scripts']);
		add_action('wp_footer', [$this, 'print_cookie_notice'], 1000);

		add_filter('body_class', [$this, 'body_class']);
	}

	public static function cookie_name() {
		return isset(self::$_defaults['cookie_options']['cname']) ? self::$_defaults['cookie_options']['cname'] : 'zu_unknown';
	}

	public function keys_values() {
		return [
			'anim'			=> 	[
				'slide'		=>	'Slide Down',
				'fade'		=>	'Fade Out',
				'none'		=>	'None',
			],
		];
	}

	public function body_class($classes) {

		if (is_admin()) return $classes;

		if ($this->cookies_set()) {
			$classes[] = 'cookies-set';
			$classes[] = $this->cookies_accepted() ? 'cookies-accepted' : 'cookies-refused';
		} else
			$classes[] = 'cookies-not-set';

		return $classes;
	}

	public function print_cookie_notice() {

		if (!$this->cookies_set()) {

			$title = $this->option_value('cookie_title', self::$_defaults['cookie_title']);
			$message = $this->option_value('cookie_message', self::$_defaults['cookie_message']);
			$accept = $this->option_value('cookie_accept', self::$_defaults['cookie_accept']);

			if (function_exists('get_privacy_policy_url')) {

				$privacy = preg_match('/(@[^@]+?@)/i', $message, $privacy_place) ? $privacy_place[1] : '';
				$privacy_link = empty($privacy) ? '' : sprintf('<a href="%1$s">%2$s</a>', get_privacy_policy_url(), str_replace('@', '', $privacy));
				$message = str_replace($privacy, $privacy_link, $message);
			}

			$add_icon = function_exists('zu_get_icon') ? true : false;
			/** @disregard P1010 because the existence of `zu_get_icon` is checked **/
			$output = sprintf(
				'
				<div id="zu-cookie-notice" role="banner" class="cookie-notice">
					<div class="cookie-notice-container">
						<div class="cookie-left">%2$s</div>
						<div class="cookie-right">
							<h2 class="cookie-notice-title">%5$s%4$s</h2>
							<p class="cookie-notice-message">%1$s</p>
						</div>
						<button id="zu-accept-cookie" data-cookie-set="accept" class="zu-button">%3$s</button>
					</div>
				</div>',
				$message,
				$add_icon ? zu_get_icon('bookmark') : '',
				$accept,
				$title,
				$add_icon  ? zu_get_icon('bookmark', true, 'cookie-header-icon') : ''
			);

			echo $output;
		}
	}

	public static function cookies_accepted() {
		/** @disregard **/
		return zu()->check_option($_COOKIE, self::cookie_name());
	}

	public static function cookies_set() {
		return isset($_COOKIE[self::cookie_name()]);
	}

	public function get_allowed_html() {

		return array_merge(wp_kses_allowed_html('post'), [
			'script' 	=> ['type' => [], 'src' => [], 'charset' => [], 'async' => []],
			'noscript' 	=> [],
			'style' 	=> ['types' => []],
			'iframe' 	=> ['src' => [], 'height' => [], 'width' => [], 'frameborder' => [], 'allowfullscreen' => []],
		]);
	}

	public function print_footer_scripts() {
		if ($this->cookies_accepted()) {
			$scripts = html_entity_decode(trim(wp_kses($this->refuse_code, $this->get_allowed_html())));
			if (!empty($scripts)) echo $scripts;
		}
	}

	public function print_header_scripts() {
		if ($this->cookies_accepted()) {
			$scripts = html_entity_decode(trim(wp_kses($this->refuse_code_head, $this->get_allowed_html())));
			if (!empty($scripts)) echo $scripts;
		}
	}

	public function print_cookie_metabox($post) {

		$form = $this->get_form();
		if (empty($form)) return;

		$form->select(
			'cookie_options:anim',
			'Notice Animations:',
			$this->get_form_value('anim', false),
			'Select animation type for hiding Cookie Notice.'
		);

		$form->checkbox('cookie_options:redirect', 'Redirect After Accept', 'If checked, after acceptence of notice the user will be redirected to the same page.');

		$form->hidden('cookie_options:cname', self::cookie_name());

		$form->set_if_empty('cookie_title', self::$_defaults['cookie_title']);
		$form->text('cookie_title', 'Notice Title', 'Enter the notice title.');
		$form->set_if_empty('cookie_message', self::$_defaults['cookie_message']);
		$form->textarea('cookie_message', 'Notice Message', 'Enter the notice message to accept the usage of the cookies and make the notification disappear. Any text <span>@</span>between<span>@</span> will be replaced with <strong>Privacy Policy URL</strong>.', 2);
		$form->set_if_empty('cookie_accept', self::$_defaults['cookie_accept']);
		$form->text('cookie_accept', 'Notice Button', 'Enter the text for the accept button.');
		echo $form->fields('Cookie Notice Settings.');
	}
}
