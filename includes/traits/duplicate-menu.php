<?php

// Duplicate Menu helpers -----------------------------------------------------]

trait zu_PlusDuplicateMenu {

    private function duplicate_from_id($id = null, $name = null) {

        if(empty($id) || empty($name)) return false;

        $id = intval($id);
        $name = sanitize_text_field($name);
        $source = wp_get_nav_menu_object($id);
        $source_items = wp_get_nav_menu_items($id);
        $new_id = wp_create_nav_menu($name);

        if(!$new_id) return false;

        // key is the original db ID, val is the new
        $rel = [];

        $i = 1;
        foreach($source_items as $menu_item) {
            $args = array(
                'menu-item-db-id'       => $menu_item->db_id,
                'menu-item-object-id'   => $menu_item->object_id,
                'menu-item-object'      => $menu_item->object,
                'menu-item-position'    => $i,
                'menu-item-type'        => $menu_item->type,
                'menu-item-title'       => $menu_item->title,
                'menu-item-url'         => $menu_item->url,
                'menu-item-description' => $menu_item->description,
                'menu-item-attr-title'  => $menu_item->attr_title,
                'menu-item-target'      => $menu_item->target,
                'menu-item-classes'     => implode(' ', $menu_item->classes),
                'menu-item-xfn'         => $menu_item->xfn,
                'menu-item-status'      => $menu_item->post_status
           );

            $parent_id = wp_update_nav_menu_item($new_id, 0, $args);

            $rel[$menu_item->db_id] = $parent_id;

            // did it have a parent? if so, we need to update with the NEW ID
            if($menu_item->menu_item_parent) {
                $args['menu-item-parent-id'] = $rel[$menu_item->menu_item_parent];
                $parent_id = wp_update_nav_menu_item($new_id, $parent_id, $args);
            }

            $i++;
        }

        return $new_id;
    }

    private function get_menus() {
        $nav_menus = wp_get_nav_menus();
		$items = [];

        foreach($nav_menus as $menu) {
            $items[$menu->term_id] = $menu->name;
        }
        return $items;
    }

    public function print_metabox($options) {

/*
        $nav_menus = wp_get_nav_menus();
		$desc = 'Here you can easily duplicate WordPress Menus.';
		$items = [];

		if(empty($nav_menus)) {
			$items[] ='You haven\'t created any Menus yet.';
		} else {
			$select_values = $items = [];
			foreach($nav_menus as $_nav_menu) $select_values[$_nav_menu->term_id] = $_nav_menu->name;

			$items[] = tplus_select($options, 'source_menu', 'Duplicate this menu:', $select_values);
            $items[] = tplus_text($options, 'new_menu', 'And call it', 'The name will be assigned to duplicated menu.');
			$items[] = tplus_button_link('tplus_duplicate_menu', __('Duplicate', 'tplus-plugin'), 'images-alt2', 'red', true, false);
		}
		echo tplus_fields($items, $desc, 'tplus_duplicate_menu');
*/
    }

    private function duplicate_menu() {

        $source_id = intval($_POST['source_menu']);
        $destination = sanitize_text_field($_POST['new_menu']);
        // go ahead and duplicate our menu
        $new_menu_id = $this->duplicate_from_id($source_id, $destination);

    	if($new_menu_id) return $this->create_notice('success', sprintf('Menu was duplicated with name <strong>%1$s</strong>', $destination));
    	else return $this->create_notice('error', 'There was a problem duplicating your menu. No action was taken.');
    }
 }
