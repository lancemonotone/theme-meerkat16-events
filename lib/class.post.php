<?php

class M16_Events_Post {
    private static $instance;
    private static $ctd;

    protected function __construct() {
        add_filter('posts_results', array(&$this, 'populate_posts'), 10, 1);
        add_filter('tribe_events_single_event_after_the_meta', array(&$this, 'remove_populate_posts'));

        self::$ctd = array(
            "'62 Center" => get_blog_details(186),
            "Music"      => get_blog_details(196),
            "Theatre"    => get_blog_details(162),
            "Dance"      => get_blog_details(134),
            "Studio62"   => get_blog_details(244)
        );
    }

    /**
     * We don't need to get all the info for the related posts. Let's save some cycles.
     */
    function remove_populate_posts() {
        remove_filter('posts_results', array(__CLASS__, 'populate_posts'));
    }


    public static function populate_posts($posts) {
        if (current($posts)->post_type !== 'tribe_events') return $posts;
        if (current($posts)->init) return $posts;

        foreach ($posts as &$post) {
            $post = self::populate_post($post);
        }

        return $posts;
    }

    public static function populate_post($post) {
        // Make sure post is an object.
        if ( ! is_a($post, 'WP_Post')) {
            return false;
        }

        $post->init = true;

        $post->title     = stripslashes($post->post_title);
        $post->content   = self::get_linked_content($post->post_content);
        $post->event_url = tribe_get_event_link($post->ID);
        $post->type      = current(array_keys(M16_Events_Event::get_terms($post, 'tribe_events_cat')));

        $post->private      = self::get_es_acf_field($post, 'acf_private');
        $post->venue_room   = self::get_es_acf_field($post, 'acf_room');
        $post->facebook_url = self::get_es_acf_field($post, 'acf_facebook_event_url');
        $post->website_url  = self::get_es_acf_field($post, 'acf_external_website');
        $post->twitter_hash = self::get_es_acf_field($post, 'acf_hashtag');

        $post->twitter_url = preg_replace("/#([A-Za-z0-9\/\.]*)/", "<a target=\"_new\" href=\"http://twitter.com/search?q=$1\">#$1</a>", $post->twitter_hash);

        $post->cost           = tribe_get_cost($post, true);
        $post->venue          = tribe_get_venue($post->ID);
        $post->venue_phone    = tribe_get_phone($post->ID);
        $post->map            = tribe_get_embedded_map($post->ID);
        $post->tags           = tribe_meta_event_tags(esc_html__('Tags', 'the-events-calendar'), ', ', false);
        $post->recurring      = tribe_is_recurring_event($post->ID);
        $post->recurrence_url = esc_url(tribe_all_occurences_link($post->ID, false));

        self::get_ctd($post);
        self::get_edit_link($post);
        self::get_author($post);
        self::get_department($post);
        self::get_categories($post);
        self::get_organizers($post);
        self::get_dm($post);
        self::get_dates($post);
        self::get_image($post);

        return $post;
    }

    /**
     * Get field from Elasticsearch or fallback to ACF.
     *
     * @param $post
     * @param $field
     *
     * @return mixed|string
     */
    public static function get_es_acf_field($post, $field) {
        return $post->meta[ $field ]['value'] ?? get_field($field, $post->ID);
    }

    public static function get_organizers(&$post) {
        if (empty($post)) return;

        $organizer_ids = tribe_get_organizer_ids($post->ID);
        $multiple      = count($organizer_ids) > 1;

        $post->organizers_label = tribe_get_organizer_label(! $multiple);

        if (count($organizer_ids)) {
            $organizers = array();
            foreach ($organizer_ids as $organizer) {
                array_push($organizers, tribe_get_organizer_link($organizer));
            }
            $post->organizers = $organizers;
        }

        if ( ! $multiple) {
            $post->organizer_phone   = tribe_get_organizer_phone($post->ID);
            $post->organizer_email   = tribe_get_organizer_email($post->ID);
            $post->organizer_website = tribe_get_organizer_website_link($post->ID);
        }
    }

    /**
     * Convert raw urls into html links
     *
     * @param $content
     *
     * @return array|string|string[]
     */
    public static function get_linked_content($content) {
        $content = M16_Events_Autolink::autolink($content);
        $content = apply_filters('the_content', $content);
        $content = str_replace(']]>', ']]&gt;', $content);

        return $content;
    }

    /**
     * @param WP_Post $post
     */
    public static function get_ctd(WP_Post &$post) {
        if ( ! isset($post->depts_arr)) return;
        if ($post->ctd_site = array_intersect_key(self::$ctd, array_flip($post->depts_arr))) {
            $post->return_site = array(
                'url'  => $post->ctd_site->home,
                'name' => $post->ctd_site->blogname
            );

            // Music/CTD/WCMA/GradArt
            $post->before_title = self::get_es_acf_field($post, 'acf_before_title');
            $post->after_title  = self::get_es_acf_field($post, 'acf_after_title');

            $post->music_cal_cat    = get_term_by('id', self::get_es_acf_field($post, 'music_dept_cal_types'), 'music_dept_cal_types')->name;
            $post->music_event_type = get_term_by('id', self::get_es_acf_field($post, 'acf_music_season_type'), 'music_dept_season_types')->name;
            $post->music_event_cat  = get_term_by('id', self::get_es_acf_field($post, 'acf_music_event_cat'), 'music_dept_cats')->name;
            $post->music_season     = get_term_by('id', self::get_es_acf_field($post, 'acf_music_seasons'), 'music_dept_seasons')->name;
            $post->ctd_series_cat   = get_term_by('id', self::get_es_acf_field($post, 'acf_ctd_series_cats'), 'ctd_series_cats')->name;
            $post->ctd_season       = get_term_by('id', self::get_es_acf_field($post, 'acf_ctd_seasons'), 'ctd_seasons')->name;

            // Extra Stuff
            $post->ticket_url        = self::get_es_acf_field($post, 'acf_tickets_url');
            $post->program_url       = self::get_es_acf_field($post, 'acf_program');
            $post->press_release_url = self::get_es_acf_field($post, 'acf_press_release');

            // Get 62 Center Integrated Events
            if (current($post->ctd_site)->blog_id === '186') {
                global $wpdb;

                // If this is a child, get the parent
                if ($integrated_parent = self::get_es_acf_field($post, 'acf_parent_event')) {
                    $post->integrated_parent = array(
                        'url'   => get_permalink($integrated_parent->ID),
                        'title' => $integrated_parent->post_title
                    );
                }

                // If this is a parent, get the children
                $parent_id = $integrated_parent ? $integrated_parent->ID : $post->ID;
                if ($children = $wpdb->get_results("
                    SELECT p.ID, p.post_title from {$wpdb->posts} p 
                    JOIN {$wpdb->postmeta} pm on p.ID = pm.post_id 
                    WHERE p.post_status = 'publish'
                      AND pm.meta_key = 'acf_parent_event' 
                      AND pm.meta_value = {$parent_id}
                ")) {
                    $post->integrated_children = array();
                    foreach ($children as $child) {
                        if (intval($child->ID) !== $post->ID) {
                            array_push($post->integrated_children, array(
                                'url'   => get_permalink(intval($child->ID)),
                                'title' => $child->post_title
                            ));
                        }
                    }
                }
            }
        }
    }

    /**
     * @param WP_Post $post
     */
    public static function get_edit_link(WP_Post &$post): void {
        $post_type_object = get_post_type_object($post->post_type);
        $edit_link        = $post_type_object->_edit_link ? admin_url(sprintf($post_type_object->_edit_link . '&amp;action=edit', $post->ID)) : '';
        $post->edit_link  = M16_Events_Admin::$is_admin ? "<div class=\"edit-me edit-single\"><a href=\"$edit_link\" target='_blank'>Edit</a></div>" : null;
    }

    /**
     * @param WP_Post $post
     */
    public static function get_author(WP_Post &$post): void {
        if ($user_object = get_user_by('id', $post->post_author)) {
            $user               = $user_object->data;
            $post->user         = $user->user_login;
            $post->author       = stripslashes($user->display_name);
            $post->author_email = stripslashes($user->user_email);
        }
    }

    /**
     * @param WP_Post $post
     */
    public static function get_department(WP_Post &$post): void {
        $post->ldap_department = M16_Events_LDAP::get_ldap_department($post);
        $post->depts_arr       = M16_Events_Event::get_terms($post, 'event_departments');
        $post->depts           = is_array($post->depts) ? join(', ', $post->depts) : '';
        $post->orgs            = join(', ', M16_Events_Event::get_terms($post, 'event_groups'));
    }

    public static function get_categories(WP_Post &$post) {
        $post->cats          = wp_get_post_terms($post->ID, 'event_category');
        $post->category      = join(', ', M16_Events_Event::get_terms($post, 'event_category')); // drm2: I don't think this is used
        $post->category_html = tribe_get_event_categories(
            $post->ID, array(
                'before'       => '',
                'sep'          => ', ',
                'after'        => '',
                'label'        => 'Categories', // An appropriate plural/singular label will be provided
                'label_before' => '<dt class="category-label-before screen-reader-text">',
                'label_after'  => '</dt>',
                'wrap_before'  => '<dd class="tribe-events-event-categories">',
                'wrap_after'   => '</dd>',
                'taxonomy'     => 'event_category',
                'hide_empty'   => false
            )
        );
    }

    public static function get_dm(&$post) {
        $post->is_dm   = has_term('announcement', TribeEvents::TAXONOMY, $post->ID);
        $post->dm_text = stripslashes(M16_Events_Autolink::autolink(tribe_events_get_the_excerpt($post, array())));
        // Announcements show run dates instead of event dates.
        $dates = array();
        // All
        $date_0 = self::get_es_acf_field($post, 'acf_daily_message_run_dates_0');
        // Students only
        $date_1 = self::get_es_acf_field($post, 'acf_daily_message_run_dates_1');
        if ($date_0) {
            array_push($dates, date('l, F jS, Y', strtotime($date_0)));
        }
        if ($date_1) {
            array_push($dates, date('l, F jS, Y', strtotime($date_1)));
        }
        $post->dm_dates = $dates;
    }

    public static function get_image(&$post) {
        $post->thumb_id = self::get_es_acf_field($post, 'acf_event_image');
        $post->img      = M16_Events_Event::tribe_event_featured_image($post->ID, 'large', 'tribe-events-event-image', false, true, $post->thumb_id);
        if ( ! empty($post->thumb_id)) {
            $post->thumb_url           = wp_get_attachment_image_url($post->thumb_id, 'newsmix-featured2');
            $post->thumb_url_uncropped = wp_get_attachment_image_url($post->thumb_id, 'newsmix-uncropped-thumb');
            $post->thumb_url_medium    = wp_get_attachment_image_url($post->thumb_id, 'medium');
        } else {
            $venueid                   = tribe_get_venue_id($post->ID);
            $venue_imageid             = get_post_thumbnail_id($venueid);
            $post->thumb_url           = wp_get_attachment_image_url($venue_imageid, 'newsmix-featured2');
            $post->thumb_url_uncropped = wp_get_attachment_image_url($venue_imageid, 'newsmix-uncropped-thumb');
            $post->thumb_url_medium    = wp_get_attachment_image_url($venue_imageid, 'medium');
        }
    }

    public static function get_dates(&$post) {
        // Main date/time in event header
        $event_dates = array();
        array_push($event_dates, tribe_get_start_date($post));
        $post->event_dates   = $event_dates;
        $datetime_format     = 'Y-m-d\TH:i:s';
        $post->StartDateTime = tribe_get_start_date($post->ID, false, $datetime_format);

        // Mostly for details module
        $time_formatted       = null;
        $time_format          = get_option('time_format', Tribe__Date_Utils::TIMEFORMAT);
        $time_range_separator = tribe_get_option('timeRangeSeparator', ' - ');
        $start_time           = tribe_get_start_date($post->ID, false, $time_format);
        $end_time             = tribe_get_end_date($post->ID, false, $time_format);
        if ($start_time == $end_time) {
            $post->time_formatted = esc_html($start_time);
        } else {
            $post->time_formatted = esc_html($start_time . $time_range_separator . $end_time);
        }

        //$post->time_formatted = apply_filters('tribe_events_single_event_time_formatted', $time_formatted, $post->ID);
        $post->start_date = tribe_get_start_date($post->ID, false); // used once
        $post->start_ts   = tribe_get_start_date($post->ID, false, Tribe__Date_Utils::DBDATEFORMAT); // used once
        $post->end_ts     = tribe_get_end_date($post->ID, false, Tribe__Date_Utils::DBDATEFORMAT); // used once
    }

    /**
     * Returns the singleton instance of this class.
     *
     * @return M16_Events_Post The singleton instance.
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

M16_Events_Post::instance();
