(function($) {
	'use strict';

	var click_attempt = 0;
	var removed = false;

	$(window).on('load', function() {
		click_autosave_notice();
		setTimeout(click_autosave_notice, 0);
		setTimeout(click_autosave_notice, 300);
		setInterval(click_autosave_notice, 1000);
	});

	function click_autosave_notice() {
		if(removed) return;

		var messages = {
			backup: 'The backup of this post in your browser is different from the version below',
			autosave: 'There is an autosave of this post that is more recent than the version below',
		};

		$('.components-notice.is-dismissible').each(function() {
			$(this).each(function() {
				let $notice = $(this);
				let text = $notice.find('.components-notice__content').text();

				lodash.forEach(messages, function(value) {
					if(lodash.includes(text, value)) {
						$notice.find('.components-notice__dismiss').trigger('click');
						// eslint-disable-next-line no-console
						console.info('REMOVED (at ' +  (click_attempt + 1) + ' try): ' + value);
						removed = true;
					}
				});
			});
		});

		click_attempt++;
	}

})(jQuery);
