<?php

/**
 * Class M16_Events_Email
 * @uses Wms_Server
 * @uses M16_Events_Rest
 */
class M16_Events_Daily_Messages {
    const EVENT_TYPE_TAX = 'event_category';
    const DM_AUDIENCE_TAX = 'daily_message_audience';
    const EVENT_CATEGORY_TAX = 'event_category';
    const EVENT_DEPARTMENT_TAX = 'event_departments';
    const IS_DM = 'acf_is_dm';
    const DM_LOGO = 'https://www.williams.edu/wp-content/themes/meerkat16-events/assets/build/img/email_logo_2021.png';
    const ARROW_UP = 'https://www.williams.edu/wp-content/themes/meerkat16-events/assets/build/img/blacktie-arrow_up_right_2020.png';
    const WHITE = '#ffffff';
    const BLACK = '#434343';
    const BLACK_W_ALPHA = "#5a6265";
    const LINKS = '#516693';
    const IRON = '#9da2a2';
    const WATTLE = '#ddcf57';
    const ORANGERED = '#cf432b';

    /**
     * The DM Archive filter ACF fields must be translated to Tribe fields in order
     * to perform a query on the values saved with each submission. We don't include
     * the keyword field from the filter because there is no search term field in the
     * submission (we instead use the 's' parameter in WP_Query).
     */
    private static $acf_to_tribe = array(
        'dm_filter_audience' => 'audience',
        'dm_form_category'   => self::EVENT_CATEGORY_TAX,
        'dm_form_department' => self::EVENT_DEPARTMENT_TAX,
        'dm_form_start_date' => 'start_date',
        'dm_form_end_date'   => 'end_date'
    );

    private static $translated = array();

    public static
        $css = array(
        'tablewrap'     => "border-collapse:collapse;mso-table-lspace:0;mso-table-rspace:0;max-width:690px;margin-top:0;margin-bottom:0;margin-right:auto;margin-left:auto;table-layout:fixed;overflow:hidden;font-family: Helvetica Neue, Arial, Open Sans, sans-serif;color: " . self::BLACK . ";font-size: 14px;",
        'ol'            => "margin-left:1.5em;padding-left:0",
        'li_top'        => "font-size:auto;margin-bottom:1em;position: relative;",
        'li_bottom'     => "font-size:auto;margin-bottom:1.75em;position: relative;",
        'hr'            => "height: 1px;background-color: " . self::IRON . ";border: none;margin: 2em 0 1em;",
        'a'             => "color: " . self::LINKS . ";text-decoration: none;",
        'strong'        => "margin-top: .2em;display: inline-block; color:#999;",
        'table'         => "width:auto; min-width: 200px; border-collapse:collapse; mso-table-lspace:0; mso-table-rspace:0; max-width:100%; margin-top:0; margin-bottom:0; margin-right:auto; margin-left:auto; table-layout:fixed; overflow:hidden; width:100%",
        'hero'          => "padding:19px 0;",
        'logo'          => "",
        "datetext"      => "font-size: small;font-weight:bold;",
        'view_online'   => "font-size: 10px; letter-spacing: 3px;color: " . self::IRON . "; text-transform: uppercase;",
        'jump_link'     => "text-decoration: none; display: inline-block; color: " . self::LINKS . "; height: 1.2em; padding: 0; line-height: 17px;margin-left: 2px;",
        'btb'           => "display: inline-block; font: normal normal normal 18px/1 'Black Tie'; vertical-align: middle; text-rendering: auto; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;",
        'heading'       => "color: #000000; display: inline-block; /*font-size: 12px; font-weight: 400; text-transform: uppercase; letter-spacing: 2.5px; line-height: 19px; padding-bottom: 3px; margin: 1em 0; border-bottom: 1px solid " . self::BLACK_W_ALPHA . ";*/",
        "toc"           => "/*font-size: auto; font-weight: 600; color: " . self::BLACK . ";letter-spacing: 1px;*/ line-height: 1.3;",
        "title"         => "font-size: auto; font-weight: 600; color: " . self::LINKS . "; text-decoration: none; /*letter-spacing: 1px;*/ line-height: 1.3;",
        "bodytext"      => "line-height: 1.4;font-weight: 500;",
        "link"          => "text-decoration: none; color: " . self::LINKS . ";",
        "count"         => "width: 2em; position:absolute;left: 0; line-height:23px;",
        "text_w_indent" => "display: inline-table;margin-left: 20px; position: relative;",
    ),
        $is_admin,
        $instance,
        $url,
        $debug,
        $file,
        $ldap,
        $ga_source = 'daily_messages',
        $ga_medium = 'email',
        $ga_campaign = 'dm_',
        $ga_term = '',
        $ga_content = '',
        $start_date = 'today',
        $end_date,
        $format = 'Ymd',
        $audience = array('students', 'faculty', 'staff'),
        $hash,
        $is_rest_api,
        $default_args = array(
        'start_date'     => null,
        'end_date'       => null,
        'num_days'       => '0',
        'posts_per_page' => -1,
        'meta_key'       => 'acf_is_dm',
        'meta_value'     => '1',
        'audience'       => 'all'
    );

    public function __construct($args = array()) {
        self::$url                        = get_rest_url() . M16_Events_Rest::NS . '/list?';
        self::$debug                      = isset($_REQUEST['dm']);
        self::$file                       = get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'email' . DIRECTORY_SEPARATOR;
        self::$ldap                       = new WilliamsPeopleDirectory();
        self::$hash                       = md5('williams_daily_messages');
        self::$default_args['start_date'] = self::$start_date;
        self::$default_args['end_date']   = self::$end_date;
        add_filter('get_dm_html', array(&$this, 'get_dm_html'), 10, 1);
        add_filter('get_dm_json', array(&$this, 'get_dm_json'), 10, 1);
        add_filter('tribe_get_option', array(&$this, 'dateTimeSeparator'), 10, 3);

        // Preload DM filter request values into the filter if they have been sent.
        if ( ! empty($_REQUEST['dm_filter_form']) && $_REQUEST['acf']) {
            add_filter('acf/pre_load_value', array(&$this, 'populate_filter_form_field'), 20, 3);
            add_filter('acf/update_value', array(&$this, 'prevent_keyword_save'), 20, 3);
        }
    }

    public static function prevent_keyword_save($value, $post_id, $field) {
        $x = 0;

        return null;
    }

    /**
     * Hook from ACF to return value of field if it is set as a request param.
     * This will allow us to repopulate the form and save its state between requests.
     *
     * Returning null will cause ACF to load value normally.
     *
     * @param $null
     * @param $post_id
     * @param $field
     *
     * @return null
     */
    public static function populate_filter_form_field($null, $post_id, $field) {
        if (array_key_exists($field['_name'], self::$translated)) {
            return isset(self::$translated[ $field['_name'] ]) ? self::$translated[ $field['_name'] ] : null;
        } else if (array_key_exists($field['name'], self::$acf_to_tribe)) {
            return isset(self::$translated[ self::$acf_to_tribe[ $field['_name'] ] ]) ? self::$translated[ self::$acf_to_tribe[ $field['_name'] ] ] : null;
        }

        return null;
    }

    /**
     * Get DM filter variables and translate from ACF field names into Tribe field names.
     *
     * @return array
     */
    public static function translate_acf_form_fields($request) {

        if (isset($request['dm_filter_form'])) {
            self::$translated = array();
            $has_value        = false;
            foreach ($request['acf'] as $key => $value) {
                if ( ! $value) continue;

                $key                      = self::translate_filter($key);
                self::$translated[ $key ] = $value;
                $has_value                = true;
            }

            if ($has_value) {
                // Prevent form from saving data.
                unset($_REQUEST['acf']);

                return self::$translated;
            }
        }

        return $request;
    }

    /**
     * If the key matches an entry in the ACF to Tribe translation array, send back
     * the translated value
     *
     * @param $key
     *
     * @return mixed
     */
    public static function translate_filter($key) {
        $field_obj_name = get_field_object($key, null, null, false)['name'];

        if (key_exists($field_obj_name, self::$acf_to_tribe)) {
            return self::$acf_to_tribe[ $field_obj_name ];
        } else {
            return $field_obj_name;
        }
    }

    /**
     * Parse filter form request args and translate them into usable query params.
     *
     * @param $request
     *
     * @return array|mixed
     */
    public static function parse_filter_form_args($request) {
        // Bail if this isn't a filter_form request or if the form has been reset
        if ( ! isset($request['acf']) || $request['dm_filter_form'] === '0') return $request;

        $args = self::translate_acf_form_fields($request);
        $args = self::parse_filter_form_dates($args);
        $args = self::populate_tax_query($args);

        return $args;
    }

    /**
     * Build a tax query if Categories or Departments are selected
     *
     * @param $args
     *
     * @return mixed
     */
    public static function populate_tax_query($args) {
        $tax_query = array();

        if (array_key_exists(self::EVENT_DEPARTMENT_TAX, $args)) {
            array_push($tax_query, self::build_tax_query(self::EVENT_DEPARTMENT_TAX, $args[ self::EVENT_DEPARTMENT_TAX ]));
        }

        if (count($tax_query)) $args['tax_query'] = $tax_query;

        return $args;
    }

    public static function build_tax_query($cat, $terms) {
        return array(
            'taxonomy' => $cat,
            'terms'    => $terms,
            'operator' => 'IN',
            'field'    => 'term_id'
        );
    }

    /**
     * Date params via the filter form work differently than
     * using $_GET params: submitting a start_date but no end_date
     * via the form will return all events from start_date to midnight
     * tonight (subscribers) or 30 years (admin). ($_GET start_date will
     * return a single day.)
     *
     * @param $args
     *
     * @return mixed
     */
    public static function parse_filter_form_dates($args) {
        // If there is no start date, use default date config.
        if ( ! isset($args['start_date'])) {
            $args['start_date'] = '20180801';
        };

        // Override default 0 num_days to allow multidate results.
        $args['num_days'] = null;
        if (isset($args['start_date']) && ! isset($args['end_date'])) {
            if ( ! M16_Events_Admin::$is_admin) {
                // If no end date and not admin, set end date to
                // today to prevent casual viewing of upcoming DMs.
                $args['end_date'] = 'midnight';
            }
        }

        return $args;
    }

    /**
     *
     */
    public static function get_filter_form() {
        ob_start();
        echo "<h3>Search Daily Messages</h3>";

        acf_form_head();

        $acf_settings = array(
            'form'               => true,
            'form_attributes'    => array(
                'class'        => 'dm_filter_form',
                'autocomplete' => 'off',
                //'method'       => 'get'
            ),
            'post_id'            => null, // Important! Keeps ACF from creating new posts
            'return'             => '',
            'submit_value'       => __('Search'),
            'html_before_fields' => '<input type="hidden" autocomplete="false"/>
                                     <input type="hidden" name="dm_filter_form" value="1"/>',
            'html_after_fields'  => '<button value="reset" class="button home-btn" aria-label="Reset all form fields.">Reset</button>'
        );

        acf_form(array_merge($acf_settings, array(
            'fields' => array(
                'dm_form_search_term',
                'dm_form_start_date',
                'dm_form_end_date',
                'dm_form_category',
                'dm_form_department',
                'dm_filter_audience',
            ))));

        return ob_get_clean();
    }

    /**
     * Return HTML representation of DM email
     *
     * @param $args
     *
     * @return string
     */
    public static function get_dm_html($args) {
        $args = wp_parse_args($args, self::$default_args);
        // We want a string for the GA tracking value
        $args['audience_string'] = $args['audience'];
        // We want an array for WP query
        $args['audience']  = $args['audience'] === 'all' ? self::$audience : (array) $args['audience'];
        self::$is_rest_api = true;
        $posts             = self::get_posts($args);
        $template_args     = self::get_template_args($posts, $args);

        $html = \Timber\Timber::fetch('daily-messages/daily-messages-email.twig', $template_args);

        // We die here because need to bypass WP API, which returns application/json
        header('Content-Type: text/html');
        die($html);
    }

    /**
     * Return JSON representation of DM email
     *
     * @param $args
     *
     * @return string
     */
    public static function get_dm_json($args) {
        $args = wp_parse_args($args, self::$default_args);
        // We want a string for the GA tracking value
        $args['audience_string'] = $args['audience'];
        // We want an array for WP query
        $args['audience']  = $args['audience'] === 'all' ? self::$audience : (array) $args['audience'];
        self::$is_rest_api = true;
        $posts             = self::get_posts($args);

        return $posts;
    }

    /**
     *
     * @return array
     */
    public static function get_page_args() {
        remove_filter('excerpt_more', 'twentysixteen_excerpt_more');
        add_filter('excerpt_more', function() {
            return '...';
        });
        self::$is_rest_api = false;

        // Get args from DM filter if present
        //$args = $_REQUEST;
        $args = self::parse_filter_form_args($_REQUEST);

        $args = wp_parse_args($args, self::$default_args);

        $dates            = M16_Events_Rest::parse_dates($args, self::$format);
        $args             = array_merge($args, $dates);
        $args['audience'] = self::parse_audience($args['audience'] === 'all' ? self::$audience : $args['audience']);
        $posts            = self::get_posts($args);
        $template_args    = self::get_template_args($posts, $args);

        $args['content'] = \Timber\Timber::fetch('daily-messages/daily-messages-email.twig', $template_args);

        return self::get_template_args($posts, $args);
    }

    /**
     * Convert get params into array if necessary.
     *
     * @param $audience
     *
     * @return array
     */
    public static function parse_audience($audience) {
        // Already an array? We're fine.
        if (is_array($audience)) return $audience;
        // Convert to array.
        if (stristr($audience, ',')) return explode(',', $audience);

        return (array) $audience;
    }

    /**
     * Get tribe_events posts which have DM run dates matching $args.
     * Return $args array with specific post IDs or false.
     *
     * @param $args
     *
     * @return array|bool
     */
    public static function get_posts($args) {
        // Modify WP_Query to allow wildcards in ACF repeater field in meta_query.
        add_filter('posts_where', array(__CLASS__, 'posts_where'), 50, 2);

        $category_posts = array();

        $event_categories = array();
        if ( ! empty($args[ self::EVENT_CATEGORY_TAX ])) {
            // Get specified category
            foreach ($args['event_category'] as $cat) {
                array_push($event_categories, get_term($cat, self::EVENT_CATEGORY_TAX));
            }
        } else {
            // Get all categories
            $event_categories = get_terms(self::EVENT_CATEGORY_TAX);
        }

        foreach ($event_categories as $category) {
            $category_posts = array_merge($category_posts, self::get_category_posts($args, $category));
        }

        // Remove our ACF wildcard modification.
        remove_filter('posts_where', array(__CLASS__, 'posts_where'), 50);

        return $category_posts;
    }

    /**
     * @param $args
     * @param $category
     * @param $category_posts
     *
     * @return mixed
     */
    public static function get_category_posts($args, $category) {
        $category_posts = array();
        $category_q     = self::do_category_query($args, $category);

        // If there are qualifying DMs, replace $args with the IDs of DM posts.
        if (count($category_q->posts)) {
            $post__in = array();
            foreach ($category_q->posts as $post) {
                array_push($post__in, $post->ID);
            }

            $posts_q = array(
                'is_dm_query'    => true,
                'post__in'       => $post__in,
                'posts_per_page' => $args['posts_per_page'],
                'orderby'        => 'menu_order',
                'order'          => 'ASC'
            );

            $posts = tribe_get_events($posts_q);

            $category_posts[ $category->name ] = self::build_category_posts($posts, $args);
        }

        return $category_posts;
    }

    public function dateTimeSeparator($option, $optionName, $default) {
        global $dm_post;
        if ($optionName === 'dateTimeSeparator' && is_a($dm_post, 'WP_Post')) {
            // Set the separator to the default @ instead of <br>
            $option = $default;
            // Replace <br> separator in $event_dates
            /*foreach ($dm_post->event_dates as &$date) {
                $date = str_replace($option, $default, $date);
            }*/
        }

        return $option;
    }

    /**
     * Get all events with DM run dates set by category (passed in args).
     * We'll use the returned IDs to get_tribe_events().
     *
     * @param $args
     * @param $term
     *
     * @return WP_Query
     */
    public static function do_category_query($args, $term) {
        // Keyword search from DM archive filter form.
        $s = $args['dm_form_search_term'] ? $args['dm_form_search_term'] : null;

        $tax_query = array(
            'relation' => 'AND',
            array(
                'taxonomy' => self::DM_AUDIENCE_TAX,
                'terms'    => $args['audience'],
                'operator' => 'IN',
                'field'    => 'slug'
            ),
            $term ? array(
                'taxonomy' => self::EVENT_CATEGORY_TAX,
                'terms'    => array($term->slug),
                'operator' => 'IN',
                'field'    => 'slug'
            ) : null
        );

        if ($args['tax_query']) {
            $tax_query = array_merge($tax_query, $args['tax_query']);
        }

        $q = new WP_Query(array(
            // Disentangle the Tribe events engine, which adds WHERE clauses we don't want.
            'tribe_remove_date_filters' => true,
            'is_dm_query'               => true,
            'post_parent'               => 0,
            'post_type'                 => TribeEvents::POSTTYPE,
            'posts_per_page'            => $args['posts_per_page'],
            'meta_query'                => array(
                'relation' => 'AND',
                // THIS MUST BE TRUE regardless of audience.
                // If this isn't checked, the DM will not be included.
                array(
                    'key'   => self::IS_DM,
                    'value' => '1'
                ),
                array(
                    'key'     => in_array('students', $args['audience']) ? 'acf_daily_message_run_dates_$' : 'acf_daily_message_run_dates_0',
                    'value'   => array(
                        date(self::$format, strtotime($args['start_date'])),
                        date(self::$format, strtotime($args['end_date']))
                    ),
                    'compare' => 'BETWEEN',
                    'type'    => 'DATE'
                ),
            ),
            'tax_query'                 => $tax_query,
            's'                         => $s
        ));

        return $q;
    }

    /**
     * @param $args
     *
     * @return array Event posts array
     */
    public static function build_category_posts($posts, $args) {
        if (count($posts)) {
            // Store global object so we can change its properties via filters if needed.
            global $dm_post;
            foreach ($posts as &$post) {
                $dm_post         = $post;
                $post->is_dm     = true;
                $post->debug     = self::$debug;
                $post->css       = self::$css;
                $post->arrow_up  = self::ARROW_UP;
                $post->hash      = self::$hash . '_post_' . $post->ID;
                $post->hash_link = '#' . self::$hash . '_post_' . $post->ID;
                foreach ($post->event_dates as &$date) {
                    $date = str_replace('<br>', ' @ ', $date);
                }

                if (self::$is_rest_api) {
                    // Set up GA tracking
                    $ga_content      = $post->thumb_id ? 'has-image' : 'no-image';
                    $ga_term         = current((array) $post->depts) ? current((array) $post->depts) : current((array) $post->organization);
                    $post->event_url .= '?utm_source=' . self::$ga_source . '&utm_medium=' . self::$ga_medium . '&utm_campaign=' . self::$ga_campaign . $args['audience_string'];
                    $post->event_url .= '&utm_term=' . $ga_term . '&utm_content=' . $ga_content;
                }

                if (tribe_is_recurring_event($post->ID)) {

                    $recurrence_args = array(
                        'post_parent'    => $post->ID,
                        'meta_key'       => '_EventStartDate',
                        'orderby'        => 'meta_key',
                        'order'          => 'ASC',
                        'posts_per_page' => -1,
                    );

                    $all_event_ids_in_recurrence_series = tribe_get_events($recurrence_args);

                    foreach ($all_event_ids_in_recurrence_series as $child) {
                        array_push($post->event_dates, tribe_get_start_date($child));
                    }
                }
            }
        }

        return $posts;
    }

    /**
     * @param $category_posts
     *
     * @return array
     */
    public static function get_template_args($category_posts, $args) {
        return array(
            'audience'       => count($args['audience']) > 1 ? join(',', $args['audience']) : current($args['audience']),
            'is_admin'       => M16_Events_Admin::$is_admin,
            'start_date'     => strtotime($args['start_date']),
            'end_date'       => strtotime($args['end_date']),
            'today'          => date(self::$format, strtotime($args['start_date'])),
            'yesterday'      => date(self::$format, strtotime($args['start_date'] . " -1 day")),
            'tomorrow'       => date(self::$format, strtotime($args['start_date'] . " +1 day")),
            'css'            => self::$css,
            'home_url'       => get_home_url(),
            'logo'           => self::DM_LOGO,
            'category_posts' => $category_posts,
            'hash'           => self::$hash,
            'content'        => $args['content'] // complete html post listing
        );
    }

    /**
     * @param $data
     */
    public static function write_file($data, $file) {
        // Create/truncate test file
        if (is_resource($handle = fopen($file, 'w'))) {
            ftruncate($handle, 0);
            fwrite($handle, $data);
            fclose($handle);
        }
    }

    /**
     * Replace showings_$ with repeater_slug_$
     *
     * @param $where
     *
     * @return mixed
     */
    public static function posts_where($where) {
        $where = str_replace("meta_key = 'acf_daily_message_run_dates_$", "meta_key LIKE 'acf_daily_message_run_dates_%", $where);

        return $where;
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

M16_Events_Daily_Messages::instance();
