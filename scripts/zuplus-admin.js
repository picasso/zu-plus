/* global zuplus_custom, lodash, console */
(function($) {
	'use strict';

	var zuplus_data = zuplus_custom.data || {};

	$(window).on('load', function() {
		zuplus_repair_kint_tabs();

		setTimeout(function() {
			$('body').addClass('ready');
		}, 200);

		if(zuplus_data.remove_autosave)  {
			zuplus_click_autosave_notice();
			setTimeout(zuplus_click_autosave_notice, 300);
			setInterval(zuplus_click_autosave_notice, 1000);
		}

	});

	// When the document is ready...
	$(document).ready(function() {

		if($('body').hasClass('zuplus')) {			// only for plugin screen  if($('body').hasClass(zuplus_data.screen_id)) {
			// Bind action links
			zuplus_bind_links();

			// Setup required fields
			$('.zu-field.required').each(function() {

				// Define the field <td>
				var $zu_field_td = $(this);

				// Check the input
				$zu_field_td.find('.zu-input-required').on('keyup change',function() {

					if(String($(this).val()) !== '') {

						// If the input has a value, remove the error messages
						$zu_field_td.removeClass('zu-field-error').removeClass('zu-field-is-blank');

					} else {

						// If the input doesn't have a value, show the error messages
						$zu_field_td.addClass('zu-field-error').addClass('zu-field-is-blank');

					}
				});
			});
		}
	});

	function zuplus_bind_dismiss_links() {
		$('.notice.is-dismissible .notice-dismiss').each(function() {
			var $link = $(this);
			var prefix_name = $link.parent().data('zuplus_prefix');
			$link.click(function() { zuplus_turn_option('zuplus_dismiss_error', prefix_name); });
			if($link.hasClass('ajax-dismiss')) $link.click(function() { setTimeout(function() { $link.parent().remove(); }, 100); });
		});
	}

	function zuplus_bind_links() {

		zuplus_bind_dismiss_links();
		zuplus_bind_kint_tabs();

		$('.zuplus_ajax_option').each(function() {
			var $ajax_link = $(this);
			var option_name = $ajax_link.data('zuplus_option');
			var prefix_name = $ajax_link.data('zuplus_prefix');

			if(option_name !== undefined && option_name.length !== 0) {

				var confirm_msg = $ajax_link.data('zuplus_confirm');
				var ajax_value = $ajax_link.data('zuplus_ajax_value');

				$ajax_link.unbind().click(function(e) {
					e.preventDefault();
					if(confirm_msg !== undefined && confirm_msg.length !== 0) {
						if(window.confirm(confirm_msg)) zuplus_turn_option(option_name, prefix_name, ajax_value);
					} else {
						zuplus_turn_option(option_name, prefix_name, ajax_value);
					}
				});
			}
		});
	}

	function zuplus_repair_kint_tabs() {

	   $('#qm-debug_bar_zu_debugbarpanel-container .kint-tabs').each(function() {

		   let $tabs_content = $(this).next().find('li');

		   $tabs_content.each(function() {
			   let $li = $(this);

			   if($li.index() === 0) $li.attr('style', 'display: block !important;');
			   else $li.attr('style', 'display: none !important;');

			   // var style = $value.attr('style') || '';
			   //
			   // style = style.replace('none;', 'none !important;').replace('block;', 'block !important;');
			   // $value.attr('style', style);
		   });
	   });

	   // replace stardard output with 'error' message for 'ZU_LOG:'
	   $('.kint-rich dt').each(function() {

		   let $dt = $(this);
		   if($dt.text().indexOf('ZU_LOG:') !== -1) {
			   $dt.find('dfn').remove();
			   $dt.find('var').remove();
			   let msg = $dt.text().replace(/\s*\(\d+\)\s*/g, '').replace(/\"/g, '').replace('ZU_LOG:', '<span>ZU_LOG:</span>');
			   let matches = /\[\s*([^\]]+)]/i.exec(msg);
			   if(matches && matches.length > 1) msg = msg.replace(matches[0], '[ <b>'+matches[1]+'</b> ]');
			   $dt.html(msg);
		   }
	   });
    }

	function zuplus_bind_kint_tabs() {

	   $('#qm-debug_bar_zu_debugbarpanel-container .kint-tabs').each(function() {

		   let $tabs = $(this).find('li');
		   let $tabs_content = $tabs.parent().next().find('li');

		   $tabs.each(function() {
			   let $link = $(this);
			   let index = $link.index();

			   $link.click(function() {

				   $tabs_content.each(function() {
					   let $li = $(this);
					   if($li.index() === index) $li.attr('style', 'display: block !important;');
					   else $li.attr('style', 'display: none !important;');
				   });
			   });
		   });
	   });
    }

	function zuplus_click_autosave_notice() {

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
						$notice.find('.components-notice__dismiss').click();
						console.info('REMOVED: ' + value);
					}
				});
			});
		});
	 }

	function zuplus_ajax_data($selector) {
	    var form_data = $selector.serialize().split('&');
	    var data = {};

	    $.each(form_data, function(_key, value) {
	        var row = value.split('=');
	        var key_name = decodeURIComponent(row[0]).replace(/^[^\[]+\[([^\]]+)\]/, '$1');
	        data[key_name] = decodeURIComponent(row[1]);
	    });

	    return data;
	}

	function zuplus_turn_option(option_name, prefix_name, ajax_value) {

		if(prefix_name === undefined || prefix_name.length === 0) prefix_name = 'zuplus';

		var data = {
			action: prefix_name + '_option',
			option_name: option_name,
			ajax_value: ajax_value,
		};

		// Try serialize data
		var $rel = $("[data-ajaxrel*='" +option_name+ "']");
		if($rel.length) {
			$.extend(data, zuplus_ajax_data($rel.find('input, textarea, select')));
		}

		var $container = $rel.length ? $rel.parents('.postbox') : $('#' +prefix_name+'-options-mb');

		if(data.option_name === 'zuplus_revoke_cookie') {
			document.cookie = data.ajax_value+'=;Max-Age=-99999999;expires=Thu, 01 Jan 1970 00:00:01 GMT;path=/;';
		}

		// Send an AJAX call to switch the option
		$container.addClass('now_ajaxed');
		$.ajax({
			url: zuplus_data.ajaxurl,
			type: 'POST',
			dataType: 'json',
			async: true,
			cache: false,
			data: data,
			success: function(response) { zuplus_update_UI(option_name, response, $container); },
			error: function() { $container.removeClass('now_ajaxed'); }
		});
	}

	function zuplus_update_UI(option_name, response, $container) {

		var result = $.extend({result:''}, response.data).result;
		var clear_now_ajaxed = true;
		var scroll_top = true;

		if(String(result).length) {
			$('#poststuff').parents('.wrap').find('.notice-after').after(result);
			zuplus_bind_dismiss_links();
		}

        switch (option_name) {
            case 'zuplus_clear_revisions':
	                $('.zuplus_revision_info').empty();
	                scroll_top = false;
	                break;

            case 'zuplus_clear_errors':
	                $('#zuplus-errors-mb .inside').empty().append('<div class="form_desc">There\'re no errors.</div>');
	                break;

            default:
	                break;
        }

        if(clear_now_ajaxed) $container.removeClass('now_ajaxed');

		if(scroll_top) $('html, body').stop().animate({scrollTop:0}, 500, 'swing');
	}

	// Process Filetree if presented
	// Hide all subfolders at startup
	$('.zu-file-tree').find('ul').hide();

	// Expand/collapse on click
	$('.zu-directory a').click( function() {
		$(this).parent().find('ul:first').slideToggle('medium');
		if($(this).parent().hasClass('zu-directory')) return false;
	});


})(jQuery);
