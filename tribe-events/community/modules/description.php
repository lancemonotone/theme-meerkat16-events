<?php
// Don't load directly
defined('WPINC') or die;

/**
 * Event Submission Form
 * The wrapper template for the event submission form.
 *
 * Override this template in your own theme by creating a file at
 * [your-theme]/tribe-events/community/modules/description.php
 *
 * @since    4.5
 * @version  4.5
 *
 */

$events_label_singular = tribe_get_event_label_singular();
?>

<div class="tribe-section events-community-post-content">
    <?php
    /**
     * Allow developers to hook and add content to the beginning of this section
     */
    do_action('tribe_events_community_section_before_description');
    ?>

    <div class="tribe-section-header">
        <?php tribe_community_events_field_label('post_content', sprintf(__('Description', 'tribe-events-community'))); ?>
        <p class="description"><?php _e('This will appear on the single-view landing page and can be any length. ')?></p>
    </div>

    <div class="tribe-section-content">
        <?php tribe_community_events_form_content(); ?>
    </div>

    <?php
    /**
     * Allow developers to hook and add content to the end of this section
     */
    do_action('tribe_events_community_section_after_description');
    ?>
</div>


