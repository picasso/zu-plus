/* global zuplus_custom */
(function($) {
	'use strict';

	$(window).on('load', function() { 	
		setTimeout(function() { $('body').addClass('ready'); }, 200);
	});	

	// When the document is ready...
	$(document).ready(function() {
		
		if($('body').hasClass('zuplus')) {			// only for plugin screen  if($('body').hasClass(zuplus_custom.screen_id)) {
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
			$link.click(function() { zuplus_turn_option('zuplus_dismiss_error'); }); 
			if($link.hasClass('ajax-dismiss')) $link.click(function() { setTimeout(function() { $link.parent().remove(); }, 100); });
		});
	}

	function zuplus_bind_links() {
		
		zuplus_bind_dismiss_links();
		
		$('.zuplus_ajax_option').each(function() {  
			var $ajax_link = $(this);
			var option_name = $ajax_link.data('zuplus_option');
			var prefix_name = $ajax_link.data('zuplus_prefix');
			
			if(option_name !== undefined && option_name.length !== 0) {
				$ajax_link.unbind().click(function(e) {
					e.preventDefault();
					zuplus_turn_option(option_name, prefix_name);
				}); 
			}
		});
	}

	function zuplus_ajax_data($selector) {
	    var form_data = $selector.serialize().split('&');
	    var data = {};
	
	    $.each(form_data, function(key, value) {
	        var row = value.split('=');
	        var key_name = decodeURIComponent(row[0]).replace(/^[^\[]+\[([^\]]+)\]/, '$1');
	        data[key_name] = decodeURIComponent(row[1]);
	    });
	
	    return data;
	}
	
	function zuplus_turn_option(option_name, prefix_name) {

		var data = {
			action: prefix_name + '_option',
			option_name: option_name
		};

		// Try serialize data
		var $rel = $("[data-ajaxrel='" + option_name + "']");
		if($rel.length) {
			$.extend(data, zuplus_ajax_data($rel.find('input, textarea, select')));			
		}
						
		// Send an AJAX call to switch the option
		$.ajax({
			url: zuplus_custom.ajaxurl,
			type: 'POST',
			dataType: 'json',
			async: true,
			cache: false,
			data: data,
			success: function(response) { zuplus_update_UI(option_name, response); },
			complete: function(/*  $jqXHR, $textStatus  */) { }
		});
	}

	function zuplus_update_UI(option_name, response) {
		
		var result = $.extend({result:''}, response.data).result;
		if(String(result).length) {
			$('#poststuff').parents('.wrap').find('.notice-after').after(result);
			zuplus_bind_dismiss_links();
		}
		
        switch (option_name) {
            case 'zuplus_clear_revisions':
	                $('.zuplus_revision_info').empty();
	                break;
                
            case 'zuplus_clear_errors':
	                $('#zuplus-errors-mb .inside').empty().append('<div class="form_desc">There\'re no errors.</div>');
	                break;

            default:
	                break;
        }
	}

})(jQuery);