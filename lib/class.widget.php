<?php

class Meerkat16_Events_Widgets {
    private static $instance;

    protected function __construct() {

         register_sidebar( array(
            'name'          => __( 'Sidebar Message', 'm16_events' ),
            'id'            => 'sidebar-message',
            'description'   => __( 'A widget area for messages at the bottom of the Events Calendar sidebar.' ),
            'before_widget' => '',
            'after_widget' => '',
            'before_title' => '',
            'after_title' => '',
        ) );
     
    }

    /**
     * Returns the singleton instance of this class.
     *
     * @return Meerkat16_Events_Widgets The singleton instance.
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

Meerkat16_Events_Widgets::instance();