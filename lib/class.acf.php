<?php

class M16_Events_ACF {
    private static $instance;

    protected function __construct() {
        remove_action('tribe_events_update_meta', array('Tribe__Events__Pro__Custom_Meta', 'save_single_event_meta'));
        add_action('tribe_events_update_meta', array(&$this, 'save_single_event_meta'), 30, 3);

        // Save DB cycles by not tracking field changes.
        add_filter('tribe_tracker_enabled', '__return_false');
        add_filter('acf/validate_value/name=acf_private', array(&$this, 'validate_private'), 10, 4);
        add_filter('acf/validate_value/name=event_type', array(&$this, 'validate_event_type'), 10, 4);
        add_filter('acf/validate_value/name=acf_daily_message_run_dates_0', array(&$this, 'validate_run_date'), 10, 4);

        // Friendlier errors by scrolling down to blank required fields
        //add_filter('tribe_events_community_required_fields', array(&$this, 'my_community_required_fields'), 10, 1);
        //add_filter('acf/validate_value/name=acf_headline', array(&$this, 'validate_headline'), 10, 4);
        //add_filter('acf/validate_value/name=acf_description', array(&$this, 'validate_description'), 10, 4);
    }

    public static function my_community_required_fields($fields) {
        if (($key = array_search('post_content', $fields)) !== false) {
            unset($fields[ $key ]);
        }
        if (($key = array_search('post_title', $fields)) !== false) {
            unset($fields[ $key ]);
        }

        return $fields;
    }

    public static function validate_headline($valid, $value, $field, $input) {
        if ( ! $valid) {
            return $valid;
        }

        if ( ! $value) {
            $valid = __('This field is required for all events');
        }

        return $valid;
    }

    public static function validate_event_type($valid, $value, $field, $input) {
        if ( ! $valid) {
            return $valid;
        }

        if ( ! $value) {
            $valid = __('This field is required');
        }

        return $valid;
    }

    public static function validate_private($valid, $value, $field, $input) {
        if ( ! $valid) {
            return $valid;
        }
        $type_key = self::acf_get_field_key('event_type', $_REQUEST['post_id']);
        $type     = $_REQUEST['acf'][ $type_key ];

        if ($type !== 'announcement') {
            if ( ! $value) {
                $valid = __('This field is required for all events');
            }
        }

        return $valid;
    }

    public static function validate_run_date($valid, $value, $field, $input) {
        if ( ! $valid) {
            return $valid;
        }
        $audience_key = self::acf_get_field_key('acf_dm_audience', $_REQUEST['post_id']);
        $audience     = $_REQUEST['acf'][ $audience_key ];

        if ($audience) {
            if ( ! $value) {
                $valid = __('This field is required for Daily Messages');
            }
        }

        return $valid;
    }


    /**
     * Saves the custom fields for a single event.
     *
     * In the case of fields where multiple values have been assigned (or even if only
     * a single value was assigned - but the field type itself supports multiple
     * values, such as a checkbox field) an additional set of records will be created
     * storing each value in a separate row of the postmeta table.
     *
     * @param $event_id
     * @param $data
     *
     * @return void
     * @see 'tribe_events_update_meta'
     */
    public static function save_single_event_meta($event_id, $data, $update) {
        // This function will run twice depending on whether the form is admin or front-end.
        M16_Events_Event::$event_id = $event_id;
        $event                      = get_post($event_id);

        if ( ! metadata_exists('post', $event_id, 'event_created')) {
            $date = new DateTime();
            $date->setTimezone(new DateTimeZone('America/New_York'));
            $formatted = $date->format('Y-m-d H:i:s');
            add_post_meta($event_id, 'event_created', $formatted);
        }

        // Remove empty key/value pairs
        if ( ! empty($_REQUEST['acf'])) {
            $_REQUEST['acf'] = array_filter($_REQUEST['acf'], function($val) {
                return $val !== '';
            });

            foreach ($_REQUEST['acf'] as $key => $value) {
                update_field($key, $value, $event_id);

                switch (get_field_object($key)['name']) {
                    case 'acf_is_dm':
                        // If is_dm is true but user didn't toggle an audience, assign all choices.
                        $is_dm    = get_field_object('acf_is_dm')['value'] === true;
                        $audience = get_field_object('acf_dm_audience');
                        $value    = $is_dm && $audience['value'] === '' ? array_keys($audience['choices']) : '';
                        update_field($audience['key'], $value, $event_id);
                        self::save_terms($value, 'daily_message_audience', $event_id);
                        break;
                    /*case 'acf_headline':
                        $update_event = array(
                            'ID'         => $event_id,
                            'post_title' => $value,
                        );
                        wp_update_post($update_event);
                        break;
                    case 'acf_description':
                        $update_event = array(
                            'ID'           => $event_id,
                            'post_content' => $value,
                        );
                        wp_update_post($update_event);
                        break;*/
                    case 'event_type':
                        self::save_terms($value, 'tribe_events_cat', $event_id);
                        break;
                    case 'acf_dm_audience':
                        self::save_terms($value, 'daily_message_audience', $event_id);
                        break;
                    case 'acf_private':
                        if ($value === 'yes') {
                            update_metadata('post', $event_id, '_EventHideFromUpcoming', 'yes');
                        } else {
                            delete_metadata('post', $event_id, '_EventHideFromUpcoming');
                        }
                }
            }
        }

        // Sync Title
        $title = $event->post_title;
        if (get_field('acf_headline', $event_id) !== $event->post_title) {
            update_field('acf_headline', $title, $event_id);
        }

        // Sync
        $description = apply_filters('the_content', $event->post_content);
        if (get_field('acf_description', $event_id) !== $description) {
            update_field('acf_description', $description, $event_id);
        }

        // Make Private/Campus-only
        if (get_field('acf_private', $event_id) === 'yes') {
            update_metadata('post', $event_id, '_EventHideFromUpcoming', 'yes');
        }

        // Translate request to Tribe stickiness
        if (isset($_REQUEST['EventShowInCalendar']) && $_REQUEST['EventShowInCalendar'] == 'yes' && $event->menu_order != '-1') {
            $update_event = array(
                'ID'         => $event_id,
                'menu_order' => '-1',
            );
            wp_update_post($update_event);
        } elseif (( ! isset($_REQUEST['EventShowInCalendar']) || $_REQUEST['EventShowInCalendar'] != 'yes') && $event->menu_order == '-1') {
            $update_event = array(
                'ID'         => $event_id,
                'menu_order' => '0',
            );
            wp_update_post($update_event);
        }

        // Set featured status.
        empty($_REQUEST['feature_event'])
            ? tribe('tec.featured_events')->unfeature($event_id)
            : tribe('tec.featured_events')->feature($event_id);

        // Only run notification
        do_action('prepare_notification', $event_id, $event);
    }

    /**
     * Convert form-friendly ACF field to event_type taxonomy term
     * for use when querying. Replaces existing terms.
     *
     * @param $acf_field
     * @param $acf_terms
     * @param $post_id
     *
     * @return array|false|WP_Error
     */
    public static function save_terms($acf_terms, $tax_slug, $post_id) {
        // Loop through all terms from taxonomy
        // If the current term matches an ACF field value
        // attached to the post, add it to the stack.
        $term_ids = array();
        foreach ((array) $acf_terms as $term) {
            if ($term_id = term_exists($term, $tax_slug)['term_id']) {
                array_push($term_ids, $term_id);
            }
        }

        return wp_set_post_terms($post_id, $term_ids, $tax_slug);

    }

    /**
     * Get field key for field name.
     * Will return first matched acf field key for a given field name.
     *
     * ACF requires a field key, where a sane developer would prefer a human readable field name.
     * http://www.advancedcustomfields.com/resources/update_field/#field_key-vs%20field_name
     *
     * This function will return the field_key of a certain field.
     *
     * @see https://gist.github.com/mcguffin/81509c36a4a28d9c682e
     *
     * @param $field_name String ACF Field name
     * @param $post_id int The post id to check.
     *
     * @return
     */
    public static function acf_get_field_key($field_name, $post_id) {
        global $wpdb;
        $acf_fields = $wpdb->get_results($wpdb->prepare("SELECT ID,post_parent,post_name FROM $wpdb->posts WHERE post_excerpt=%s AND post_type=%s", $field_name, 'acf-field'));
        // get all fields with that name.
        switch (count($acf_fields)) {
            case 0: // no such field
                return false;
            case 1: // just one result.
                return $acf_fields[0]->post_name;
        }
        // result is ambiguous
        // get IDs of all field groups for this post
        $field_groups_ids = array();
        $field_groups     = acf_get_field_groups(array(
            'post_id' => $post_id,
        ));
        foreach ($field_groups as $field_group)
            $field_groups_ids[] = $field_group['ID'];

        // Check if field is part of one of the field groups
        // Return the first one.
        foreach ($acf_fields as $acf_field) {
            if (in_array($acf_field->post_parent, $field_groups_ids))
                return $acf_field->post_name;
        }

        return false;
    }

    /**
     * Returns the singleton instance of this class.
     *
     * @return M16_Events_ACF The singleton instance.
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

M16_Events_ACF::instance();