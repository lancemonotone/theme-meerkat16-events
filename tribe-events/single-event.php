<?php
/**
 * Single Event Template
 * A single event. This displays the event title, description, meta, and
 * optionally, the Google map for the event.
 *
 * Override this template in your own theme by creating a file at [your-theme]/tribe-events/single-event.php
 *
 * @package TribeEventsCalendar
 * @version 4.6.3
 *
 */

if ( ! defined('ABSPATH')) {
    die('-1');
}

$post  = get_post(get_the_ID());
$event = M16_Events_Post::populate_post($post);
$event->events_label_singular = tribe_get_event_label_singular();
$event->events_label_plural = tribe_get_event_label_plural();

Timberizer::render_template(array(
    'template'              => 'single-event',
    'M16_Events_Event'      => M16_Events_Event::instance(),
    'event'                 => $event,
    'sidebar_message'       => Timber::get_widgets('sidebar-message')
));
