<?php
// Don't load directly
defined('WPINC') or die;

/**
 * Header links for edit forms.
 *
 * Override this template in your own theme by creating a file at
 * [your-theme]/tribe-events/community/modules/header-links.php
 *
 * @since  3.1
 * @version 4.5.7
 *
 */

$post_id      = get_the_ID();
$event        = current(tribe_get_events(array('p' => $_REQUEST['post_ID'])));
$instructions = <<<EOD
 <p>Preview your submission below. Make sure to check both the <em>Events Site</em> and <em>Daily Messages</em> previews, if applicable.
 <ul>
    <li>The Daily Messages preview is what users will see in the Daily Messages email (if applicable). 
    <li>The Events Site preview is the long-form entry users will see when they click on the headline in the email or in an events feed or widget.
 </ul>
 <ul>
    <li>Click Edit to return to the form if you need to make a change.</li>
    <li>Click Cancel to cancel your submission without saving.</li>
    <li>Click Submit to save your submission pending moderation.</li>
 </ul>
</p>
EOD;
$instructions = do_shortcode('[expando title="Instructions"]' . $instructions . '[/expando]');
?>

<header class="my-events-header">

    <?php if (tribe_is_event($_REQUEST['post_ID'])) { ?>
        <h2 class="my-events"><?php esc_html_e('Confirm Submission', 'tribe-events-community'); ?></h2>
        <?php echo $instructions ?>
        <div class="submit-nag">Don't forget to Submit!</div>
    <?php } elseif ($post_id && tribe_is_organizer($post_id)) { ?>
        <h2 class="my-events"><?php esc_html_e('Edit Organizer', 'tribe-events-community'); ?></h2>
    <?php } elseif ($post_id && tribe_is_venue($post_id)) { ?>
        <h2 class="my-events"><?php esc_html_e('Edit Venue', 'tribe-events-community'); ?></h2>
    <?php } else { ?>
        <h2 class="my-events"><?php esc_html_e('Add New Event/Announcement', 'tribe-events-community'); ?></h2>
    <?php } ?>

    <div>
        <?php if (is_user_logged_in()) : ?>
            <?php //echo do_shortcode('[home_btn color="#cf432b" text="Your Submitted Events/Announcements" link="' . home_url() . '/events/community/list" ]') ?>
            <?php //echo do_shortcode('[logout class="home-btn" redirect="' . home_url() . '/events/community/add"]') ?>
        <?php endif; ?>
    </div>

    <?php
    M16_Events_Submit::load_preview($event);
    if ($event->preview) {
        echo $event->edit_form;
    } else {
        echo tribe_community_events_get_messages();
    } ?>
</header>

<?php echo $event->preview; ?>
