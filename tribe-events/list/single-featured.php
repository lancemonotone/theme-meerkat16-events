<?php
/**
 * List View Featured  Event
 * This file contains one event in the list view
 *
 * Override this template in your own theme by creating a file at [your-theme]/tribe-events/list/single-event.php
 *
 * @version 4.6.3
 *
 */
if ( ! defined('ABSPATH')) {
    die('-1');
}

$post  = get_post(get_the_ID());
$event = M16_Events_Post::populate_post($post);
$image = M16_Events_Event::tribe_event_featured_image($event->ID, 'medium', 'tribe-events-event-thumb', true, false);

\Timber\Timber::render('list/single.twig', array(
    'M16_Events_Event' => M16_Events_Event::instance(),
    'event'            => $event,
    'image'            => $image
));
