<div class="wrap <?php echo $_wrap_class; ?>">
	<h2 class="notice-after"><?php do_action($_prefix.'_print_title'); ?></h2>
	<?php
		// Show error messages
		settings_errors();
		do_action($_prefix.'_print_error_settings');
		// Oputput form
		do_action($_prefix.'_print_form');
	?>
		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-<?php echo 1 == get_current_screen()->get_columns() ? '1' : '2'; ?>">
				<?php do_action($_prefix.'_print_body'); ?> 
				<div id="postbox-container-1" class="postbox-container">
				<?php do_meta_boxes('', 'side', null); ?>
				<?php do_action($_prefix.'_print_side');	?> 
				</div> <!-- #postbox-container-1 -->
				
				<div id="postbox-container-2" class="postbox-container plugin-mb">
				<?php do_meta_boxes('', 'normal', null);  ?>
				<?php do_action($_prefix.'_print_normal');	?> 
				<?php do_meta_boxes('', 'advanced', null); ?>
				<?php do_action($_prefix.'_print_advanced');	?> 
				</div> <!-- #postbox-container-2 -->	     					
			</div> <!-- #post-body -->
		</div> <!-- #poststuff -->
	</form>			
	<?php do_action($_prefix.'_print_footer'); ?>
</div><!-- .wrap -->



