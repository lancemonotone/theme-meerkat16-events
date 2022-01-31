<?php

class Meerkat16_Events {
    private static $instance;
    public static $debug = false;
    private static $plugins_path = 'plugins/';
    private static $global = array(
        'the-events-calendar/the-events-calendar.php',
        'events-calendar-pro/events-calendar-pro.php',
        'the-events-calendar-community-events/tribe-community-events.php',
        'advanced-post-manager/tribe-apm.php',
        'the-events-calendar-filterbar/the-events-calendar-filter-view.php',
        'tribe-ext-advanced-ical-export-master/index.php',
    );
    private static $local = array(
        'admin-menu-editor/menu-editor.php',
        'wp-crontrol/wp-crontrol.php',
        'better-notifications/bnfw.php'
    );

    protected function __construct() {
        add_action('after_setup_theme', array(&$this, 'load_global_plugins'));
        add_action('after_setup_theme', array(&$this, 'load_local_plugins'));
        add_action('after_setup_theme', array(&$this, 'load_lib'));
        add_action('admin_menu', array(&$this, 'category_group_configurator'));
    }

    public function category_options() {
        echo 'Create groups of Cats';
    }

    public function load_local_plugins() {
        foreach (self::$local as $plugin) {
            require(self::$plugins_path . $plugin);
        }
    }

    public function load_lib() {
        require('lib/class.css.php');
        require('lib/class.ical.php');
        require('lib/class.js.php');
        require('lib/class.admin.php');
        // To be used for adding filters to events list in dash
        require('lib/class.admin-filter-columns.php');
        require('lib/class.event.php');
        require('lib/class.notification.php'); // uses better-notifications plugin
        require('lib/class.acf.php');
        require('lib/class.rest.php');
        require('lib/class.daily-messages.php');
        require('lib/class.ldap.php');
        require('lib/class.hero.php');
        require('lib/class.filters-custom.php');
        require('lib/class.filterbar.php');
        require('lib/class.autohyperlink-urls.php');
        require('lib/class.admin-hide-recurring.php');
        require('lib/class.post.php');
        require('lib/class.submit.php');
        require('lib/class.taxonomies.php');
        require('lib/class.widget.php');
        // Update user names to latest LDAP record
        require(WPMU_MUPLUGIN_PATH . 'wms-admin/lib/class.ldap_user_updater.php');
    }


    function load_global_plugins() {
        // need is_plugin_active() for autoloading plugins
        require_once(WPMU_ADMIN_PATH . '/includes/plugin.php');
        require_once(THEME_PLUGINS_PATH . '/index.php');
        foreach (self::$global as $plugin) {
            if ( ! is_plugin_active($plugin)) {
                activate_plugin($plugin);
            }
        }
    }

    public static function get_user_links() {
        if (is_user_logged_in()) {
            $logout_btn = do_shortcode('[logout class="btn"]');

            return <<<ELO
<div class="widget sidebar-login"><h4>You are logged in</h4>
    <ul>
        <li>&middot; <a href="/events/community/add">Add new event/announcement</a></li>
        <li>&middot; <a href="/events/community/list">View your events</a></li>
    </ul>
    {$logout_btn}
 </div>
ELO;
        } else {
            $login_btn = do_shortcode('[login class="btn"]');

            return <<<ELO
<div class="widget sidebar-login"><h4>Login for full access</h4>
    <ul>
        <li>&middot; View campus-only events</li>
        <li>&middot; Add new events</li>
        <li>&middot; View your submitted events</li>
    </ul>
    {$login_btn}
</div>
ELO;
        }
    }

    function category_group_configurator() {
        $args = array(
            'page_title' => 'Category Groups',
            'capability' => 'manage_options',
            'position'   => '11.6',
        );
        if (function_exists('acf_add_options_page')) {
            acf_add_options_page($args);
        }
    }

    /**
     * Returns the singleton instance of this class.
     *
     * @return M16_Events_Event The singleton instance.
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

Meerkat16_Events::instance();
