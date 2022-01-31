<?php
/**
 * Default Events Template
 * This file is the basic wrapper template for all the views if 'Default Events Template'
 * is selected in Events -> Settings -> Template -> Events Template.
 *
 * Override this template in your own theme by creating a file at [your-theme]/tribe-events/default-template.php
 *
 * @package TribeEventsCalendar
 *
 */

$posttype = get_query_var('post_type');
$classes  = get_body_class();
//if a tribe post and using filter view---only the home views use filter view
$view = $posttype == ('tribe_events' && in_array('tribe-events-filter-view', $classes)) && ! in_array('error404', $classes) ? 'search' : 'simple';

$user_links = Meerkat16_Events::get_user_links();
///add a message area above the user links
$widgets = Timber::get_widgets('sidebar-message') . $user_links;
Timberizer::render_template(array(
    'template'     => 'events-default',
    'view'         => $view,
    'hide_sidebar'  => is_home() ? false : true,
    'sidebar_widgets_extra' => array(
        'widgets' => $widgets,
        'id'      => 'tertiary',
        'class'   => 'sidebar widget-area cf user-links',
    )
));
