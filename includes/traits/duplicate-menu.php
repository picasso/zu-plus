<?php

// Duplicate Menu helpers -----------------------------------------------------]

trait zu_PlusDuplicateMenu {

    private function duplicate_from_id($id = null, $name = null) {

        if(empty($id) || empty($name)) return false;

        $source = wp_get_nav_menu_object($id);
        $source_items = wp_get_nav_menu_items($id);
        $new_id = wp_create_nav_menu($name);

        if(!$new_id) return false;

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

            // if it has a parent, we should update with a new id
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
            $items[] = [
                'id'    => $menu->term_id,
                'title' => $menu->name,
            ];
        }
        return $items;
    }

    private function sanitize_data($menu_data) {
        return is_array($menu_data) ? [
            'id'    => intval($menu_data['id'] ?? 0),
            'title' => sanitize_text_field($menu_data['title'] ?? ''),
        ] : null;
    }

    private function duplicate_menu($menu_data) {
        $data = $this->sanitize_data($menu_data);
        // go ahead and duplicate the requested menu
        $new_menu_id = $this->duplicate_from_id($data['id'], $data['title']);

    	if($new_menu_id) {
            return $this->create_notice(
                'successdata', // combine 'success' with 'data'
                sprintf('Menu was duplicated with name "<strong>%1$s</strong>"', $data['title']),
                $this->get_menus()
            );
        } else {
            return $this->create_notice(
                'error',
                'There was a problem duplicating your menu. No action was taken.'
            );
        }
    }
 }
