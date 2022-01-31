<?php

class Meerkat16_Events_CSS {
    private static $instance;

    protected function __construct() {
        add_action('admin_head', array(&$this, 'admin_head'));
    }

    function admin_head(){
        wp_enqueue_style('events-admin', CHILD_URL . CSSBUILDPATH . '/admin.css');
        //wp_enqueue_script('events-admin', CHILD_URL. JSBUILDPATH . 'admin.js', array('jquery'), '', true);
    }

    /**
     * Returns the singleton instance of this class.
     *
     * @return Meerkat16_Events_CSS The singleton instance.
     */
    public static function instance() {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Private clone method to prevent cloning of the instance of the
     * singleton instance.
     *
     * @return void
     */
    private function __clone() {
    }

    /**
     * Private unserialize method to prevent unserializing of the singleton
     * instance.
     *
     * @return void
     */
    private function __wakeup() {
    }
}

Meerkat16_Events_CSS::instance();