<?php

class M16_Events_Taxonomies {
    private static $instance;

    protected function __construct() {
        add_action('init', array(&$this, 'register_taxonomies'));
    }

    /**
     * @return void
     */
    public static function register_taxonomies() {

        /**
         * Taxonomy: Daily Message Audience.
         */

        $labels = array(
            "name"          => __("Daily Message Audience", "custom-post-type-ui"),
            "singular_name" => __("Daily Message Audience", "custom-post-type-ui"),
        );

        $args = array(
            "label"                 => __("Daily Message Audience", "custom-post-type-ui"),
            "labels"                => $labels,
            "public"                => true,
            "publicly_queryable"    => true,
            "hierarchical"          => true,
            "show_ui"               => true,
            "show_in_menu"          => true,
            "show_in_nav_menus"     => true,
            "query_var"             => true,
            "rewrite"               => array('slug' => 'daily_message_audience', 'with_front' => true,),
            "show_admin_column"     => false,
            "show_in_rest"          => false,
            "rest_base"             => "daily_message_audience",
            "rest_controller_class" => "WP_REST_Terms_Controller",
            "show_in_quick_edit"    => false,
        );
        register_taxonomy("daily_message_audience", array("tribe_events"), $args);

        /**
         * Taxonomy: Music Department Categories.
         */

        $labels = array(
            "name"          => __("Music Department Categories", "custom-post-type-ui"),
            "singular_name" => __("Music Department Category", "custom-post-type-ui"),
        );

        $args = array(
            "label"                 => __("Music Department Categories", "custom-post-type-ui"),
            "labels"                => $labels,
            "public"                => true,
            "publicly_queryable"    => true,
            "hierarchical"          => true,
            "show_ui"               => true,
            "show_in_menu"          => true,
            "show_in_nav_menus"     => true,
            "query_var"             => true,
            "rewrite"               => array('slug' => 'music_dept_cats', 'with_front' => true,),
            "show_admin_column"     => false,
            "show_in_rest"          => false,
            "rest_base"             => "music_dept_cats",
            "rest_controller_class" => "WP_REST_Terms_Controller",
            "show_in_quick_edit"    => false,
        );
        register_taxonomy("music_dept_cats", array("tribe_events"), $args);

        /**
         * Taxonomy: Music Department Calendar Types.
         */

        $labels = array(
            "name"          => __("Music Department Calendar Types", "custom-post-type-ui"),
            "singular_name" => __("Music Department Calendar Type", "custom-post-type-ui"),
        );

        $args = array(
            "label"                 => __("Music Department Calendar Types", "custom-post-type-ui"),
            "labels"                => $labels,
            "public"                => true,
            "publicly_queryable"    => true,
            "hierarchical"          => true,
            "show_ui"               => true,
            "show_in_menu"          => true,
            "show_in_nav_menus"     => true,
            "query_var"             => true,
            "rewrite"               => array('slug' => 'music_dept_cal_types', 'with_front' => true,),
            "show_admin_column"     => false,
            "show_in_rest"          => false,
            "rest_base"             => "music_dept_cal_types",
            "rest_controller_class" => "WP_REST_Terms_Controller",
            "show_in_quick_edit"    => false,
        );
        register_taxonomy("music_dept_cal_types", array("tribe_events"), $args);

        /**
         * Taxonomy: Music Department Season Types.
         */

        $labels = array(
            "name"          => __("Music Department Season Types", "custom-post-type-ui"),
            "singular_name" => __("Music Department Season Type", "custom-post-type-ui"),
        );

        $args = array(
            "label"                 => __("Music Department Season Types", "custom-post-type-ui"),
            "labels"                => $labels,
            "public"                => true,
            "publicly_queryable"    => true,
            "hierarchical"          => true,
            "show_ui"               => true,
            "show_in_menu"          => true,
            "show_in_nav_menus"     => true,
            "query_var"             => true,
            "rewrite"               => array('slug' => 'music_dept_season_types', 'with_front' => true,),
            "show_admin_column"     => false,
            "show_in_rest"          => false,
            "rest_base"             => "music_dept_season_types",
            "rest_controller_class" => "WP_REST_Terms_Controller",
            "show_in_quick_edit"    => false,
        );
        register_taxonomy("music_dept_season_types", array("tribe_events"), $args);

        /**
         * Taxonomy: Music Department Seasons.
         */

        $labels = array(
            "name"          => __("Music Department Seasons", "custom-post-type-ui"),
            "singular_name" => __("Music Department Season", "custom-post-type-ui"),
        );

        $args = array(
            "label"                 => __("Music Department Seasons", "custom-post-type-ui"),
            "labels"                => $labels,
            "public"                => true,
            "publicly_queryable"    => true,
            "hierarchical"          => true,
            "show_ui"               => true,
            "show_in_menu"          => true,
            "show_in_nav_menus"     => true,
            "query_var"             => true,
            "rewrite"               => array('slug' => 'music_dept_seasons', 'with_front' => true,),
            "show_admin_column"     => false,
            "show_in_rest"          => false,
            "rest_base"             => "music_dept_seasons",
            "rest_controller_class" => "WP_REST_Terms_Controller",
            "show_in_quick_edit"    => false,
        );
        register_taxonomy("music_dept_seasons", array("tribe_events"), $args);

        /**
         * Taxonomy: 62 Center Seasons.
         */

        $labels = array(
            "name"          => __("62 Center Seasons", "custom-post-type-ui"),
            "singular_name" => __("62 Center Season", "custom-post-type-ui"),
        );

        $args = array(
            "label"                 => __("62 Center Seasons", "custom-post-type-ui"),
            "labels"                => $labels,
            "public"                => true,
            "publicly_queryable"    => true,
            "hierarchical"          => true,
            "show_ui"               => true,
            "show_in_menu"          => true,
            "show_in_nav_menus"     => true,
            "query_var"             => true,
            "rewrite"               => array('slug' => 'ctd_seasons', 'with_front' => true,),
            "show_admin_column"     => false,
            "show_in_rest"          => false,
            "rest_base"             => "ctd_seasons",
            "rest_controller_class" => "WP_REST_Terms_Controller",
            "show_in_quick_edit"    => false,
        );
        register_taxonomy("ctd_seasons", array("tribe_events"), $args);

        /**
         * Taxonomy: Departments.
         */

        $labels = array(
            "name"          => __("Departments", "custom-post-type-ui"),
            "singular_name" => __("Department", "custom-post-type-ui"),
        );

        $args = array(
            "label"                 => __("Departments", "custom-post-type-ui"),
            "labels"                => $labels,
            "public"                => true,
            "publicly_queryable"    => true,
            "hierarchical"          => true,
            "show_ui"               => true,
            "show_in_menu"          => true,
            "show_in_nav_menus"     => true,
            "query_var"             => true,
            "rewrite"               => array('slug' => 'event_departments', 'with_front' => true,),
            "show_admin_column"     => false,
            "show_in_rest"          => true,
            "rest_base"             => "event_departments",
            "rest_controller_class" => "WP_REST_Terms_Controller",
            "show_in_quick_edit"    => false,
        );
        register_taxonomy("event_departments", array("tribe_events"), $args);

        /**
         * Taxonomy: Groups.
         */

        $labels = array(
            "name"          => __("Groups", "custom-post-type-ui"),
            "singular_name" => __("Group", "custom-post-type-ui"),
        );

        $args = array(
            "label"                 => __("Groups", "custom-post-type-ui"),
            "labels"                => $labels,
            "public"                => true,
            "publicly_queryable"    => true,
            "hierarchical"          => true,
            "show_ui"               => true,
            "show_in_menu"          => true,
            "show_in_nav_menus"     => true,
            "query_var"             => true,
            "rewrite"               => array('slug' => 'event_groups', 'with_front' => true,),
            "show_admin_column"     => false,
            "show_in_rest"          => false,
            "rest_base"             => "event_groups",
            "rest_controller_class" => "WP_REST_Terms_Controller",
            "show_in_quick_edit"    => false,
        );
        register_taxonomy("event_groups", array("tribe_events"), $args);

        /**
         * Taxonomy: Event Categories.
         */

        $labels = array(
            "name"          => __("Event Categories", "custom-post-type-ui"),
            "singular_name" => __("Event Category", "custom-post-type-ui"),
        );

        $args = array(
            "label"                 => __("Event Categories", "custom-post-type-ui"),
            "labels"                => $labels,
            "public"                => true,
            "publicly_queryable"    => true,
            "hierarchical"          => true,
            "show_ui"               => true,
            "show_in_menu"          => true,
            "show_in_nav_menus"     => true,
            "query_var"             => true,
            "rewrite"               => array('slug' => 'event_category', 'with_front' => true,),
            "show_admin_column"     => false,
            "show_in_rest"          => false,
            "rest_base"             => "event_category",
            "rest_controller_class" => "WP_REST_Terms_Controller",
            "show_in_quick_edit"    => false,
        );
        register_taxonomy("event_category", array("tribe_events"), $args);

        /**
         * Taxonomy: 62 Center Series Categories.
         */

        $labels = array(
            "name"          => __("62 Center Series Categories", "custom-post-type-ui"),
            "singular_name" => __("62 Center Series Categories", "custom-post-type-ui"),
        );

        $args = array(
            "label"                 => __("62 Center Series Categories", "custom-post-type-ui"),
            "labels"                => $labels,
            "public"                => true,
            "publicly_queryable"    => true,
            "hierarchical"          => true,
            "show_ui"               => true,
            "show_in_menu"          => true,
            "show_in_nav_menus"     => true,
            "query_var"             => true,
            "rewrite"               => array('slug' => 'ctd_series_cats', 'with_front' => true,),
            "show_admin_column"     => false,
            "show_in_rest"          => false,
            "rest_base"             => "ctd_series_cats",
            "rest_controller_class" => "WP_REST_Terms_Controller",
            "show_in_quick_edit"    => false,
        );
        register_taxonomy("ctd_series_cats", array("tribe_events"), $args);
    }

    /**
     * Returns the singleton instance of this class.
     *
     * @return M16_Events_Taxonomies The singleton instance.
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

M16_Events_Taxonomies::instance();