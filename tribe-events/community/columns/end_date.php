<?php
// Don't load directly
defined('WPINC') or die;

/**
 * My Events Column for End Date Display
 *
 * Override this template in your own theme by creating a file at
 * [your-theme]/tribe-events/community/columns/end_date.php
 *
 * @since 4.5
 * @version 4.5.5
 */


echo esc_html(date('D, M j, Y ',strtotime(tribe_get_end_date($event->ID, false))));
echo esc_html(date('@ g:i a', strtotime(tribe_get_end_time($event->ID))));