<?php

class zu_PlusDuplicatePage extends zukit_Addon {

	private $duplicate_action = 'zuplus_dup_post';
	private $duplicate_status = 'draft';
	private $duplicate_redirect = 'page'; // 'list' 'page'
	private $duplicate_suffix = 'copy';

	protected function config() {
		return ['name' => 'zuplus_duplicate'];
	}

	protected function construct_more() {

		add_action('admin_action_' . $this->duplicate_action, [$this, 'duplicate_post_as_draft']);
		add_filter('post_row_actions', [$this, 'duplicate_post_link'], 10, 2);
		add_filter('page_row_actions', [$this, 'duplicate_post_link'], 10, 2);
		// add_action('post_submitbox_misc_actions', [$this, 'duplicate_post_button']);
	}

	// From Duplicate Page Plugin
	// based on version: 2.6
	// Plugin URI: https://wordpress.org/plugins/duplicate-page/
	// Author: mndpsingh287
	public function duplicate_post_as_draft() {
		global $wpdb;

		// check post ID for duplicating
		if(!(
			isset($_GET['post'])
			|| isset($_POST['post'])
			|| (isset($_REQUEST['action']) && $_REQUEST['action'] === $this->duplicate_action)
		)) {
			zu_logc('!No post ID found to duplicate', $_REQUEST);
			wp_die('No post ID found to duplicate!');
		}

		// check nonce and user rights
		$nonce = $_REQUEST['nonce'] ?? null;
		if(!wp_verify_nonce($nonce, $this->ajax_nonce()) || !current_user_can('edit_posts')) {
			zu_logc('!Security issue during post duplicating', $nonce, current_user_can('edit_posts'));
			wp_die('Security issue, please try again!');
		}

		$post_id = isset($_GET['post']) ? intval($_GET['post']) : intval($_POST['post'] ?? 0);
		$post = get_post($post_id);
		$current_user = wp_get_current_user();
		$new_post_author = $current_user->ID;
		$new_post_title = sprintf('%s [%s]', $post->post_title, $this->duplicate_suffix);
		// это нужно исправить для плагина перевода чтобы правильно добавлять суффикс внутрь разных языков
		// function_exists('tplus_modify_content') ?
		// tplus_modify_content($post->post_title, '', $suffix) : zu()->modify_content($post->post_title, '', $suffix);

		// if post data exists, create the post duplicate
		if(isset($post) && $post !== null) {
			$args = [
				'post_title' 		=> $new_post_title,
				'post_author' 		=> $new_post_author,
				'post_status' 		=> $this->duplicate_status,

				'post_type' 		=> $post->post_type,
				'post_parent' 		=> $post->post_parent,
				'post_content' 		=> $post->post_content,
				'post_excerpt' 		=> $post->post_excerpt,
				'post_password' 	=> $post->post_password,
				'comment_status' 	=> $post->comment_status,
				'ping_status' 		=> $post->ping_status,
				'to_ping' 			=> $post->to_ping,
				'menu_order'		=> $post->menu_order
			];

			// insert the post by wp_insert_post() function
			$new_post_id = wp_insert_post($args);

			 //	get all current post terms ad set them to the new post draft
			$taxonomies = get_object_taxonomies($post->post_type);
			if(!empty($taxonomies) && is_array($taxonomies)) {
				foreach($taxonomies as $taxonomy) {
					$post_terms = wp_get_object_terms($post_id, $taxonomy, ['fields' => 'slugs']);
					wp_set_object_terms($new_post_id, $post_terms, $taxonomy, false);
				}
			}

			// duplicate all post meta
			$post_meta_infos = $wpdb->get_results("SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=$post_id");
			if(count($post_meta_infos) !== 0) {
				$sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
				foreach($post_meta_infos as $meta_info) {
					$meta_key = sanitize_text_field($meta_info->meta_key);
					$meta_value = addslashes($meta_info->meta_value);
					$sql_query_sel[] = "SELECT $new_post_id, '$meta_key', '$meta_value'";
				}
				$sql_query .= implode(" UNION ALL ", $sql_query_sel);
				$wpdb->query($sql_query);
			}

			// finally, redirecting
			$redirect_url = $this->duplicate_redirect === 'list' ?
				admin_url('edit.php' . ($post->post_type !== 'post' ? '?post_type='.$post->post_type : '')) :
				admin_url('post.php?action=edit&post=' . $new_post_id);

			// if($post->post_type != 'post') $returnpage = '?post_type='.$post->post_type;
			// if(!empty($redirect_it) && $redirect_it == 'to_list') wp_redirect(admin_url('edit.php'.$returnpage));
			// elseif(!empty($redirect_it) && $redirect_it == 'to_page') wp_redirect(admin_url('post.php?action=edit&post=' . $new_post_id));
			// else

			wp_redirect($redirect_url);
			exit;

		} else {
			zu_logc('!Could not find original post during duplicating', $post_id, $post);
			wp_die('Post creation failed, could not find original post: ' . $post_id);
		}
	}

	// Add the duplicate link to action list for post_row_actions
	public function duplicate_post_link($actions, $post) {
		if(current_user_can('edit_posts')) {
			$link = __('Duplicate', 'zu-plus');
			$title = __('Duplicate this %s as draft', 'zu-plus');
			$post_type = get_post_type_object($post->post_type);
			if($post_type !== null) {
				// use 'name_admin_bar' instead of 'singular_name'
				// because this name is already in the correct declension form
				$name = $post_type->labels->name_admin_bar;
				$link = sprintf('%s %s', $link, $name);
				$title = sprintf($title, mb_strtolower($name));
			} else {
				$title = preg_replace('/%s\s+/', '', $title);
			}
			$actions['zuplus_duplicate'] = sprintf(
				'<a href="admin.php?action=%1$s&amp;post=%2$s&amp;nonce=%3$s" title="%4$s" rel="permalink">%5$s</a>',
				$this->duplicate_action,
				$post->ID,
				$this->ajax_nonce(true),
				$title,
				$link
			);
		}
		return $actions;
	}

	// Add the duplicate link to edit screen
	// public function duplicate_post_button() {
	// 	global $post;
	//
	// 	$icon = 'images-alt2';
	// 	$color = 'blue';
	// 	$button_classes = ['button', 'button-primary', 'zu-dashicons', 'zu-button', 'zu-side-button']; // , 'zuplus_ajax_option'
	//
	// 	zu_printf(
	// 		'<div id="zuplus-duplicate-this" class="zuplus zu-pub-section">
	// 			<a class="%6$s zu-button-%5$s" href="admin.php?action=%1$s&amp;post=%2$s" title="Duplicate this as %3$s" rel="permalink">
	// 				<span class="dashicons dashicons-%4$s"></span>
	// 				<span class="zu-link-text">%7$s</span>
	// 			</a>
	// 		</div>',
	// 		$this->duplicate_action,
	// 		$post->ID,
	// 		$this->get_form_value('dup_status'),
	// 		$icon,
	// 		$color,
	// 		zu()->merge_classes($button_classes),
	// 		__('Duplicate This', 'zu-plus')
	// 	);
	// }

	// $form->select(
	// 	'dup_status',
	// 	'Duplicate Post Status:',
	// 	$this->get_form_value('dup_status', false),
	// 	'Select any post status you want to assign for duplicate post.'
	// );
	//
	// $form->select(
	// 	'dup_redirect',
	// 	'Redirect to after click on link:',
	// 	$this->get_form_value('dup_redirect', false),
	// 	'Select any post redirection after click on <strong>"Duplicate This"</strong> link.'
	// );
	//
    // $form->text('dup_suffix', 'Duplicate Post Suffix', 'Add a suffix for duplicate post as Copy, Clone etc. It will show after title.');
	// echo $form->fields('Duplicate Page Settings.');

	// 'dup_status'	=> 	[
	// 	'draft'			=>	'Draft',
	// 	'publish'		=>	'Publish',
	// 	'private'		=>	'Private',
	// 	'pending'		=>	'Pending',
	// ],
	// 'dup_redirect'	=>	[
	// 	'to_list'		=>	'To All Posts List',
	// 	'to_page'		=>	'To Edit Duplicated Page',
	// ],

}
