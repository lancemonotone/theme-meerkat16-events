<?php
/**
 * Single Venue Template
 * The template for a venue. By default it displays venue information and lists
 * events that occur at the specified venue.
 *
 * This view contains the filters required to create an effective single venue view.
 *
 * You can recreate an ENTIRELY new single venue view by doing a template override, and placing
 * a single-venue.php file in a tribe-events/pro/ directory within your theme directory, which
 * will override the /views/pro/single-venue.php.
 *
 * You can use any or all filters included in this file or create your own filters in
 * your functions.php. In order to modify or extend a single filter, please see our
 * readme on templates hooks and filters (TO-DO)
 *
 * @package TribeEventsCalendarPro
 *
 * @version 4.3.2
 */

if ( ! defined( 'ABSPATH' ) ) {
    die( '-1' );
}

$venue_id     = get_the_ID();
$full_address = tribe_get_full_address();
$telephone    = tribe_get_phone();
$website_link = tribe_get_venue_website_link();
global $wp_query;
?>
<?php while ( have_posts() ) : the_post(); ?>
<div class="tribe-events-venue">

     <!-- Venue Featured Image -->
        <?php echo tribe_event_featured_image( null, 'medium' ) ?>
    <div class="tribe-events-venue-meta tribe-clearfix">
        <!-- Venue Title -->
        <?php do_action( 'tribe_events_single_venue_before_title' ) ?>
        <h2 class="tribe-venue-name"><?php echo tribe_get_venue( $venue_id ); ?></h2>
        <?php do_action( 'tribe_events_single_venue_after_title' ) ?>

      

        <div class="tribe-events-event-meta">

            <?php if ( tribe_show_google_map_link() && tribe_address_exists() ) : ?>
                <!-- Google Map Link -->
                <?php echo tribe_get_map_link_html(); ?>
            <?php endif; ?>

            <!-- Venue Meta -->
            <?php do_action( 'tribe_events_single_venue_before_the_meta' ) ?>

            <div class="venue-address">

                <?php if ( $full_address ) : ?>
                <address class="tribe-events-address">
                    <span class="location">
                        <?php echo $full_address; ?>
                    </span>
                </address>
                <?php endif; ?>

                <?php if ( $telephone ): ?>
                    <span class="tel">
                        <?php echo $telephone; ?>
                    </span>
                <?php endif; ?>

                <?php if ( $website_link ): ?>
                    <span class="url">
                        <?php echo $website_link; ?>
                    </span>
                <?php endif; ?>

            </div><!-- .venue-address -->

            <?php do_action( 'tribe_events_single_venue_after_the_meta' ) ?>

        </div><!-- .tribe-events-event-meta -->

        <!-- Venue Description -->
        <?php if ( get_the_content() ) : ?>
        <div class="tribe-venue-description tribe-events-content">
            <?php the_content(); ?>
        </div>
        <?php endif; ?>

       

    </div><!-- .tribe-events-venue-meta -->

    <!-- Upcoming event list -->
    <?php do_action( 'tribe_events_single_venue_before_upcoming_events' ) ?>

    <?php
    // Use the `tribe_events_single_venue_posts_per_page` to filter the number of events to get here.
    echo tribe_venue_upcoming_events( $venue_id, $wp_query->query_vars ); ?>

    <?php do_action( 'tribe_events_single_venue_after_upcoming_events' ) ?>

</div><!-- .tribe-events-venue -->
<?php
endwhile;
