<?php
// From Duplicate Page Plugin
// based on version: 2.6
// Plugin URI: https://wordpress.org/plugins/duplicate-page/
// Description: Duplicate Posts, Pages and Custom Posts using single click.
// Author: mndpsingh287
// Modified: Dmitry Rudakov on 24.03.2018

class zu_PlusDuplicatePage extends zukit_Addon {

	private static $dup_action = 'dup_post_as_draft';

	public static $_defaults = [
		'dup_status'	=> 	'draft',
		'dup_redirect'	=> 	'to_page',
		'dup_suffix'	=> 	'copy',
	];

	protected function config() {
		return [
			'name'				=> 'zuplus_duplicate_page',
			'options'			=> [
				'status'			=> 	'draft',
				'redirect'			=> 	'to_page',
				'suffix'			=> 	'copy',
			],
		];
	}

	protected function construct_more() {

		add_action('admin_action_'.self::$dup_action, [$this, 'duplicate_post_as_draft']);
		add_filter('post_row_actions', [$this, 'duplicate_post_link'], 10, 2);
		add_filter('page_row_actions', [$this, 'duplicate_post_link'], 10, 2);
		add_action('post_submitbox_misc_actions', [$this, 'duplicate_post_button']);
	}

	public function keys_values() {
		return [
			'dup_status'	=> 	[
				'draft'			=>	'Draft',
				'publish'		=>	'Publish',
				'private'		=>	'Private',
				'pending'		=>	'Pending',
			],
			'dup_redirect'	=>	[
				'to_list'		=>	'To All Posts List',
				'to_page'		=>	'To Edit Duplicated Page',
			],
		];
	}

	public function duplicate_post_as_draft() {
		global $wpdb;

		if(!(isset($_GET['post']) || isset($_POST['post']) || (isset($_REQUEST['action']) && self::$dup_action == $_REQUEST['action']))) 	wp_die('No post to duplicate has been supplied!');

		$returnpage = '';
		$post_id = (isset($_GET['post']) ? $_GET['post'] : $_POST['post']);
		$post_status = $this->get_form_value('dup_status');
		$redirect_it = $this->get_form_value('dup_redirect');
		$suffix = $this->get_form_value('dup_suffix');
		$suffix = empty($suffix) ? '' : sprintf('--%s', $suffix);

		$post = get_post($post_id);
		$current_user = wp_get_current_user();
		$new_post_author = $current_user->ID;
		$new_post_title = function_exists('tplus_modify_content') ? tplus_modify_content($post->post_title, '', $suffix) : zu()->modify_content($post->post_title, '', $suffix);

		//		if post data exists, create the post duplicate

		if(isset($post) && $post != null) {
			$args = [
				'comment_status' 			=> $post->comment_status,
				'ping_status' 				=> $post->ping_status,
				'post_author' 				=> $new_post_author,
				'post_content' 				=> $post->post_content,
				'post_excerpt' 				=> $post->post_excerpt,
// 				'post_name' 					=> $post->post_name,
				'post_parent' 				=> $post->post_parent,
				'post_password' 			=> $post->post_password,
				'post_status' 				=> $post_status,
				'post_title' 				=> $new_post_title,
				'post_type' 				=> $post->post_type,
				'to_ping' 					=> $post->to_ping,
				'menu_order' 				=> $post->menu_order
			];

			//		insert the post by wp_insert_post() function

			$new_post_id = wp_insert_post($args);

			 //	get all current post terms ad set them to the new post draft

			$taxonomies = get_object_taxonomies($post->post_type);
			if(!empty($taxonomies) && is_array($taxonomies)) {
				foreach($taxonomies as $taxonomy) {
					$post_terms = wp_get_object_terms($post_id, $taxonomy, ['fields' => 'slugs']);
					wp_set_object_terms($new_post_id, $post_terms, $taxonomy, false);
				}
			}

			//		duplicate all post meta

			$post_meta_infos = $wpdb->get_results("SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=$post_id");

			if(count($post_meta_infos) !=0) {
				$sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
				foreach($post_meta_infos as $meta_info) {
					$meta_key = $meta_info->meta_key;
					$meta_value = addslashes($meta_info->meta_value);
					$sql_query_sel[]= "SELECT $new_post_id, '$meta_key', '$meta_value'";
				}

				$sql_query .= implode(" UNION ALL ", $sql_query_sel);
				$wpdb->query($sql_query);
			}

			//		finally, redirecting to your choice

			if($post->post_type != 'post') $returnpage = '?post_type='.$post->post_type;

			if(!empty($redirect_it) && $redirect_it == 'to_list') wp_redirect(admin_url('edit.php'.$returnpage));
			elseif(!empty($redirect_it) && $redirect_it == 'to_page') wp_redirect(admin_url('post.php?action=edit&post=' . $new_post_id));
			else wp_redirect(admin_url('edit.php'.$returnpage));
			exit;

		} else {
			wp_die('Error! Post creation failed, could not find original post: ' . $post_id);
		}
	}

	public function duplicate_post_link($actions, $post) {				// Add the duplicate link to action list for post_row_actions

		if(current_user_can('edit_posts')) {
			$actions['duplicate_this'] = sprintf('<a href="admin.php?action=%1$s&amp;post=%2$s" title="Duplicate this as %3$s" rel="permalink">%4$s</a>',
				self::$dup_action,
				$post->ID,
				$this->get_form_value('dup_status'),
				__('Duplicate This', 'zu-plugin')
			);
		}
		return $actions;
	}

	public function duplicate_post_button() {									// Add the duplicate link to edit screen
		global $post;

		$icon = 'images-alt2';
		$color = 'blue';
		$button_classes = ['button', 'button-primary', 'zu-dashicons', 'zu-button', 'zu-side-button']; // , 'zuplus_ajax_option'

		printf(
			'<div id="zuplus-duplicate-this" class="zuplus zu-pub-section">
				<a class="%6$s zu-button-%5$s" href="admin.php?action=%1$s&amp;post=%2$s" title="Duplicate this as %3$s" rel="permalink">
					<span class="dashicons dashicons-%4$s"></span>
					<span class="zu-link-text">%7$s</span>
				</a>
			</div>',
			self::$dup_action,
			$post->ID,
			$this->get_form_value('dup_status'),
			$icon,
			$color,
			zu()->merge_classes($button_classes),
			__('Duplicate This', 'zu-plugin')
		);
	}

	public function print_duplicate_page($post) {

		$form = $this->get_form();
		if(empty($form)) return;

		$form->select(
			'dup_status',
			'Duplicate Post Status:',
			$this->get_form_value('dup_status', false),
			'Select any post status you want to assign for duplicate post.'
		);

		$form->select(
			'dup_redirect',
			'Redirect to after click on link:',
			$this->get_form_value('dup_redirect', false),
			'Select any post redirection after click on <strong>"Duplicate This"</strong> link.'
		);

        $form->text('dup_suffix', 'Duplicate Post Suffix', 'Add a suffix for duplicate post as Copy, Clone etc. It will show after title.');

		echo $form->fields('Duplicate Page Settings.');
	}
}
