<?php

class Wms_Tribe_Filterbar {
    private static $instance;

    protected function __construct() {
        self::add_hooks();
    }

    public function add_hooks() {
        add_filter('gettext', array(&$this, 'tribe_custom_theme_text'), 20, 3);
        add_filter('tribe-events-bar-views', array(&$this, 'remove_views_dropdown'), 9999, 1);
        add_filter('tribe-events-bar-filters', array(&$this, 'remove_from_bar'));
        add_action('tribe_events_filters_create_filters', array(&$this, 'WmsCats_taxfilter'));
        add_action('wp_enqueue_scripts', array(&$this, 'removeTribeFilterBarDefaultDisplay'), 50);
    }

    /**
     * Change default text
     *
     * @param $translation
     * @param $text
     * @param $domain
     *
     * @return string
     */
    function tribe_custom_theme_text($translation, $text, $domain) {
        // Put your custom text here in a key => value pair
        // Example: 'Text you want to change' => 'This is what it will be changed to'
        // The text you want to change is the key, and it is case-sensitive
        // The text you want to change it to is the value
        // You can freely add or remove key => values, but make sure to separate them with a comma
        // This example changes the label "Venue" to "Location", and "Related Events" to "Similar Events"
        $custom_text = array(
            'Show Filters'         => 'Filters',
            'Collapse Filters'     => 'Filters',
            'Please log in first.' => 'Please log in'
        );

        // If this text domain starts with "tribe-", "the-events-", or "event-" and we have replacement text
        if ((strpos($domain, 'tribe-') === 0 || strpos($domain, 'the-events-') === 0 || strpos($domain, 'event-') === 0) && array_key_exists($translation, $custom_text)) {
            $translation = $custom_text[ $translation ];
        }

        return $translation;
    }

    /**
     * Remove view dropdown from the filterbar. Weirdly, you have to leave
     * at least one item or it breaks the page.
     *
     * @param $views
     *
     * @return array
     */

    public function remove_views_dropdown($views) {
        return array_filter($views, function($item) {
            return $item['anchor'] === 'List';
        });
    }

    // class filterbar includes modifications to the filter bar

    public function remove_from_bar($filters) {
        // remove location search
        if (isset($filters['tribe-bar-geoloc'])) {
            unset($filters['tribe-bar-geoloc']);
        }
        if (isset($filters['tribe-bar-date'])) {
            $html = $filters['tribe-bar-date']['html'];
            $DOM  = new DOMDocument;
            $DOM->loadHTML($html);

            //get all H1
            $node = $DOM->getElementById('tribe-bar-date');
            $node->setAttribute('autocomplete', 'off');

            $filters['tribe-bar-date']['html'] = $DOM->saveHTML($node);

        }

        return $filters;
    }

    /**
     * Add the new filter to the filter bar.
     * Invokes TribeEventsFilter::__construct($name, $slug);
     */
    function WmsCats_taxfilter() {
        new Wms_Custom_Filters('Event Category', 'wmscat');
    }

    //move the location of bar to before the filter bar
    function removeTribeFilterBarDefaultDisplay() {
        $filterClass = TribeEventsFilterView::instance();
        remove_action('tribe_events_before_template', array($filterClass, 'displaySidebar'), 25);
        remove_action('tribe_events_bar_after_template', array($filterClass, 'displaySidebar'), 25);
        add_action('wms_filter_bar_inject', array($filterClass, 'displaySidebar'), 25);
    }

    /**
     * Returns the singleton instance of this class.
     *
     * @return Wms_Tribe_Filterbar The singleton instance.
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

Wms_Tribe_Filterbar::instance();
