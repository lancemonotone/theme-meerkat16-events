<?php

class M16_Events_Rest {
    private static $instance;
    // Namespace
    const NS = 'wms/events/v1';

    protected function __construct() {
		
        add_action('rest_api_init', function() {
						remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );
            add_filter( 'rest_pre_serve_request', array($this, 'initCors'));
						
            $args = array(
                'start_date'    => array(        // get events after this date
                    'validate_callback' => function($param, $request, $key) {
                        return strtotime($param);
                    }
                ),
                'end_date'      => array(            // get events up to this date
                    'validate_callback' => function($param, $request, $key) {
                        return strtotime($param);
                    }
                ),
                'num_days'      => array(            // get events from current day/start date + num
                    'validate_callback' => function($param, $request, $key) {
                        return is_numeric($param);
                    }
                ),
                'depts'         => array(            // one or more dept ids
                    'validate_callback' => function($param, $request, $key) {
                        if (is_numeric($param)) {
                            return true;
                        }
                        if (is_array($param)) {
                            foreach ($param as $id) {
                                if ( ! is_numeric($id) && ! is_string($id)) {
                                    return false;
                                }
                            }

                            return true;
                        }
                        if (is_string($param)) {
                            return true;
                        }

                        return false;
                    }
                ),
                'venue'         => array(            // one or more dept ids
                    'validate_callback' => function($param, $request, $key) {
                        if (is_numeric($param)) {
                            return true;
                        }
                        if (is_array($param)) {
                            foreach ($param as $id) {
                                if ( ! is_numeric($id) && ! is_string($id)) {
                                    return false;
                                }
                            }

                            return true;
                        }
                        if (is_string($param)) {
                            return true;
                        }

                        return false;
                    }
                ),
                'per_page'      => array( // max events to return
                    'validate_callback' => function($param, $request, $key) {
                        return is_numeric($param);
                    }
                ),
                'featured'      => array( // request featured events
                    'validate_callback' => function($param, $request, $key) {
                        if (in_array($param, array(1, 'on', 'yes', 'true'))) {
                            return true;
                        }

                        return false;
                    }
                ),
                'meta_key'      => array( // custom field key
                    'validate_callback' => function($param, $request, $key) {
                        return is_string($param);
                    }
                ),
                'meta_value'    => array( // custom field value
                    'validate_callback' => function($param, $request, $key) {
                        return is_string($param) || is_numeric($param);
                    }
                ),
                'ignore_sticky' => array(
                    'validate_callback' => function($param, $request, $key) {
                        return is_bool($param);
                    }
                ),
                'audience'      => array( // All, Students, Faculty, Staff
                    'validate_callback' => function($param, $request, $key) {
                        if (is_string($param)) {
                            return true;
                        }
                        if (is_array($param)) {
                            foreach ($param as $id) {
                                if ( ! is_string($id)) {
                                    return false;
                                }
                            }

                            return true;
                        }

                        return false;
                    }
                ),
                'exclude_terms'  => array(
                    'validate_callback' => function($param, $request, $key) {
                        if (is_string($param)) {
                            return true;
                        }
                        if (is_array($param)) {
                            foreach ($param as $id) {
                                if ( ! is_string($id)) {
                                    return false;
                                }
                            }

                            return true;
                        }

                        return false;
                    }
                )
            );
            register_rest_route(self::NS, '/list', array(
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_events'),
                'args'     => $args,
            ));
            register_rest_route(self::NS, '/list/venue/(?P<venue>[a-zA-Z0-9-_]+)', array(
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array($this, 'by_venue'),
                'args'     => $args,
            ));
            register_rest_route(self::NS, '/list/type/(?P<tribe_events_cat>[a-zA-Z0-9-_]+)', array(
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array($this, 'by_type'),
                'args'     => $args,
            ));
            register_rest_route(self::NS, '/list/dept/(?P<event_departments>[0-9-]+)', array(
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array($this, 'by_dept'),
                'args'     => $args,
            ));
            register_rest_route(self::NS, '/list/cat/(?P<event_category>[0-9-]+)', array(
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array($this, 'by_category'),
                'args'     => $args,
            ));
            register_rest_route(self::NS, '/list/tax/(?P<tax_slug>[a-zA-Z0-9-_]+)/term/(?P<term_id>[a-zA-Z0-9-_]+)', array(
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array($this, 'by_tax'),
                'args'     => $args,
            ));
            register_rest_route(self::NS, '/list/org/(?P<event_groups>[0-9-]+)', array(
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array($this, 'by_organization'),
                'args'     => $args,
            ));
            register_rest_route(self::NS, '/list/author/(?P<author_id>[0-9-]+)', array(
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array($this, 'by_author'),
                'args'     => $args,
            ));
            register_rest_route(self::NS, '/list/dm', array(
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array($this, 'by_dm'),
                'args'     => $args,
            ));
            register_rest_route(self::NS, '/dm/(?P<audience>[a-zA-Z]+)', array(
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_dm'),
                'args'     => $args,
            ));
            register_rest_route(self::NS, '/get/terms/(?P<tax_slug>[a-zA-Z0-9-_]+)', array(
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_tax_terms'),
                'args'     => $args,
            ));
            register_rest_route(self::NS, '/get/tax/(?P<tax_slug>[a-zA-Z0-9-_]+)/term/(?P<term>[a-zA-Z0-9-_]+)', array(
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_term'),
                'args'     => $args,
            ));
        });
    }

		function initCors( $value ) {
			//console_log(array(__FUNCTION__, $value));

		  header( 'Access-Control-Allow-Origin: *' );
		  header( 'Access-Control-Allow-Methods: GET' );
		  header( 'Access-Control-Allow-Credentials: true' );
		  return $value;
		}
    /**
     * Get all terms for a particular taxonomy slug.
     *
     * @param WP_REST_Request $request
     *
     * @return array
     */
    public static function get_term(WP_REST_Request $request) {
        if (isset($request['term']) && isset($request['term'])) {
            $type = is_numeric($request['term']) ? 'id' : 'slug';

            return get_term_by($type, $request['term'], $request['tax_slug']);
        }

        return null;
    }

    /**
     * Get terms object for term/taxonomy slug.
     *
     * @param WP_REST_Request $request
     *
     * @return array
     */
    public static function get_tax_terms(WP_REST_Request $request) {
        if (isset($request['tax_slug'])) {
            return get_terms(
                array(
                    'taxonomy'   => $request['tax_slug'],
                    'hide_empty' => false
                )
            );
        }

        return null;
    }

    /**
     * @param WP_REST_Request $request
     *
     * @todo Create list for DMs
     *
     */
    public static function by_dm(WP_REST_Request $request) {
        return self::get_dm($request, false);
    }

    /**
     * @param WP_REST_Request $request
     * @param bool            $html Return HTML if true, JSON object if false
     *
     * @return mixed
     */
    public static function get_dm(WP_REST_Request $request, $html = true) {
        $request->set_param('num_days', '0');
        // Determine DM target audience
        $args['audience'] = $request->get_param('audience') ? $request->get_param('audience') : 'all';

        $dates = self::parse_request_dates($request);
        $args  = array_merge($args, self::parse_dates($dates, 'Ymd'));

        if ( ! $html) {
            $args = wp_parse_args($args, M16_Events_Daily_Messages::$default_args);
        }

        return $html ? apply_filters('get_dm_html', $args) : apply_filters('get_dm_json', $args);
    }

    /**
     * Process request parameters. Return posts.
     *
     * @param WP_REST_Request $request
     *
     * @param array           $args
     *
     * @return array|bool
     */
    public static function get_events(WP_REST_Request $request, $args = array()) {
        //
        // Number of events to retrieve
        //
        $args = wp_parse_args($args, array(
            'hide_upcoming'       => $request->get_param('hide_upcoming'),
            'posts_per_page'      => $request->get_param('per_page'),
            'meta_key'            => $request->get_param('meta_key'),
            'meta_value'          => $request->get_param('meta_value'),
            'featured'            => $request->get_param('featured'),
            'ignore_sticky_posts' => $request->get_param('ignore_sticky') ? $request->get_param('ignore_sticky') : true
        ));

        // Exclude terms if set
        if ( ! empty($request->get_param('exclude_terms'))) {
            $args['tax_query']['relation'] = 'AND';
            $tax_query_01                  = array(
                'taxonomy' => $request['tax_slug'],
                'terms'    => $request->get_param('exclude_terms'),
                'field'    => 'term_id',
                'operator' => 'NOT IN',
            );
            $args['tax_query'] []          = $tax_query_01;
        }

        $dates = self::parse_request_dates($request);
        $args  = array_merge($args, self::parse_dates($dates, 'Ymd'));
        if ($depts = $request->get_param('depts')) {
            $args = array_merge($args, self::parse_depts($depts));
        }
        if ($venues = $request->get_param('venues')) {
            $args = array_merge($args, self::parse_venues($venues));
        }

        $event_posts = tribe_get_events($args);

        return $event_posts;

    }

    /**
     * @param WP_REST_Request $request
     * @param                 $args
     *
     * @return array
     */
    public static function parse_depts($depts) {
        $result = array();
        if ($depts) {
            // Look at first element, are we passing the term id or the slug?
            $depts = (array) $depts;
            $field = is_numeric($depts[0]) ? 'term_id' : 'slug';

            // look in dept param
            $result['tax_query'] = array(
                'relation' => 'OR',
                array(
                    'taxonomy' => 'tribe_events_cat',
                    'field'    => $field,
                    'terms'    => $depts,
                ),
                array(
                    'taxonomy' => 'event_departments',
                    'field'    => $field,
                    'terms'    => $depts,
                ),
                array(
                    'taxonomy' => 'event_category',
                    'field'    => $field,
                    'terms'    => $depts,
                ),
            );
        }

        return $result;
    }

    public static function parse_venues($venues) {
        $result = array();
        if ($venues) {
            $result['venue'] = $venues;
        }

        return $result;
    }

    public static function by_venue(WP_REST_Request $request) {
        $args = array();

        if (isset($request['venue'])) {
            if ( ! $request->get_param('end_date')) {
                $request->set_param('end_date', '3000-01');
            }
            $request->set_param('venues', $request['venue']);
        }

        return self::get_events($request, $args);
    }

    /**
     * Filter by Event Type slug.
     *
     * @param WP_REST_Request $request
     *
     * @return array|bool
     */
    public static function by_type(WP_REST_Request $request) {
        $args['tax_query'] = array();

        // Build taxonomy query if any
        if (isset($request['tribe_events_cat'])) {
            array_push($args['tax_query'], array(
                'taxonomy' => 'tribe_events_cat',
                'field'    => 'slug',
                'terms'    => $request['tribe_events_cat'],
            ));
        }

        return self::get_events($request, $args);
    }

    /**
     * Filter by Event Type slug.
     *
     * @param WP_REST_Request $request
     *
     * @return array|bool
     */
    public static function by_author(WP_REST_Request $request) {
        $args = array();

        // Build taxonomy query if any
        if (isset($request['author_id'])) {
            $args['author'] = $request['author_id'];
        }

        if ( ! $request->get_param('start_date')) {
            $request->set_param('start_date', '1970-12-31');
        }
        if ( ! $request->get_param('end_date')) {
            $request->set_param('end_date', '2050-12-31');
        }

        return self::get_events($request, $args);
    }

    /**
     * Filter by Department term_id.
     *
     * @param WP_REST_Request $request
     *
     * @return array|bool
     */
    public static function by_dept(WP_REST_Request $request) {
        $args['tax_query'] = array();

        // Build taxonomy query if any
        if (isset($request['event_departments'])) {
            array_push($args['tax_query'], array(
                'taxonomy' => 'event_departments',
                'field'    => 'term_id',
                'terms'    => $request['event_departments'],
            ));
        }

        return self::get_events($request, $args);
    }

    /**
     * Filter by Category term_id.
     *
     * @param WP_REST_Request $request
     *
     * @return array|bool
     */
    public static function by_category(WP_REST_Request $request) {
        $args['tax_query'] = array();

        // Build taxonomy query if any
        if (isset($request['event_category'])) {
            array_push($args['tax_query'], array(
                'taxonomy' => 'event_category',
                'field'    => 'term_id',
                'terms'    => $request['event_category'],
            ));
        }

        return self::get_events($request, $args);
    }

    /**
     * Filter by specified taxonomy and term_id.
     *
     * @param WP_REST_Request $request
     *
     * @return array|bool
     */
    public static function by_tax(WP_REST_Request $request) {
        $args['tax_query'] = array();

        // Build taxonomy query if any
        if (isset($request['tax_slug']) && isset($request['term_id'])) {
            if ($request['term_id'] === 'all') {
                array_push($args['tax_query'], array(
                    'taxonomy' => $request['tax_slug'],
                    'operator' => 'EXISTS'
                ));
            } else {
                array_push($args['tax_query'], array(
                    'taxonomy' => $request['tax_slug'],
                    'field'    => 'term_id',
                    'terms'    => $request['term_id'],
                ));
            }
        }

        return self::get_events($request, $args);
    }

    /**
     * Filter by Organization term_id.
     *
     * @param WP_REST_Request $request
     *
     * @return array|bool
     */
    public static function by_organization(WP_REST_Request $request) {
        $args['tax_query'] = array();

        // Build taxonomy query if any
        if (isset($request['event_groups'])) {
            array_push($args['tax_query'], array(
                'taxonomy' => 'event_groups',
                'field'    => 'term_id',
                'terms'    => $request['event_groups'],
            ));
        }

        return self::get_events($request, $args);
    }

    /**
     * Disambiguate $rest request into vars
     *
     * @param WP_REST_Request $request
     */
    public static function parse_request_dates(WP_REST_Request $request) {
        return array(
            'num_days'   => $request->get_param('num_days'),
            'start_date' => $request->get_param('start_date'),
            'end_date'   => $request->get_param('end_date')
        );
    }

    /**
     * Get events from today + num_days OR the ranged defined by start_date and end_date
     *
     * @param WP_REST_Request $request
     * @param                 $args
     *
     * @return array
     */
    public static function parse_dates($dates, $format = 'Y-m-d H:i') {
        // $num_days is request or null.
        // Test for null, because 0 is falsy.
        $num_days = isset($dates['num_days']) && $dates['num_days'] !== null ? (int) $dates['num_days'] : null;

        // $start_date is request or today.
        if (isset($dates['start_date']) && $dates['start_date']) {
            $start_date = $dates['start_date'];
        } else {
            // Get local time
            $dt         = new DateTime('now', new DateTimeZone('America/New_York'));
            $start_date = $dt->format($format);
        }

        // $end_date is $start_date + $num_days, or $end_date, or $start_date.
        // $num_days overrides end_date.
        if (is_int($num_days)) {
            $end_date = $start_date . " +$num_days day";
        } else if (isset($dates['end_date']) && $dates['end_date']) {
            $end_date = $dates['end_date'];
        } else {
            $end_date = '+30 year';
        }

        $args['start_date'] = date($format, strtotime($start_date));
        $args['end_date']   = date($format, strtotime($end_date));

        return $args;
    }

    /**
     * Returns the singleton instance of this class.
     *
     * @return M16_Events_Rest The singleton instance.
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

M16_Events_Rest::instance();
