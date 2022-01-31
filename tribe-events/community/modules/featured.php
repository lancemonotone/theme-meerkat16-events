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
global $post;
$events_label_singular = tribe_get_event_label_singular();
?>
<?php
/**
 * Allow developers to hook and add content to the beginning of this section
 */
do_action('tribe_events_community_section_before_featured');
?>
    <div class="acf-fields acf-form-fields">
        <div class="acf-field">
            <div class="acf-label">
                <label for="feature_event"><?php _e('Feature') ?></label>
                <p class="description">Admin only</p>
            </div>

            <div class="acf-input">
                <ul class="acf-checkbox-list acf-bl">
                    <li>
                        <label>
                            <input value="yes" type="checkbox" <?php checked(tribe('tec.featured_events')->is_featured($post->ID)); ?> name="feature_event">
                            <span><?php esc_html_e('Feature Event', 'the-events-calendar'); ?></span>
                        </label>

                        <span class="dashicons dashicons-editor-help tribe-sticky-tooltip" title="<?php esc_attr_e('Featured events are highlighted on the front end in views, archives, and widgets.', 'the-events-calendar'); ?>"></span>
                    </li>
                    <li>
                        <label>
                            <input value="yes" type="checkbox" <?php checked($post->menu_order === -1) ?> name="EventShowInCalendar">
                            <span><?php esc_html_e('Sticky in Month View', 'the-events-calendar'); ?></span>
                        </label>

                        <span class="dashicons dashicons-editor-help tribe-sticky-tooltip" title="<?php esc_attr_e('When events are sticky in month view, they\'ll display first in the list of events shown within a given day block.', 'the-events-calendar'); ?>"></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
<?php
/**
 * Allow developers to hook and add content to the end of this section
 */
do_action('tribe_events_community_section_after_featured');
?>