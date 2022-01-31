<?php

/**
 * Class M16_Events_Filter_Columns
 *
 * This adds Advanced Post Manager (APM) column filters and headings to
 * the Dashboard Events lists (i.e., https://events.williams.edu/wp-admin/edit.php?post_type=tribe_events)
 */
class M16_Events_Filter_Columns {
    private static $instance;

    protected function __construct() {
        M16_Events_Privacy_Filters::instance();
        new M16_Events_Meta_Date_Filters(array(
            'type' => 'daily_message_date_0',
            'column_name' => 'DM Run 1',
            'meta' => 'acf_daily_message_run_dates_0',
            'key' => 'daily_message_date_key_0')
        );
        new M16_Events_Meta_Date_Filters(array(
            'type' => 'daily_message_date_1',
            'column_name' => 'DM Run 2',
            'meta' => 'acf_daily_message_run_dates_1',
            'key' => 'daily_message_date_key_1')
        );
    }

    /**
     * Returns the singleton instance of this class.
     *
     * @return M16_Events_Filter_Columns The singleton instance.
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

M16_Events_Filter_Columns::instance();

/**
 * Class M16_Events_Daily_Message_Filters
 *
 * Note: This is lifted almost verbatim from plugins/events-calendar-pro/src/Tribe/APM_Filters/Date_Filter.php
 */

class M16_Events_Meta_Date_Filters {
    private $instance;
    public $type;
    public $key;
    public $column_name;
    public $meta;
    public $active = array();

    private $query_search_options = array();

    public function __construct($config) {
        $type = $this->type = $config['type'];
        $this->meta = $config['meta'];
        $this->key = $config['key'];
        $this->column_name = $config['column_name'];

        $this->query_search_options = array(
            'is'  => esc_html__('Is', 'tribe-events-calendar-pro'),
            'not' => esc_html__('Is Not', 'tribe-events-calendar-pro'),
            'gte' => esc_html__('On and After', 'tribe-events-calendar-pro'),
            'lte' => esc_html__('On and Before', 'tribe-events-calendar-pro'),
        );

        add_filter('tribe_events_pro_apm_filters_args', array($this, 'add_filter_args'), 10, 1);
        add_filter('tribe_custom_column' . $type, array($this, 'column_value'), 10, 3);
        add_filter('tribe_custom_row' . $type, array($this, 'form_row'), 10, 4);
        add_filter('tribe_maybe_active' . $type, array($this, 'maybe_set_active'), 10, 3);
        add_action('tribe_after_parse_query', array($this, 'parse_query'), 10, 2);
    }

    public function add_filter_args($filter_args) {
        $filter_args[ $this->key ] = array(
            'name'        => $this->column_name,
            'custom_type' => $this->type,
            'sortable'    => true
        );

        return $filter_args;
    }

    public function form_row($return, $key, $value, $filter) {
        $value  = (array) $value;
        $value  = wp_parse_args($value, array('is' => '', 'value' => '', 'is_date_field' => true));
        $return = tribe_select_field('is_' . $key, $this->query_search_options, $value['is']);
        $return .= sprintf('<input name="%s" value="%s" type="text" class="date tribe-datepicker" />', $key, esc_attr($value['value']));

        return $return;
    }

    public function maybe_set_active($return, $key, $filter) {
        global $ecp_apm;

        if ( ! empty( $_POST[ $key ] ) && ! empty( $_POST[ 'is_' . $key ] ) ) {
            return array( 'value' => $_POST[ $key ], 'is' => $_POST[ 'is_' . $key ], 'is_date_field' => true );
        }

        $active_filters = $ecp_apm->filters->get_active();

        if ( ! empty( $active_filters[ $key ] ) && ! empty( $active_filters[ 'is_' . $key ] ) ) {
            return array( 'value' => $active_filters[ $key ], 'is' => $active_filters[ 'is_' . $key ], 'is_date_field' => true );
        }

        return $return;
    }

    public function parse_query($wp_query_current, $active) {
        if (empty($active)) {
            return;
        }

        foreach ($active as $key => $field) {
            if (isset($field['is_date_field'])) {
                $this->active[ $key ] = $field;
            }
        }

        add_filter('posts_join', array($this, 'join'), 10, 2);
        add_filter('posts_where', array($this, 'where'), 10, 2);
    }

    public function join($join, WP_Query $wp_query) {
        // bail if this is not a query for event post type
        if ($wp_query->get('post_type') !== Tribe__Events__Main::POSTTYPE) {
            return $join;
        }

        global $ecp_apm;

        $active_filters = array();

        if (isset($ecp_apm) && isset($ecp_apm->filters)) {
            $active_filters = $ecp_apm->filters->get_active();
        }

        if (empty($_POST[ $this->key ]) && empty($active_filters[ $this->key ])) {
            return $join;
        }

        global $wpdb;
        $meta = $this->meta;
        $key = $this->key;
        $join .= " LEFT JOIN {$wpdb->postmeta} AS {$key} ON({$wpdb->posts}.ID = {$key}.post_id AND {$key}.meta_key='{$meta}') ";

        return $join;
    }

    public function where($where, WP_Query $wp_query) {
        // bail if this is not a query for event post type
        if ($wp_query->get('post_type') !== Tribe__Events__Main::POSTTYPE) {
            return $where;
        }

        global $ecp_apm, $wpdb;
        // run once
        remove_filter('posts_where', array($this, 'where'), 10);

        foreach ($this->active as $key => $active) {

            $field = '';

            if ($key === $this->key) {
                $field = "{$key}.meta_value";
            }

            if (empty($field)) {
                continue;
            }

            //$value = date('Ymd', strtotime($active['value']));
            $value = $active['value'];

            switch ($active['is']) {
                case 'is':
                    $where .= $wpdb->prepare(" AND $field = CAST(%s as DATE) ", $value);
                    break;
                case 'not':
                    $where .= $wpdb->prepare(" AND $field NOT = CAST(%s as DATE) ", $value);
                    break;
                case 'gte':
                    $where .= $wpdb->prepare(" AND $field >= CAST(%s as DATE) ", $value);
                    break;
                case 'lte':
                    $where .= $wpdb->prepare(" AND $field <= CAST(%s as DATE) ", $value);
                    break;
            }
        }

        return $where;
    }

    public function column_value($value, $column_id, $post_id) {
        $key = get_post_meta($post_id, $this->meta, true);

        if ($key) {
            return date('D, F jS, Y', strtotime($key));
        } else {
            return '—';
        }
    }

    public function log($data = array()) {
        error_log(print_r($data, 1));
    }


    /**
     * Returns the singleton instance of this class.
     *
     * @return M16_Events_Filter_Columns The singleton instance.
     */
    public static function instance($config) {
        if (null === static::$instance) {
            static::$instance = new static($config);
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

class M16_Events_Privacy_Filters {
    private static $instance;
    public static $key = 'privacy_filter_key';
    public static $type = 'privacy_filter';
    public static $meta = '_EventHideFromUpcoming';
    public static $active = array();
    public static $opts;

    public function __construct() {
        $type       = self::$type;

        self::$opts = array(
            'no'  => esc_html__('No', 'tribe-events-calendar-pro'),
            'yes' => esc_html__('Yes', 'tribe-events-calendar-pro'),
        );

        add_filter('tribe_events_pro_apm_filters_args', array(&$this, 'add_filter_args'), 10, 1);
        add_filter('tribe_custom_column' . $type, array($this, 'column_value'), 10, 3);
        add_filter('tribe_custom_row' . $type, array($this, 'form_row'), 10, 4);
        add_filter('tribe_maybe_active' . $type, array($this, 'maybe_set_active'), 10, 3);
        add_action('tribe_after_parse_query', array($this, 'parse_query'), 10, 2);
    }

    public static function add_filter_args($filter_args) {
        $filter_args['privacy_filter_key'] = array(
            'name'        => esc_html__('Private'),
            'custom_type' => 'privacy_filter',
            'sortable'    => true
            //'disable'     => 'columns',
        );

        return $filter_args;
    }

    public static function maybe_set_active($return, $key, $filter) {
        global $ecp_apm;

        if ( ! empty($_POST[ self::$key ])) {
            return $_POST[ self::$key ];
        }

        $active_filters = $ecp_apm->filters->get_active();

        if ( ! empty($active_filters[ self::$key ])) {
            return $active_filters[ self::$key ];
        }

        return $return;
    }

    public function parse_query($wp_query_current, $active) {
        if (empty($active[ self::$key ])) {
            return;
        }

        self::$active = $active;

        add_filter('posts_join', array($this, 'join_privacy'), 10, 2);
        add_filter('posts_where', array($this, 'where_privacy'), 10, 2);
    }

    public static function join_privacy($join, WP_Query $wp_query) {
        // bail if this is not a query for event post type
        if ($wp_query->get('post_type') !== Tribe__Events__Main::POSTTYPE) {
            return $join;
        }

        global $ecp_apm;

        $active_filters = array();

        if (isset($ecp_apm) && isset($ecp_apm->filters)) {
            $active_filters = $ecp_apm->filters->get_active();
        }

        if (empty($_POST[ self::$key ]) && empty($active_filters[ self::$key ])) {
            return $join;
        }

        global $wpdb;
        $meta = self::$meta;
        $join .= " LEFT JOIN {$wpdb->postmeta} AS privacy_meta ON({$wpdb->posts}.ID = privacy_meta.post_id AND privacy_meta.meta_key='{$meta}') ";

        return $join;
    }

    public static function where_privacy($where, WP_Query $query) {
        // bail if this is not a query for event post type
        if ($query->get('post_type') !== Tribe__Events__Main::POSTTYPE) {
            return $where;
        }

        if (self::$active[ self::$key ] === 'yes') {
            $where .= ' AND privacy_meta.meta_key IS NOT NULL';
        } else if (self::$active[ self::$key ] === 'no') {
            $where .= ' AND privacy_meta.meta_key IS NULL';
        }

        return $where;

    }

    public static function form_row($return, $key, $value, $filter) {
        // in case we have a blank row
        $value = (string) $value;

        return tribe_select_field(self::$key, self::$opts, $value);
    }

    public static function column_value($value, $column_id, $post_id) {
        $privacy = get_post_meta($post_id, self::$meta, true);

        if ($privacy) {
            return 'Private';
        } else {
            return '—';
        }
    }

    public static function log($data = array()) {
        error_log(print_r($data, 1));
    }


    /**
     * Returns the singleton instance of this class.
     *
     * @return M16_Events_Filter_Columns The singleton instance.
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