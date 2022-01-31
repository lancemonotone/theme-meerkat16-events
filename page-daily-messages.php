<?php
/**
 * Default Events Template
 * This file is the basic wrapper template for all the views of Page Daily Messages
 */
$posttype = get_query_var('post_type');
$classes  = get_body_class();
//if a tribe post and using filter view---only the home views use filter view
$view = $posttype === ('tribe_events' && in_array('tribe-events-filter-view', $classes)) && ! in_array('error404', $classes) ? 'search' : 'simple';

if (is_on_campus() || is_user_logged_in()) {

    $args = M16_Events_Daily_Messages::get_page_args();

    // Place in sidebar
    $filter_form = M16_Events_Daily_Messages::get_filter_form();

    Timberizer::render_template(array_merge($args, array(
        'template'              => 'daily-messages',
        'view'                  => $view,
        'sidebar_widgets_extra' => array(
            'widgets' => $filter_form,
            'id'      => 'tertiary',
            'class'   => 'sidebar widget-area cf daily-messages-form'
        )
        //'hide_sidebar' => $view === 'simple'
    )));
} else {
    $args['title']   = 'Daily Message Archive';
    $args['content'] = do_shortcode('<p>Please [login text="log in"] to read Daily Messages.</p>');
    Timberizer::render_template(array_merge($args, array(
        'template' => 'login-form',
        'view'     => $view,
    )));
}
