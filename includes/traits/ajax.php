<?php

// Ajax/REST API helpers ------------------------------------------------------]

trait zu_PlusAjax {

	public function ajax_more($action, $value) {
		// if($action === 'zuplus_clear_log') return $this->is_debug() ? $this->plugin->dbug->clear_log() : [];
		if($action === 'zuplus_duplicate_menu') return $this->duplicate_menu();
		// if($action === 'zuplus_revoke_cookie') return ['info'	=> sprintf('Cookie "<strong>%1$s</strong>" was deleted', $ajax_value)];

		if($action === 'zuplus_reset_cached') return $this->reset_cached();

		return null;
	}
}
