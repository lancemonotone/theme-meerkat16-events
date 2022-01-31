<?php

class Meerkat_Events_JS {
    private static $instance;

    protected function __construct() {
        // Add our js after ACF has loaded so ACF js has priority.
        //add_action('acf/input/admin_enqueue_scripts', array(&$this, 'init'), 15);
        //frontend scripts
        add_action('wp_enqueue_scripts', array(&$this, 'init'), 15);
    }

    public static function init() {
        $filename = '/app.js';
        $url      = CHILD_JS_URL . $filename;
        $path     = CHILD_JS_PATH . $filename;

        // native WP object
        global $userdata;

        Meerkat16_Js::instance()->do_load('m16-events', array(
            'src'   => $url,
            'path'  => $path,
            'local' => array(
                'M16Events' => array('user' => json_encode($userdata->caps))
            )
        ));
    }

    /**
     * Returns the singleton instance of this class.
     *
     * @return Meerkat_Events_JS The singleton instance.
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

Meerkat_Events_JS::instance();
