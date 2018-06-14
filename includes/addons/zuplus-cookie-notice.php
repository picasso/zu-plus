<?php
// From Cookie Notice Plugin
// based on version: 1.2.43
// Plugin URI: http://www.dfactory.eu/plugins/cookie-notice/
// Description: Cookie Notice allows you to inform users that the site uses cookies and to comply with the EU cookie law GDPR regulations.
// Author: dFactory
// Modified: Dmitry Rudakov on 10.06.2018

class ZU_CookieNotice extends zuplus_Addon {
	
	private $message; 
	private $accept;
	
	private $refuse_code = '';
	private $refuse_code_head = '';

    public static $_defaults = [
		'cookie_options'		=>	[ 'cname' => 'zu_notice_accepted', 'anim' => 'fade' ],
		'cookie_title'				=>	'',
		'cookie_message'		=>	'',
		'cookie_accept'			=>	'',
    ];

	protected function construct_more() {

		$this->message = __('We use cookies for performance and analytics purposes. To find out more, review our @privacy policy@. Once you press the button, the dialogue box will disappear.', 'zu-plugin');
		$this->accept = __('Got It', 'zu-plugin');
		
		add_action('wp_head', [$this, 'print_header_scripts']);
		add_action('wp_print_footer_scripts', [$this, 'print_footer_scripts']);
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
		
		if(is_admin()) return $classes;

		if($this->cookies_set()) {
			$classes[] = 'cookies-set';
			$classes[] = $this->cookies_accepted() ? 'cookies-accepted' : 'cookies-refused';
		} else
			$classes[] = 'cookies-not-set';

		return $classes;
	}

	public function print_cookie_notice() {
		
		if(!$this->cookies_set()) {
			
			$message = $this->message;
			
			if(function_exists('get_privacy_policy_url')) {
				
				$privacy = preg_match('/(@[^@]+?@)/i', $this->message, $privacy_place) ? $privacy_place[1] : '';
				$privacy_link = empty($privacy) ? '' : sprintf('<a href="%1$s">%2$s</a>', get_privacy_policy_url(), str_replace('@', '', $privacy));
				$message = str_replace($privacy, $privacy_link, $message);							
			}

// <div class="privacy-basic">  <div class="privacy-basic-body">    <img alt="Notification Icon" class="privacy-basic-icon" src="https://www.xe.com/themes/xe/images/icon-notification.svg">    <div class="privacy-basic-text">      <h1 class="privacy-basic-title">        Cookie Management      </h1>      <p class="privacy-basic-message">        XE.com uses cookies and tags for performance, analytics and tracking purposes.  To find out more, review our <a class="privacy-basic-link" href="/privacy.php">privacy policy</a> and <a class="privacy-basic-link" href="/cookiepolicy.php">cookie policy</a>.  If you want to customise your cookies, <a class="privacy-basic-link privacy-customize-cookies">click here</a>.  Once you press the “Got It” button, the dialogue box will disappear.      </p>    </div>    <div class="privacy-button-container">      <button class="privacy-basic-button privacy-basic-button-submit" type="submit">			  GOT IT			</button>    </div>  </div></div>
			
			$output = sprintf('
				<div id="zu-cookie-notice" role="banner" class="cookie-notice">
					<div class="cookie-notice-container">
						<h2 class="cookie-notice-title">%2$sCookie Management</h2>
						<p class="cookie-notice-message">%1$s</p>
						<button id="zu-accept-cookie" data-cookie-set="accept" class="zu-button">%3$s</button>
					</div>
				</div>',
				$message,
				function_exists('zu_get_icon') ? zu_get_icon('bookmark') : '',
				$this->accept
			);

			echo $output;
		}
	}

	public static function cookies_accepted() {
		return zu()->check_option($_COOKIE, self::cookie_name());
	}

	public static function cookies_set() {
		return isset($_COOKIE[self::cookie_name()]);
	}

	public function get_allowed_html() {
	
		return array_merge(wp_kses_allowed_html('post'), [
			'script' 		=> ['type' => [], 'src' => [],'charset' => [], 'async' => []], 
			'noscript' 	=> [], 
			'style' 		=> ['types' => []], 
			'iframe' 	=> ['src' => [], 'height' => [], 'width' => [], 'frameborder' => [], 'allowfullscreen' => []],
		]);
	}

	public function print_footer_scripts() {
		if($this->cookies_accepted()) {
			$scripts = html_entity_decode(trim(wp_kses($this->refuse_code, $this->get_allowed_html())));
			if(!empty($scripts)) echo $scripts;
		}
	}

	public function print_header_scripts() {
		if($this->cookies_accepted()) {
			$scripts = html_entity_decode(trim(wp_kses($this->refuse_code_head, $this->get_allowed_html())));
			if(!empty($scripts)) echo $scripts;
		}
	}
	
/*
__('Enter the cookie notice message.', 'cookie-notice')
__('The text of the option to accept the usage of the cookies and make the notification disappear.', 'cookie-notice')
__('Synchronize with WordPress Privacy Policy page.', 'cookie-notice')
__('Enter the full URL starting with http(s)://', 'cookie-notice')
__('Select the privacy policy link target.', 'cookie-notice')
*/
	public function print_cookie_metabox($post) {

		$form = $this->get_form();
		if(empty($form)) return;

		$form->select(
			'cookie_options:anim', 
			'Notice Animations:', 
			$this->get_form_value('anim', false), 
			'Select animation type for hiding Cookie Notice.'
		);

		$form->checkbox('cookie_options:redirect', 'Redirect After Accept', 'If checked, after acceptence of notice the user will be redirected.');

		$form->hidden('cookie_options:cname', self::cookie_name());

        $form->text('cookie_message', 'Notice Message', 'Enter the cookie notice message.');
		echo $form->fields('Cookie Notice Settings.');
	}
}
