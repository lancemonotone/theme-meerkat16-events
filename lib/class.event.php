<?php

/**
 * Class M16_Events_Event
 *
 */
class M16_Events_Event {
    private static $instance;
    // Caches event id for async use
    public static $event_id;
    // Used if no event or venue image
    public static $placeholder_img = 'transparent_iron_w.png';

    protected function __construct() {
        add_action('pre_get_posts', array(&$this, 'rss_custom_parameters'), 52);
        add_action('pre_get_posts', array(&$this, 'pre_get_posts'), 20, 1);
        add_action('pre_get_posts', array(&$this, 'exclude_cats'), 20, 2);
        add_action('tribe_events_before_the_title', array(&$this, 'wms_event_before_title'));
        add_action('tribe_events_after_the_title', array(&$this, 'wms_event_after_title'));
        // Add trash post status to editor status dropdown
        add_action('admin_footer-post.php', array(__CLASS__, 'append_post_status_list'));
        //hide time for past events on list view
        add_filter('tribe_events_event_schedule_details_formatting', array(&$this, 'wms_prev_event'));
        add_filter('tribe_get_events_title', array(&$this, 'wms_custom_tax_title'));
        add_filter('tribe_the_notices', array(&$this, 'tribe_the_notices'), 25, 3);
        add_filter('excerpt_length', array(&$this, 'excerpt_length'), 25, 1);
        // drm2 Purpose is to allow formatting in excerpts. Disabled because it breaks tags.
        //add_filter('wp_trim_words', array(&$this, 'wp_trim_words'), 25, 4);
        add_filter('tribe_events_event_schedule_details_inner', array(&$this, 'tribe_events_event_schedule_details_inner'), 25, 2);
        add_filter('tribe_related_posts_args', array(&$this, 'suppress_related_posts'));
        add_filter('tribe_events_hide_from_upcoming_ids', array(&$this, 'show_private_events_if_logged_in'));
        add_filter('tribe_events_tribe_venue_create', array(&$this, 'venue_check'), 20, 3);
        //add_filter('tribe_community_events_form_errors', array(__CLASS__, 'event_update_output_message'), 20, 1);
        add_action('tribe_events_ical_single_event_links', array($this, 'suppress_event_links'), 10, 1);
    }

    public function init() {
    }

    public static function get_changed_status($event_id) {
        $event_status = get_post_status($event_id);
        if (isset($_REQUEST['post_status']) && $_REQUEST['post_status'] === 'trash') {
            $status = 'trash';
        } else if ($event_status === 'inherit') {
            $status = 'inherit';
        } else if ($event_status === 'pending') {
            $status = 'pending';
        } else if ($event_status === 'publish') {
            $status = 'publish';
        }

        return $status;
    }

    /**
     * Prevent users from exporting Announcements to Google and iCal
     *
     * @param $calendar_links
     *
     * @return string
     */
    public static function suppress_event_links($calendar_links) {
        global $post;

        if ($post->type === 'announcement') {
            $calendar_links = '';
        }

        return $calendar_links;
    }

    /**
     * Restrict RSS Feed to only the current day's events when 'today' param
     * is present. Used by the automated MailChimp newsletter.
     *
     * @param $query
     *
     * @return $query
     */
    public static function rss_custom_parameters(WP_Query $query) {
        if ($query->is_feed() && $query->tribe_is_event_query && isset($_REQUEST['today'])) {
            // Change number of posts retrieved on events feed
            $query->set('posts_per_rss', 100);
            // Add restriction to only show events within one week
            $query->set('end_date', date('Y-m-d H:i:s', mktime(23, 59, 59, date('n'), date('j'), date('Y'))));
        }

        return $query;
    }

    /**
     * @param $post
     * @param $tax
     *
     * @return array
     */
    public static function get_terms($post, $tax) {
        $post_terms = array();
        $terms      = wp_get_post_terms($post->ID, $tax);
        foreach ($terms as $term) {
            $post_terms[ $term->slug ] = $term->name;
        }

        return $post_terms;
    }

    /**
     * Natively, wp_trim_words() will strip HTML tags, which we want to keep,
     * so this is basically wp_trim_words() without tag-stripping.
     *
     * @param $text
     * @param $num_words
     * @param $more
     * @param $original_text
     *
     * @return string
     */
    function wp_trim_words($text, $num_words, $more, $original_text) {
        if (null === $more) {
            $more = __('&hellip;');
        }

        $text = $original_text;

        /*
         * translators: If your word count is based on single characters (e.g. East Asian characters),
         * enter 'characters_excluding_spaces' or 'characters_including_spaces'. Otherwise, enter 'words'.
         * Do not translate into your own language.
         */
        if (strpos(_x('words', 'Word count type. Do not translate!'), 'characters') === 0 && preg_match('/^utf\-?8$/i', get_option('blog_charset'))) {
            $text = trim(preg_replace("/[\n\r\t ]+/", ' ', $text), ' ');
            preg_match_all('/./u', $text, $words_array);
            $words_array = array_slice($words_array[0], 0, $num_words + 1);
            $sep         = '';
        } else {
            $words_array = preg_split("/[\n\r\t ]+/", $text, $num_words + 1, PREG_SPLIT_NO_EMPTY);
            $sep         = ' ';
        }

        if (count($words_array) > $num_words) {
            array_pop($words_array);
            $text = implode($sep, $words_array);
            $text = $text . $more;
        } else {
            $text = implode($sep, $words_array);
        }

        return $text;
    }

    function venue_check($check, $data, $post_status) {
        if ($data['Venue'] === "") {
            $check = false;
        }

        return $check;
    }

    /**
     * @param array $hide_upcoming_ids
     *
     * @return array
     */
    function show_private_events_if_logged_in($hide_upcoming_ids) {
        if (is_user_logged_in()) {
            unset($hide_upcoming_ids);
            $hide_upcoming_ids = array();
        }

        return $hide_upcoming_ids;
    }

    /**
     * Don't display related posts on single DM page.
     * Don't display DMs in related posts.
     *
     * @param $args
     *
     * @return array
     */
    function suppress_related_posts($args) {
        global $post;
        // If the post is a DM, return empty array to suppress related posts
        if (has_term('announcement', 'tribe_events_cat', $post->ID)) {
            return array();
        }
        // Otherwise prevent DMs from appearing in related posts
        $tax_query                      = array(
            'taxonomy' => TribeEvents::TAXONOMY,
            'terms'    => array('announcement'),
            'field'    => 'slug',
            'operator' => 'NOT IN',
        );
        $args['tax_query']['relation']  = 'AND';
        $args['tax_query']           [] = $tax_query;

        return $args;
    }

    /**
     * @param string $start_date
     * @param string $start_time
     * @param string $end_time
     * @param bool   $show_end_time
     *
     * @return string
     */
    public static function get_inner_datetime($start_date, $start_time = null, $end_time = null) {
        $date_with_year_format = tribe_get_date_format(true);
        $datetime_separator    = ' @ ';
        $time_range_separator  = tribe_get_option('timeRangeSeparator', ' - ');
        $show_end_time         = $end_time && $end_time !== $start_time;

        $start_date = date($date_with_year_format, strtotime($start_date));
        $start_time = $start_time ? $datetime_separator . $start_time : '';

        $inner = '<span class="tribe-event-date-start">';
        $inner .= $start_date . $start_time;
        $inner .= '</span>';
        if ($show_end_time) {
            $inner .= $time_range_separator;
            $inner .= '<span class="tribe-event-time">' . $end_time . '</span>';
        }

        return $inner;
    }

    /**
     * Add Rejected to post status dropdown.
     */
    function append_post_status_list() {
        global $post;
        $selected = '';
        $label    = '';
        if ($post->post_type == 'tribe_events') {
            if ($post->post_status == 'trash') {
                $selected = ' selected="selected"';
                $label    = '<span id="post-status-display">Rejected</span>';
            }
            echo <<< EOD
          <script>
          jQuery(document).ready(function($){
            const post_status_select = $("select#post_status");
               post_status_select.append('<option value="trash"{$selected}>Reject</option>');
               $(".misc-pub-section label").append('{$label}');
               /*post_status_select.on('change', ()=>{
                 if($(':selected', $(this)).val() === 'trash'){
                     x = 1;
                 }
               });*/
          });
          </script>
EOD;
        }
    }

    /**
     * Return event image with specifications.
     *
     * @param $post_id
     * @param $size
     * @param $class
     * @param $link
     * @param $wrap
     *
     * @return string
     */
    public static function tribe_event_featured_image($post_id, $size, $class, $link, $wrap, $img_id = null) {
        global $post;
        if (has_term('announcement', 'tribe_events_cat', $post_id)) {
            // announcements do not have images (July 2018)
            return '';
        }
        //$sizes = Meerkat16_Images::get_all_image_sizes();
        // Event image
        if ( ! $img_id) $img_id = get_field('acf_event_image', $post_id);
        // Venue image
        if ( ! $img_id) {
            if ($venue_id = tribe_get_venue_id($post_id)) {
                $img_id = get_post_thumbnail_id($venue_id);
                $venue  = tribe_get_venue($post_id);
                $alt    = "Image of " . $venue;
                $class  .= ' venue-img';
            }
        }
        if ( ! $img_id) {
            $img_id = Meerkat16_Images::get_image_id(self::$placeholder_img);
        }

        if ($img_id) {
            if (empty($alt)) {
                $alt = stripslashes($post->post_title);
            }
            $attr           = array(
                'alt' => $alt,
                //'class' => $class,
            );
            $featured_image = wp_get_attachment_image($img_id, $size, "", $attr);
        }
        /**
         * Controls whether the featured image should be wrapped in a link
         * or not.
         *
         * @param bool $link
         */
        if ( ! empty($featured_image) && apply_filters('tribe_event_featured_image_link', $link)) {
            $featured_image = '<a class="' . $class . '" href="' . esc_url(tribe_get_event_link($post_id)) . '">' . $featured_image . '</a>';
        }

        /**
         * Whether to wrap the featured image in our standard div (used to
         * assist in targeting featured images from stylesheets, etc).
         *
         * @param bool $wrapper
         */
        if ( ! empty($featured_image) && apply_filters('tribe_events_featured_image_wrap', $wrap)) {
            $featured_image = '<div class="' . $class . '">' . $featured_image . '</div>';
        }

        return $featured_image;
    }

    /**
     * Exclude terms we don't want to see on the home page or certain lists.
     * We do want to see them in the Dashboard and in Daily Messages, but not
     * in regular lists or filter bar requests, so some conditional gymnastics
     * are in order.
     *
     * @param WP_Query $query
     */
    function exclude_cats(WP_Query $query) {
        if ($query->query_vars['post_type'] !== 'tribe_events') {
            return;
        }

        if($query->is_singular) {
            return;
        }

        $excluded_cats = array('ongoing-exhibitions', 'announcement');

        // confirmation of event submission
        $not_confirmation = ! (isset($_REQUEST['confirm']));
        // 'Your Events' list page
        $not_your_events = ! isset($_REQUEST['eventDisplay']);
        // filter bar
        $is_ajax = tribe_is_ajax_view_request();
        // includes dashboard AND ajax filterbar searches
        $not_admin = ! is_admin();
        // daily messages
        $not_dm_query = empty($query->query_vars['is_dm_query']);
        // don't exclude explicitly queried terms
        $not_queried = empty($query->queried_object->slug) || ! in_array($query->queried_object->slug, $excluded_cats);

        if ($is_ajax || ($not_admin && $not_dm_query) && $not_queried && $not_confirmation && $not_your_events) {
            $tax_query['relation'] = 'AND';
            // Exclude Announcements
            $tax_query_01 = array(
                'taxonomy' => TribeEvents::TAXONOMY,
                'terms'    => array('announcement'),
                'field'    => 'slug',
                'operator' => 'NOT IN',
            );
            $tax_query [] = $tax_query_01;

            // Exclude Ongoing Exhibitions unless keyword search
            if ( ! isset($_REQUEST['tribe-bar-search'])) {
                $tax_query_02 = array(
                    'taxonomy' => 'event_category',
                    'terms'    => array('ongoing-exhibitions'),
                    'field'    => 'slug',
                    'operator' => 'NOT IN',
                );
                $tax_query [] = $tax_query_02;
            }

            // If there is an existing tax query, we don't want to kill it.
            if (is_array($query->tax_query->queries) && count($query->tax_query->queries)) {
                // Save existing queries.
                $original_tax_query = $query->tax_query->queries;
                // Clear the queries array.
                unset($query->tax_query->queries);
                // Push our new queries AND the original queries.
                $query->tax_query->queries['relation'] = 'AND';
                $query->tax_query->queries []          = $original_tax_query;
            }

            $query->tax_query->queries []   = $tax_query;
            $query->query_vars['tax_query'] = $query->tax_query->queries;
        }
    }

    function pre_get_posts(WP_Query $query) {
        /**
         * drm2 - This breaks ordering in category archives
         * because the query doesn't have a post_type.
         * It doesn't seem to be necessary.
         */
         //if ($query->query_vars['post_type'] !== 'tribe_events') {
         // return;
         //}

        // Do not modify queries for tribe filter query or navigation.
        // Make other list pages ordered by start date.
        if ( ! tribe_is_event_query() && is_archive() && ! is_admin() && is_main_query()) {
            $query->set('orderby', 'meta_value');
            $query->set('meta_key', '_EventStartDate');
            $query->set('order', 'ASC');
            $query->set('meta_query',
                array(
                    array(
                        'key'     => '_EventEndDate',
                        'value'   => date('Y-m-d H:i:s'),
                        'compare' => '>',
                        'type'    => 'DATETIME',
                    ),
                )
            );
        }
    }

    function tribe_events_event_schedule_details_inner($inner, $event_id) {
        if (has_term('announcement', TribeEvents::TAXONOMY, $event_id)) {
            $inner = '<span class="tribe-event-date-start"></span>';
        }

        return $inner;
    }

    function tribe_the_notices($html, $notices) {
        global $post;
        if (has_term('announcement', TribeEvents::TAXONOMY, $post)) {
            unset($notices['event-past']);
        }

        $html = ! empty($notices) ? '<div class="tribe-events-notices"><ul><li>' . implode('</li><li>', $notices) . '</li></ul></div>' : '';

        return $html;
    }

    /**
     * Set excerpt length. ACF excerpt is 240 chars ~ 120 words.
     *
     * @return int
     */
    function excerpt_length($words) {
        return 60;
    }

    /**
     * Add new markup to page titles
     *
     * @return void
     */
    function wms_event_before_title() {
        $output = '<div class="title-wrapper">';
        echo $output;
    }

    function wms_event_after_title() {
        $output = '</div>';
        echo $output;
    }

    /**
     *turn off time for past events in list views
     *
     * @return updated settings
     */
    function wms_prev_event($settings) {
        if (tribe_is_past() or tribe_is_past_event() && ! is_single()) {
            $settings['time'] = false;
        };

        return $settings;
    }

    /**
     *check and build titles for our custom taxonomies
     *
     * @return $title
     */
    function wms_custom_tax_title($title) {

        //check to see if using our taxonomy
        $classes = get_body_class();
        if (in_array('tax-event_category', $classes)) {
            $cur_term = get_term_by('slug', get_query_var('term'), get_query_var('taxonomy'));
            $title    = "Category: $cur_term->name";
        }

        return $title;
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

M16_Events_Event::instance();
