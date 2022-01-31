<?php

class M16_Events_Admin {
    private static $instance;
    public static $is_admin;

    protected function __construct() {
        self::$is_admin = current_user_can('edit_others_tribe_events');

        // Saving the site options via wp-admin/network/site-settings.php?id=247 breaks Tribe's 'virtual' front page, so reset it
        add_filter('do_parse_request', array(&$this, 'page_on_front'), 20, 3);

        // The Wms_Admin method breaks the login redirect back to the form.
        //if ( ! class_exists('core_google_apps_login')) {
        //    remove_filter('login_redirect', array(Wms_Admin::instance(), 'redirect_to_request'), 100);
        //} else {
            add_action('tribe_ce_event_submission_login_form', array(&$this, 'glogin_redirect'));
        //}
        add_filter('wms_logout_redirect', array(&$this, 'redirect_logout'), 20, 1);

        add_action('admin_menu', array(__CLASS__, 'remove_meta'));
        add_action('register_post_type_args', array(&$this, 'register_cpt_for_rest'), 20, 2);

        add_filter('custom_crumb_title', array(&$this, 'custom_crumb_title'));
        add_filter('login_form_defaults', array(&$this, 'login_form_defaults'));
        add_filter('tribe_event_label_singular', array(&$this, 'do_label_singular'), 10, 1);
        add_filter('tribe_ical_feed_calname', array(&$this, 'custom_ical_feed_name'), 10, 1);
        add_filter('tribe_ce_submit_event_page_title', array(&$this, 'do_submit_page_title'), 10, 1);
        add_filter('tribe_events_register_venue_type_args', array(&$this, 'tribe_venues_custom_field_support'));
        add_filter('tribe_events_importer_venue_column_names', array(&$this, 'add_venue_columns'), 10, 1);
        add_filter('tribe_community_required_field_marker', array(&$this, 'required_field_marker'), 10, 1);
        add_filter('tribe_events_recurrence_tooltip', array(&$this, 'recurrence_tooltip'), 10, 1);
        add_filter('tribe_events_get_the_excerpt', array(&$this, 'get_the_excerpt'), 10, 2);
        add_filter('tribe_get_single_option', array(&$this, 'autoapprove_trusted_users'), 10, 3);

        // Allow admins to edit users
        add_filter('map_meta_cap', array(&$this, 'mc_admin_users_caps'), 1, 4);
        remove_all_filters('enable_edit_any_user_configuration');
        add_filter('enable_edit_any_user_configuration', '__return_true');
        add_filter('admin_head', array(&$this, 'mc_edit_permission_check'), 1, 4);
        // End allow admins to edit users

        // Allow subscribers to upload images
        add_action('init', array(&$this, 'allow_subscriber_uploads'));
    }

    function redirect_logout($redirect_url){
        if(stristr($redirect_url, 'events/community')) return home_url();
        return $redirect_url;
    }

    function glogin_redirect() {
        if ( ! class_exists('core_google_apps_login')) return;
        $network_url  = str_replace(parse_url(network_site_url())['path'], '/wp-login.php', network_site_url());
        $redirect_url = Wms_Server::instance()->get_site_url() . Wms_Server::instance()->request_uri;
        wp_redirect($network_url . '?redirect_to=' . $redirect_url);
        die();
    }

    function page_on_front($do_parse_request, $wp, $extra_query_vars) {
        $value = intval(tribe('tec.front-page-view')->get_virtual_id());
        if ($value !== intval(get_option('page_on_front'))) {
            update_option('page_on_front', $value);
        }

        return $do_parse_request;
    }

    function allow_subscriber_uploads() {
        //Allow Subscribers to Add Media when creating an event
        if (is_user_logged_in() && ! is_user_member_of_blog()) {
            global $blog_id;
            $user = wp_get_current_user();
            add_user_to_blog($blog_id, $user->ID, 'subscriber');
            wp_redirect($_SERVER['REQUEST_URI']);
            exit;
        }
    }

    /**
     * Automatically approve all trusted (non-subscribers).
     *
     * @param $option
     * @param $default
     * @param $optionName
     *
     * @return string
     */
    function autoapprove_trusted_users($option, $default, $optionName) {
        global $userdata;
        // Users without a site account (network users) won't have capabilities
        if (empty($userdata)) return $option;

        if ($caps = $userdata->caps) {
            switch ($optionName) {
                case 'defaultStatus':
                    if ( ! count(array_intersect(array('subscriber', 'contributor', 'author'), array_keys($caps)))) {
                        $option = 'publish';
                    }
                    break;
                default:
                    break;
            }
        }

        return $option;
    }

    /**
     * Allow Admins to edit users
     *
     * @param $caps
     * @param $cap
     * @param $user_id
     * @param $args
     *
     * @return mixed
     */
    function mc_admin_users_caps($caps, $cap, $user_id, $args) {

        foreach ($caps as $key => $capability) {

            if ($capability != 'do_not_allow')
                continue;

            switch ($cap) {
                case 'edit_user':
                case 'edit_users':
                    $caps[ $key ] = 'edit_users';
                    break;
                case 'delete_user':
                case 'delete_users':
                    $caps[ $key ] = 'delete_users';
                    break;
                case 'create_users':
                    $caps[ $key ] = $cap;
                    break;
            }
        }

        return $caps;
    }

    /**
     * Checks that both the editing user and the user being edited are
     * members of the blog and prevents the super admin being edited.
     */
    function mc_edit_permission_check() {
        global $current_user, $profileuser;

        $screen = get_current_screen();

        _wp_get_current_user();

        if ( ! is_super_admin($current_user->ID) && in_array($screen->base, array('user-edit', 'user-edit-network'))) { // editing a user profile
            if (is_super_admin($profileuser->ID)) { // trying to edit a superadmin while less than a superadmin
                wp_die(__('You do not have permission to edit this user.'));
            } elseif ( ! (is_user_member_of_blog($profileuser->ID, get_current_blog_id()) && is_user_member_of_blog($current_user->ID, get_current_blog_id()))) { // editing user and edited user aren't members of the same blog
                wp_die(__('You do not have permission to edit this user.'));
            }
        }

    }

    public function get_the_excerpt($excerpt, $post) {
        if ($short_desc = get_field('acf_daily_message_text', $post->ID)) {
            return wpautop($short_desc);
        } else {
            return $excerpt;
        }
    }

    public function recurrence_tooltip($value) {
        return str_replace('Recurring Event/Announcement', 'Recurring', $value);
    }

    public function required_field_marker($value) {
        return '<span class="acf-required">*</span>';
    }

    public function custom_ical_feed_name($value) {
        return __('Williams Events Calendar');
    }

    public function do_submit_page_title($value) {
        return __('Submit an Event/Announcement');
    }

    public function do_label_singular($value) {
        return __('Event/Announcement');
    }

    public function login_form_defaults($defaults) {
        return wp_parse_args(array('label_username' => 'Williams Username'), $defaults);
    }

    public function register_cpt_for_rest($args, $post_type) {
        if ($post_type == "tribe_events") {
            $args['show_in_rest'] = true;
        }

        return $args;
    }

    /**
     * Enable custom field support for venue posts.
     *
     * @param array $args
     *
     * @return array
     */
    function tribe_venues_custom_field_support($args) {
        $args['supports'][] = 'custom-fields';

        return $args;
    }

    /**
     * Filters the Venue column names that will be shown to the user.
     *
     * @param array $column_names
     */
    function add_venue_columns($column_names) {
        $column_names['campus_bird_id'] = esc_html__('Campus Bird ID', 'the-events-calendar');

        return $column_names;
    }

    public function remove_meta() {
        $to_remove = array(
            'tagsdiv-post_tag',
            'event_categorydiv',
            'daily_message_catsdiv',
            'daily_message_audiencediv',
            'music_dept_catsdiv',
            'music_dept_cal_typesdiv',
            'music_dept_season_typesdiv',
            'music_dept_seasonsdiv',
            'ctd_catsdiv',
            'ctd_season_typesdiv',
            'ctd_seasonsdiv',
            'event_departmentsdiv',
            'event_groupsdiv',
            'ctd_series_catsdiv',
            'postexcerpt',
            'commentsdiv',
            'advanced-sortables'
            //'tribe_events_catdiv',
        );

        foreach ($to_remove as $id) {
            remove_meta_box($id, 'tribe_events', 'side');
        }

        remove_meta_box('tribe_dashboard_widget', 'dashboard', 'normal');
    }

    /**
     * Set breadcrumb based on page title instead of Tribe's default 'WP Router Placeholder'
     *
     * @param $title String not used
     *
     * @return string
     */
    public static function custom_crumb_title($title) {
        return trim(wp_title(null, false, null));
    }

    /**
     * Returns the singleton instance of this class.
     *
     * @return M16_Events_Admin The singleton instance.
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

M16_Events_Admin::instance();
