<?php
/*
 * Adjustments to ical feeds
 */


class Meerkat16_Events_Ical {
    private static $instance;

    protected function __construct() {
        add_filter('tribe_ical_properties', array(&$this, 'ical_feed_name'));
    }

    public function ical_feed_name($ical_header) {
		if (is_archive() && $category = single_cat_title('', false)) {
			$category = preg_replace("/&amp;/", "and", $category);
			$ical_header = preg_replace("/X-WR-CALNAME:[\w -]+/", "X-WR-CALNAME:$category", $ical_header);
		}
		return $ical_header;
    }

    /**
     * Returns the singleton instance of this class.
     *
     * @return Meerkat16_Events_CSS The singleton instance.
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

Meerkat16_Events_Ical::instance();

