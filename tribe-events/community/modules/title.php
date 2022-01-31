<?php
// Don't load directly
defined('WPINC') or die;

/**
 * Event Submission Form
 * The wrapper template for the event submission form.
 *
 * Override this template in your own theme by creating a file at
 * [your-theme]/tribe-events/community/modules/title.php
 *
 * @since    4.5
 * @version  4.5
 *
 */

$events_label_singular = tribe_get_event_label_singular();
?>
<div class="tribe-section events-community-post-title add-counter">
    <?php
    /**
     * Allow developers to hook and add content to the beginning of this section
     */
    do_action('tribe_events_community_section_before_title');
    ?>
    <div class="tribe-section-header">
        <?php tribe_community_events_field_label('post_title', __('Headline', 'tribe-events-community')); ?>
        <p class="description"><span class="counter">100</span> characters remaining.</p>
    </div>

    <div class="tribe-section-content">
        <?php //tribe_community_events_form_title();

        $title = get_the_title($event);
        if (empty($title) && ! empty($_POST['post_title'])) {
            $title = stripslashes($_POST['post_title']);
        }
        ?>
        <input id="post_title" maxlength="100" type="text" name="post_title" value="<?php esc_attr_e($title); ?>"/>
    </div>

    <?php
    /**
     * Allow developers to hook and add content to the end of this section
     */
    do_action('tribe_events_community_section_after_title');
    ?>
</div>