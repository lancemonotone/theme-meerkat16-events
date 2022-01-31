<?php
/**
 * Event Submission Form
 * The wrapper template for the event submission form.
 *
 * Override this template in your own theme by creating a file at
 * [your-theme]/tribe-events/community/edit-event.php
 *
 * The data-showif attribute matches the event type checkbox, and
 * controls the display of the field. Possible values include:
 * 'event','announcement','music','62_center','grad-art'
 *
 * @since    3.1
 * @version  4.5
 *
 * @var int|string $tribe_event_id
 */

if ( ! defined('ABSPATH')) {
    die('-1');
}

// Sets a hidden input true/false. If true, after form submission,
// we'll display a preview of the DM and single event, and provide an opportunity for
// the event author to edit or confirm the event. During the preview, the event's status
// will be set to 'draft'. Upon confirmation, the status will be set to 'pending' for
// moderator review.
$do_preview = true;

if ( ! isset($tribe_event_id)) {
    $tribe_event_id = null;
}

acf_form_head();

$acf_settings = array(
    'form'    => false,
    'return'  => '',
    'post_id' => $tribe_event_id
);

// To determine whether to show admin-only fields
global $userdata;
$caps = $userdata->caps;

// This is a joke. Refactor!
$instructions_dm = do_shortcode('[reuse_post id="2344"]');
$instructions_dm = wpautop($instructions_dm);
$instructions_dm = do_shortcode('[expando title="Daily Message Instructions"]' . $instructions_dm . '[/expando]');

$feedback = do_shortcode('[reuse_post id="3423"]');
$feedback = wpautop($feedback);
$feedback = do_shortcode('[expando title="Got Feedback?"]' . $feedback . '[/expando]');

$instructions_type      = do_shortcode('[reuse_post id="15746"]');
$instructions_type      = wpautop($instructions_type);
$instructions_type      = do_shortcode('[expando title="Instructions"]' . $instructions_type . '[/expando]');
$instructions_recurring = do_shortcode('[reuse_post id="20449"]');
$instructions_recurring = wpautop($instructions_recurring);
$instructions_recurring = do_shortcode('[expando title="Event Time &amp; Date Instructions"]' . $instructions_recurring . '[/expando]');
?>

<?php //tribe_get_template_part('community/modules/header-links'); ?>

<?php do_action('tribe_events_community_form_before_template', $tribe_event_id); ?>

<form method="post" enctype="multipart/form-data" data-user="<?php echo esc_js(json_encode($caps)) ?>" data-datepicker_format="<?php echo esc_attr(tribe_get_option('datepickerFormat', 0)); ?>">
    <input type="hidden" name="do_preview" value="<?php echo $do_preview ?>">
    <input type="hidden" name="post_ID" id="post_ID" value="<?php echo absint($tribe_event_id); ?>"/>
    <?php wp_nonce_field('ecp_event_submission'); ?>
    <div class="cf">
        <a style="display: inline-block; float: right;" href="mailto:drm2@williams.edu?subject=Events Feedback" target="_blank" rel="noopener">Feedback?</a>
    </div>
    <?php echo $instructions_type ?>
    <?php acf_form(array_merge($acf_settings, array(
        'fields' => array(
            'event_type',
        )))); ?>

    <div class="initially-hidden" data-showif="['event', 'music','62_center','grad-art']">
        <?php acf_form(array_merge($acf_settings, array(
            'fields' => array(
                'acf_private'
            )))); ?>
    </div>

    <?php if (in_array('administrator', array_keys($caps))) { ?>
        <div class="initially-hidden" data-showif="['event']">
            <?php tribe_get_template_part('community/modules/featured'); ?>
        </div>
    <?php } ?>

    <div class="initially-hidden" data-showif="['event', 'music','62_center','grad-art']">
        <?php acf_form(array_merge($acf_settings, array(
            'fields' => array(
                'acf_event_department',
            )))); ?>
    </div>

    <div class="initially-hidden" data-showif="['event','announcement','music','62_center','grad-art']">
        <?php acf_form(array_merge($acf_settings, array(
            'fields' => array(
                'acf_event_category'
            )))); ?>
    </div>

    <div class="initially-hidden acf-form" data-showif="['event']">
        <?php acf_form(array_merge($acf_settings, array(
            'fields' => array(
                'acf_event_group',
                //'acf_student_only'
            )))); ?>
    </div>

    <div class="initially-hidden acf-form acf-music" data-showif="['music']">
        <?php acf_form(array_merge($acf_settings, array('fields' => array(
            'acf_music_calendar_type',
            'acf_music_season_type',
            'acf_music_event_cat',
            'acf_music_seasons',
            'acf_program',
            'acf_press_release',
        )))); ?>
    </div>

    <div class="initially-hidden acf-form acf-ctd" data-showif="['62_center']">
        <?php acf_form(array_merge($acf_settings, array('fields' => array(
            'acf_ctd_season_type',
            'acf_ctd_event_cat',
            'acf_ctd_seasons',
            'acf_program',
            'acf_press_release',
            'acf_tickets_url'
        )))); ?>
    </div>

    <div class="acf-form acf-event">
        <div class="initially-hidden" data-showif="['event','announcement','music','62_center','grad-art']">
            <?php /*acf_form(array_merge($acf_settings, array('fields' => array(
                'acf_headline',
            ))));*/ ?>
            <?php tribe_get_template_part('community/modules/title'); ?>
        </div>

        <div class="initially-hidden" data-showif="['music','62_center']">
            <?php acf_form(array_merge($acf_settings, array('fields' => array(
                'acf_before_title',
                'acf_after_title',
            )))); ?>
        </div>

        <div class="initially-hidden" data-showif="['event','music','62_center','grad-art']">
            <?php echo $instructions_recurring ?>
            <?php tribe_get_template_part('community/modules/datepickers'); ?>
        </div>

        <div class="initially-hidden" data-showif="['event','announcement','music','62_center','grad-art']">
            <?php acf_form(array_merge($acf_settings, array('fields' => array(
                'acf_external_website',
            )))); ?>
        </div>

        <div class="initially-hidden" data-showif="['event','announcement','music','62_center','grad-art']">
            <?php acf_form(array_merge($acf_settings, array('fields' => array(
                'acf_daily_message_text',
                //'acf_description'
            )))); ?>

            <?php tribe_get_template_part('community/modules/description'); ?>
        </div>

        <div class="initially-hidden" data-showif="['event','music','62_center','grad-art']">
            <?php //tribe_get_template_part('community/modules/taxonomy', null, array('taxonomy' => 'post_tag')); ?>

            <?php tribe_get_template_part('community/modules/venue'); ?>

            <?php acf_form(array_merge($acf_settings, array('fields' => array(
                'acf_room',
            )))); ?>

            <?php /*if (in_array('administrator', array_keys($caps))) {
                tribe_get_template_part('community/modules/organizer');
            } */ ?>

            <?php //tribe_get_template_part('community/modules/website'); ?>

            <?php tribe_get_template_part('community/modules/custom'); ?>

            <?php tribe_get_template_part('community/modules/cost'); ?>

            <?php //if ( ! in_array('subscriber', array_keys($caps))) { ?>
            <?php acf_form(array_merge($acf_settings, array('fields' => array(
                'acf_event_image'
            )))); ?>
            <?php //} ?>

            <?php acf_form(array_merge($acf_settings, array('fields' => array(
                'acf_facebook_event_url',
                'acf_hashtag',
            )))); ?>

            <!--<div class="initially-hidden" data-showif="['event','music','62_center','grad-art']">
                <?php /*acf_form(array_merge($acf_settings, array('fields' => array(
                    'acf_post_tag'
                )))); */ ?>
            </div>-->
        </div>

        <div class="initially-hidden acf-form acf-dm" data-showif="['event','announcement','music','62_center','grad-art']">
            <?php echo $instructions_dm; ?>
            <?php acf_form(array_merge($acf_settings, array('fields' => array(
                'acf_is_dm',
                'acf_dm_audience',
                'acf_daily_message_run_dates_0',
                'acf_daily_message_run_dates_1',
            )))); ?>

            <div class="initially-hidden" data-showif="['announcement']">
                <?php acf_form(array_merge($acf_settings, array('fields' => array(
                    'acf_research_check',
                    'acf_research_faculty',
                    'acf_research_approved'
                )))); ?>
            </div>
        </div>

        <div class="initially-hidden acf-form acf-dm" data-showif="['event','announcement','music','62_center','grad-art']">

            <?php tribe_get_template_part('community/modules/spam-control'); ?>

            <?php tribe_get_template_part('community/modules/submit'); ?>

        </div>

</form>

<?php do_action('tribe_events_community_form_after_template', $tribe_event_id); ?>
