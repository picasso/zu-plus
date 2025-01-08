<?php

// Add a new Debug Bar Panel
/** @disregard P1009 because `Debug_Bar_Panel` is defined in Query Monitor **/
class zu_PlusDebugBarPanel extends Debug_Bar_Panel {
	private $callback;

	public function set_callback($callback) {
		$this->callback = $callback;
	}

	public function prerender() {
		/** @disregard P1013 because `set_visible` is defined in Query Monitor **/
		$this->set_visible(true);
	}

	public function render() {
		echo call_user_func($this->callback);
	}
}
