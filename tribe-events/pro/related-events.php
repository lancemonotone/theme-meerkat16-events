<?php
/**
 * Related Events Template
 * The template for displaying related events on the single event page.
 *
 * You can recreate an ENTIRELY new related events view by doing a template override, and placing
 * a related-events.php file in a tribe-events/pro/ directory within your theme directory, which
 * will override the /views/pro/related-events.php.
 *
 * You can use any or all filters included in this file or create your own filters in
 * your functions.php. In order to modify or extend a single filter, please see our
 * readme on templates hooks and filters
 *
 * @package TribeEventsCalendarPro
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
    die( '-1' );
}

$posts = tribe_get_related_posts();

if ( is_array( $posts ) && ! empty( $posts ) ) : ?>

<h3 class="widgettitle"><?php printf( __( 'Related %s', 'tribe-events-calendar-pro' ), tribe_get_event_label_plural() ); ?></h3>

<ul class="tribe-related-events tribe-clearfix">
    <?php foreach ( $posts as $post ) : ?>
    <li>
        <?php // Thumbnail ?>
        <?php echo M16_Events_Event::tribe_event_featured_image($post->ID, 'wms_widget_thumb_size_default', 'tribe-related-events-thumbnail', true, true); ?>
        <div class="tribe-related-event-info">
            <h3 class="tribe-related-events-title"><a href="<?php echo tribe_get_event_link( $post ); ?>" class="tribe-event-url" rel="bookmark"><?php echo get_the_title( $post->ID ); ?></a></h3>
            <?php
                if ( $post->post_type == Tribe__Events__Main::POSTTYPE ) {
                    echo tribe_events_event_schedule_details( $post );
                }
            ?>
        </div>
    </li>
    <?php endforeach; ?>
</ul>
<?php
endif;
