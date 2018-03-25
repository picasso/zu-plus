<?php
// From Duplicate Page Plugin
// based on version: 2.6
// Plugin URI: https://wordpress.org/plugins/duplicate-page/
// Description: Duplicate Posts, Pages and Custom Posts using single click.
// Author: mndpsingh287
// Modified: Dmitry Rudakov on 24.03.2018

class ZU_DuplicatePage extends zuplus_Addon {
	
	private $dup_action = 'dup_post_as_draft';

	private 	$status_values = [
		'draft'		=>	'Draft',
		'publish'	=>	'Publish',
		'private'		=>	'Private',
		'pending'	=>	'Private',
	];	

	private $redirect_values = [
		'to_list'		=>	'To All Posts List',
		'to_page'	=>	'To Duplicate Edit Screen',
	];	
	
	protected function construct_more() {

		add_action('admin_action_'.$this->dup_action, [$this, 'duplicate_post_as_draft']); 
		add_filter('post_row_actions', [$this, 'duplicate_post_link'], 10, 2);
		add_filter('page_row_actions', [$this, 'duplicate_post_link'], 10, 2);
		add_action('post_submitbox_misc_actions', [$this, 'duplicate_post_button']);
	}

	public function status_values() {
		return $this->status_values;
	}

	public function redirect_values() {
		return $this->redirect_values;
	}

	public function duplicate_post_as_draft() {
		global $wpdb;

		if(!(isset($_GET['post']) || isset($_POST['post']) || (isset($_REQUEST['action']) && $this->dup_action == $_REQUEST['action']))) 	wp_die('No post to duplicate has been supplied!');

		$returnpage = '';
		$post_id = (isset($_GET['post']) ? $_GET['post'] : $_POST['post']);
		$post_status = $this->option_value('dup_status', 'draft');	
		$redirectit = $this->option_value('dup_redirect', 'to_list');	 
		$suffix = $this->option_value('dup_suffix', '');
		
		$post = get_post($post_id); 
		$current_user = wp_get_current_user();
		$new_post_author = $current_user->ID; 

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
				'post_title' 					=> $post->post_title . (empty($suffix) ? '' : sprintf('--%s', $suffix)),
				'post_type' 					=> $post->post_type,
				'to_ping' 						=> $post->to_ping,
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
				
			if(!empty($redirectit) && $redirectit == 'to_list') wp_redirect(admin_url('edit.php'.$returnpage));
			elseif(!empty($redirectit) && $redirectit == 'to_page') wp_redirect(admin_url('post.php?action=edit&post=' . $new_post_id));
			else wp_redirect(admin_url('edit.php'.$returnpage));
			exit;
		
		} else {
			wp_die('Error! Post creation failed, could not find original post: ' . $post_id);
		}
	}
	
	// Add the duplicate link to action list for post_row_actions
	public function duplicate_post_link($actions, $post) {
		
		if(current_user_can('edit_posts')) {
			$actions['duplicate_this'] = sprintf('<a href="admin.php?action=%1$s&amp;post=%2$s" title="Duplicate this as %3$s" rel="permalink">%4$s</a>',
				$this->dup_action,
				$post->ID,
				$this->status_values[$this->option_value('dup_status', 'draft')],
				__('Duplicate This', 'zu-plugin')
			);
		}
		return $actions;
	}
	 
	// Add the duplicate link to edit screen
	public function duplicate_post_button() {
		global $post;
		
		printf(
			'<div id="zuplus-publishing-actions">
				<div id="zuplus-export-action">
					<a href="admin.php?action=%1$s&amp;post=%2$s" title="Duplicate this as %3$s" rel="permalink">%4$s</a>
				</div>
			</div>',
			$this->dup_action,
			$post->ID,
			$this->status_values[$this->option_value('dup_status', 'draft')],
			__('Duplicate This', 'zu-plugin')
		);
	}
}
