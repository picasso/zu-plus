<?php

// Add a new Debug Bar Panel
class zu_PlusDebugBarPanel extends Debug_Bar_Panel {
    private $callback;

    public function set_callback( $callback ) {
        $this->callback = $callback;
    }

    public function prerender() {
        $this->set_visible(true);
    }

    public function render() {
        echo call_user_func($this->callback);
    }
}
