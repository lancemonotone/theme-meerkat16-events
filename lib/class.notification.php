<?php

/**
 * Class M16_Events_Notification
 * @uses BNFW_Notification
 * @uses BNFW
 */
class M16_Events_Notification {
    private static $instance;
    public static $handle;
    public static $first_run = true;
    private static $debug = false;

    // We only want to show tribe_events post status in BNFW dropdown.
    private static $custom_posttypes = array('tribe_events');
    public static $event_statuses = array(
        'event_pending' => 'Events Submission Pending',
        'event_publish' => 'Events Submission Accepted',
        'event_trash'   => 'Events Submission Rejected',
        'event_inherit' => 'Message Only',
    );

    function __construct() {
        if (class_exists('BNFW_Notification')) {
            // Replace Notification metaboxes with ours
            remove_action('add_meta_boxes_' . BNFW_Notification::POST_TYPE, array('BNFW_Notification', 'add_meta_boxes'));
            add_action('add_meta_boxes_' . BNFW_Notification::POST_TYPE, array(&$this, 'add_meta_boxes'), 15);
            // Remove Featured Image metabox
            add_action('do_meta_boxes', array(&$this, 'do_meta_boxes'), 150);
            // Add custom post types to Notification types dropdown
            add_filter('bnfw_notification_dropdown_posttypes', array(&$this, 'filter_custom_posttypes'), 10, 1);

            add_action('prepare_notification', array(&$this, 'prepare_notification'), 35, 2);
            //add_action('acf/save_post', array(__CLASS__, 'prepare_notification_update'), 40, 1);
            //add_action('wp_trash_post', array(__CLASS__, 'prepare_notification_update'), 100, 1);

            // Register notification shortcodes
            add_shortcode('wms_events', array(&$this, 'get_shortcode_field'));
            // Use Notification class to parse message shortcodes
            add_filter('bnfw_shortcodes', array(&$this, 'handle_bnfw_shortcodes'), 10, 4);
            // Handle quick editor status changes.
            add_action('shutdown', array(&$this, 'on_shutdown'));

            // Mostly for ajax debugging, this will prevent heartbeat from triggering a shutdown action.
            /*add_filter('heartbeat_received', function() {
                remove_action('shutdown', array(&$this, 'on_shutdown'));
            }, 10, 2);*/
            // Debugging
            if (self::$debug) {
                // Create/truncate test file
                $file = get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'dm_notifications.txt';
                if ( ! is_resource(self::$handle)) {
                    self::$handle = fopen($file, 'a+');
                }
                add_filter('wp_mail', array(&$this, 'write_log_file'), null, 1);
            }

        }
    }

    public function prepare_notification($event_id, $event) {
        // Prevent notification during preview phase.
        if (isset($_REQUEST['do_preview']) && $_REQUEST['do_preview'] === '1') {
            return;
        }
        // Prevent notifications from being sent for each instance of a recurring event.
        if ( ! self::$first_run) {
            return;
        }
        if (self::$debug && is_resource(self::$handle)) {
            ftruncate(self::$handle, 0);
        }
        self::$first_run = false;

        // If the event is recurring we only want to notify once, not every instance,
        // so process the first instance and subsequently exit.
        if (tribe_is_recurring_event($event_id) && ! $event->post_parent === 0) {
            return;
        }

        $request_status = M16_Events_Event::get_changed_status($event_id);

        // If status is 'inherit' or unchanged (i.e. updated by a moderator) and Send Message to Author is unchecked, don't send notification.
        if ($_REQUEST['original_post_status'] === $event->post_status || $event->post_status === 'inherit') {
            $send_msg_checkbox = get_field_object('acf_send_message_to_author')['key'];
            if (isset($_REQUEST['acf'][ $send_msg_checkbox ]) && $_REQUEST['acf'][ $send_msg_checkbox ] !== '1') {
                return;
            }
        }

        $status = 'event_' . $request_status;

        self::send_notification($status, wp_get_post_parent_id($event_id) === 0 ? $event_id : wp_get_post_parent_id($event_id));

    }

    /**
     * Get shortcode content for notification message.
     *
     * @param $atts
     * @param $content
     * @param $shortcode
     *
     * @return string
     */
    public function get_shortcode_field($atts, $content, $shortcode) {
        $atts     = shortcode_atts(array('field' => ''), $atts);
        $event_id = M16_Events_Event::$event_id;
        $field    = '';

        if (isset($atts['field'])) {
            switch ($atts['field']) {
                case 'acf_message_to_author':
                    if (get_field('acf_send_message_to_author', $event_id)) {
                        if ($field = get_field('acf_message_to_author', $event_id)) {
                            $field = "Moderator Message<br>" . $field;
                        }
                        update_field('acf_send_message_to_author', false, $event_id);
                    }
                    break;
                case 'post_date':
                    $id = wp_is_post_revision($event_id) ? wp_is_post_revision($event_id) : $event_id;
                    if ($date = get_post_meta($id, 'event_created', true)) {
                        $field = "<strong>Date of Submission</strong><br>" . bnfw_format_date($date);
                    }
                    break;
                case 'more':
                    if ($_REQUEST['post_status'] !== 'trash' && $field = get_permalink($event_id)) {
                        $field = "<strong>More</strong><br>" . $field;
                    }
                    break;
                case 'event_cats':
                    if ($field = $this->get_the_terms($event_id, 'event_category')) {
                        $field = "<strong>Categories</strong><br>" . $field;
                    }
                    break;
                case 'event_dm_audience':
                    if ($field = $this->get_the_terms($event_id, 'daily_message_audience')) {
                        $field = "<strong>Audience</strong><br>" . $field;
                    }
                    break;
                case 'event_department':
                    if ($field_id = get_field('acf_override_author_department', $event_id)) {
                        $field = get_term_by('id', $field_id, 'event_departments')->name;
                        $field = ", " . $field;
                    } else if ($field = $this->get_the_terms($event_id, 'event_departments')) {
                        $field = ", " . $field;
                    }
                    break;
                case 'run_dates':
                    $dates  = array();
                    $date_0 = get_field('acf_daily_message_run_dates_0', $event_id);
                    $date_1 = get_field('acf_daily_message_run_dates_1', $event_id);
                    if ($date_0) {
                        array_push($dates, date('l, F jS, Y', strtotime($date_0)));
                    }
                    if ($date_1) {
                        array_push($dates, date('l, F jS, Y', strtotime($date_1)));
                    }
                    if ( ! empty($dates)) {
                        $field = '<strong>Daily Message Run Date(s)</strong><br>' . join(', ', $dates);
                    }
                    break;
                default:
                    $field = get_field($atts['field'], $event_id);
                    break;
            }
        }

        return $field;
    }

    /**
     * Send notification based on type and ref id
     *
     * @access private
     *
     * @param string $type Notification type.
     * @param int    $ref_id Reference id.
     *
     * @since 1.0
     *
     */
    private function send_notification($type, $ref_id) {
        $notifications = BNFW::factory()->notifier->get_notifications($type);
        foreach ($notifications as $notification) {
            BNFW::factory()->engine->send_notification(BNFW::factory()->notifier->read_settings($notification->ID), $ref_id);
        }
    }

    /**
     * Get comma-separated list of terms attached to post.
     *
     * @param $event_id
     * @param $tax
     *
     * @return string
     */
    public function get_the_terms($event_id, $tax) {
        $terms = get_the_terms($event_id, $tax);
        if ($terms && ! is_wp_error($terms)) {

            $terms_arr = array();

            foreach ($terms as $term) {
                $terms_arr[] = $term->name;
            }

            return join(", ", $terms_arr);
        }

        return '';
    }

    function __destruct() {
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }
        if (is_resource(self::$handle)) {
            fwrite(self::$handle, 'end');
            fclose(self::$handle);
        }
    }

    public function write_log_file($message) {
        $event_id    = M16_Events_Event::$event_id;
        $date        = date('Ymd g:i:s a', time());
        $the_message = do_shortcode(str_replace('&#8221;', '"', $message['message']));

        $message_log = <<< EOL
To: {$message['to']}
Time: {$date}
ID: {$event_id}
Subject: {$message['subject']}
Message: {$the_message}

EOL;

        if (is_resource(self::$handle)) {
            fwrite(self::$handle, $message_log);
        }

        return $message;
    }

    /**
     * Always uncheck Send to Author checkbox to prevent accidental sends.
     * Redirect to Pending list after trashing an event.
     */
    public function on_shutdown() {
        if ( is_admin() && ! empty($_REQUEST['action']) && $_REQUEST['action'] === 'editpost') {
            $post = get_post($_REQUEST['post']);
            if ($post->post_type === TribeEvents::POSTTYPE) {
                update_field('acf_send_message_to_author', false, M16_Events_Event::$event_id);
                if ($_REQUEST['post_status'] === 'trash') {
                    wp_redirect(admin_url('edit.php?post_status=pending&post_type=tribe_events'));
                    exit();
                }
            }
        }
    }

    /**
     * Route status
     */
    public function handle_bnfw_shortcodes($message, $notification, $post_id) {
        if (array_key_exists($notification, self::$event_statuses)) {
            $message = do_shortcode($message);
        }

        $message = BNFW::factory()->engine->post_shortcodes($message, $post_id);
        $post    = get_post($post_id);
        $message = BNFW::factory()->engine->user_shortcodes($message, $post->post_author);

        return $message;
    }

    /**
     * We only want to show tribe_events post status in dropdown.
     *
     * @param $types
     *
     * @return array
     */
    public function filter_custom_posttypes($types) {
        return array_filter($types, function($type) {
            return in_array($type, self::$custom_posttypes);
        });
    }

    /**
     * Remove default settings form. Replace with our own,
     * which will contain our custom Tribe post status actions.
     */
    public function add_meta_boxes() {
        remove_meta_box('bnfw-post-notification', BNFW_Notification::POST_TYPE, 'side');

        add_meta_box(
            'bnfw-post-notification',                     // Unique ID
            esc_html__('Notification Settings', 'bnfw'), // Title
            array($this, 'render_settings_meta_box'),   // Callback function
            BNFW_Notification::POST_TYPE,                              // Admin page (or post type)
            'normal'                                      // Context
        );
    }

    public function do_meta_boxes() {
        remove_meta_box('postimagediv', TribeEvents::POSTTYPE, 'side');
    }

    /**
     * Render the settings meta box.
     *
     * @param WP_Post $post
     *
     * @since 1.0
     *
     */
    public function render_settings_meta_box($post) {
        wp_nonce_field(BNFW_Notification::POST_TYPE, BNFW_Notification::POST_TYPE . '_nonce');
        $setting = BNFW::factory()->notifier->read_settings($post->ID);
        ?>
        <table class="form-table">
            <tbody>
            <tr valign="top">
                <th scope="row">
                    <label for="notification"><?php esc_html_e('Notification For', 'bnfw'); ?></label>
                    <div class="bnfw-help-tip">
                        <p><?php esc_html_e('E.g. If you select "New Post Published" from the list on the right, this notification will be sent when a new post is published.', 'bnfw'); ?></p>
                    </div>
                </th>
                <td>
                    <select name="notification" id="notification" class="select2"
                            data-placeholder="Select the notification type" style="width:75%">

                        <?php
                        $types = apply_filters(
                            'bnfw_notification_dropdown_posttypes',
                            get_post_types(
                                array(
                                    'public'   => true,
                                    '_builtin' => false,
                                ),
                                'names'
                            ));

                        foreach ($types as $type) {
                            if ($type != BNFW_Notification::POST_TYPE) {
                                $post_obj = get_post_type_object($type);
                                $label    = $post_obj->labels->singular_name;
                                ?>
                                <optgroup
                                        label="<?php esc_attr($label); ?>">

                                    <?php if ($type === 'tribe_events') { ?>
                                        <?php foreach (self::$event_statuses as $id => $value) { ?>
                                            <option
                                                    value="<?php echo $id ?>" <?php selected($id, $setting['notification']); ?>><?php echo esc_html__(' ' . $value, 'bnfw'); ?></option>
                                        <?php } ?>
                                    <?php } ?>

                                    <?php do_action('bnfw_after_notification_options', $type, $label, $setting); ?>
                                </optgroup>
                                <?php do_action('bnfw_after_notification_options_optgroup', $type, $label, $setting); ?>

                                <?php
                            }
                        }
                        ?>
                        <?php do_action('bnfw_after_notification_types_optgroup', $setting); ?>

                        <optgroup label="<?php _e('Admin', 'bnfw'); ?>">
                            <option
                                    value="admin-user" <?php selected('admin-user', $setting['notification']); ?>><?php esc_html_e('New User Registration - For Admin', 'bnfw'); ?></option>
                            <option
                                    value="admin-password" <?php selected('admin-password', $setting['notification']); ?>><?php esc_html_e('User Lost Password - For Admin', 'bnfw'); ?></option>
                            <option
                                    value="admin-password-changed" <?php selected('admin-password-changed', $setting['notification']); ?>><?php esc_html_e('Password Changed - For Admin', 'bnfw'); ?></option>
                            <option
                                    value="admin-role" <?php selected('admin-role', $setting['notification']); ?>><?php esc_html_e('User Role Changed - For Admin', 'bnfw'); ?></option>
                            <option
                                    value="core-updated" <?php selected('core-updated', $setting['notification']); ?>><?php esc_html_e('WordPress Core Automatic Background Updates', 'bnfw'); ?></option>

                            <?php do_action('bnfw_after_default_notifications', $setting); ?>
                        </optgroup>
                        <?php do_action('bnfw_after_default_notifications_optgroup', $setting); ?>

                        <optgroup label="Transactional">
                            <option
                                    value="new-user" <?php selected('new-user', $setting['notification']); ?>><?php esc_html_e('New User Registration - For User', 'bnfw'); ?></option>
                            <option
                                    value="welcome-email" <?php selected('welcome-email', $setting['notification']); ?>><?php esc_html_e('New User - Post-registration Email', 'bnfw'); ?></option>
                            <option
                                    value="user-password" <?php selected('user-password', $setting['notification']); ?>><?php esc_html_e('User Lost Password - For User', 'bnfw'); ?></option>
                            <option
                                    value="password-changed" <?php selected('password-changed', $setting['notification']); ?>><?php esc_html_e('Password Changed - For User', 'bnfw'); ?></option>
                            <option
                                    value="email-changed" <?php selected('email-changed', $setting['notification']); ?>><?php esc_html_e('User Email Changed - For User', 'bnfw'); ?></option>
                            <option
                                    value="user-role" <?php selected('user-role', $setting['notification']); ?>><?php esc_html_e('User Role Changed - For User', 'bnfw'); ?></option>
                            <option
                                    value="reply-comment" <?php selected('reply-comment', $setting['notification']); ?>><?php esc_html_e('Comment Reply', 'bnfw'); ?></option>

                            <?php do_action('bnfw_after_transactional_notifications', $setting); ?>
                        </optgroup>
                        <?php do_action('bnfw_after_transactional_notifications_optgroup', $setting); ?>
                    </select>
                </td>
            </tr>

            <?php do_action('bnfw_after_notification_dropdown', $setting); ?>

            <tr valign="top" id="user-password-msg">
                <td>&nbsp;</td>
                <td>
                    <div>
                        <p style="margin-top: 0;"><?php esc_html_e("This notification doesn't support additional email fields due to a limitation in WordPress.", 'bnfw'); ?></p>
                    </div>
                </td>
            </tr>

            <tr valign="top" id="email-formatting">
                <th>
                    <?php esc_html_e('Email Formatting', 'bnfw'); ?>
                    <div class="bnfw-help-tip">
                        <p><?php esc_html_e('How do you want to format the sent email? HTML is recommended as it\'ll show images and links correctly.', 'bnfw'); ?></p>
                    </div>
                </th>
                <td>
                    <label style="margin-right: 20px;">
                        <input type="radio" name="email-formatting"
                                value="html" <?php checked('html', $setting['email-formatting']); ?>>
                        <?php esc_html_e('HTML Formatting', 'bnfw'); ?>
                    </label>

                    <label>
                        <input type="radio" name="email-formatting"
                                value="text" <?php checked('text', $setting['email-formatting']); ?>>
                        <?php esc_html_e('Plain Text', 'bnfw'); ?>
                    </label>
                </td>
            </tr>

            <?php do_action('bnfw_after_email_formatting', $setting); ?>

            <tr valign="top" id="toggle-fields">
                <th>
                    <?php esc_html_e('Additional Email Fields', 'bnfw'); ?>
                    <div class="bnfw-help-tip">
                        <p><?php esc_html_e('This should be fairly self explanatory but if you\'re unsure, tick this checkbox and have a look at the available options. You can always untick it again should you decide you don\'t need to use it.', 'bnfw'); ?></p>
                    </div>
                </th>
                <td>
                    <input type="checkbox" id="show-fields" name="show-fields"
                            value="true" <?php checked($setting['show-fields'], 'true', true); ?>>
                    <label for="show-fields"><?php esc_html_e('Set "From" Name & Email, Reply To, CC, BCC', 'bnfw'); ?></label>
                </td>
            </tr>


            <tr valign="top" id="email">
                <th scope="row">
                    <?php esc_html_e('From Name and Email', 'bnfw'); ?>
                    <div class="bnfw-help-tip">
                        <p><?php esc_html_e('If you want to send the email from your site name and email address instead of the default "WordPress" from "wordpress@domain.com", this is where you can do it.', 'bnfw'); ?></p>
                    </div>
                </th>
                <td>
                    <input type="text" name="from-name" value="<?php echo esc_attr($setting['from-name']); ?>"
                            placeholder="Site Name" style="width: 37.35%">
                    <input type="email" name="from-email" value="<?php echo esc_attr($setting['from-email']); ?>"
                            placeholder="Site Email" style="width: 37.3%">
                </td>
            </tr>


            <tr valign="top" id="reply">
                <th scope="row">
                    <?php esc_html_e('Reply To', 'bnfw'); ?>
                    <div class="bnfw-help-tip">
                        <p><?php esc_html_e('If you want any replies to your email notification to go to another person, fill in this box with their name and email address.', 'bnfw'); ?></p>
                    </div>
                </th>
                <td>
                    <input type="text" name="reply-name" value="<?php echo esc_attr($setting['reply-name']); ?>"
                            placeholder="Name" style="width: 37.35%">
                    <input type="email" name="reply-email" value="<?php echo esc_attr($setting['reply-email']); ?>"
                            placeholder="Email" style="width: 37.3%">
                </td>
            </tr>

            <tr valign="top" id="cc">
                <th scope="row">
                    <?php esc_html_e('CC', 'bnfw'); ?>
                    <div class="bnfw-help-tip">
                        <p><?php esc_html_e('Publicly copy in any other users or user roles to this email.', 'bnfw'); ?></p>
                    </div>
                </th>

                <td>
                    <select multiple name="cc[]" class="<?php echo sanitize_html_class(bnfw_get_user_select_class()); ?>"
                            data-placeholder="<?php echo apply_filters('bnfw_email_dropdown_placeholder', 'Select User Roles / Users'); ?>" style="width:75%">
                        <?php bnfw_render_users_dropdown($setting['cc']); ?>
                    </select>
                </td>
            </tr>

            <tr valign="top" id="bcc">
                <th scope="row">
                    <?php esc_html_e('BCC', 'bnfw'); ?>
                    <div class="bnfw-help-tip">
                        <p><?php esc_html_e('Privately copy in any other users or user roles to this email.', 'bnfw'); ?></p>
                    </div>
                </th>

                <td>
                    <select multiple name="bcc[]" class="<?php echo sanitize_html_class(bnfw_get_user_select_class()); ?>"
                            data-placeholder="<?php echo apply_filters('bnfw_email_dropdown_placeholder', 'Select User Roles / Users'); ?>" style="width:75%">
                        <?php bnfw_render_users_dropdown($setting['bcc']); ?>
                    </select>
                </td>
            </tr>

            <?php do_action('bnfw_after_additional_email_fields', $setting); ?>

            <tr valign="top" id="post-author">
                <th>
                    <?php esc_html_e('Send to Author', 'bnfw'); ?>
                    <div class="bnfw-help-tip">
                        <p><?php esc_html_e('E.g. If you want a new comment notification to go to the author of the post that was commented on, tick this box. Doing so will hide the "Send To" box below.', 'bnfw'); ?></p>
                    </div>
                </th>

                <td>
                    <label>
                        <input type="checkbox" id="only-post-author" name="only-post-author"
                                value="true" <?php checked('true', $setting['only-post-author']); ?>>
                        <?php esc_html_e('Send this notification to the Author only', 'bnfw'); ?>
                    </label>
                </td>
            </tr>

            <?php do_action('bnfw_after_only_post_author', $setting); ?>

            <tr valign="top" id="current-user">
                <th>
                    &nbsp;
                    <div class="bnfw-help-tip">
                        <p><?php esc_html_e('E.g. If you\'re an editor and regularly update your posts, you might not want to be emailed about this all the time. Ticking this box will prevent you from receiving emails about your own changes.', 'bnfw'); ?></p>
                    </div>
                </th>
                <td>
                    <label>
                        <input type="checkbox" name="disable-current-user"
                                value="true" <?php checked('true', $setting['disable-current-user']); ?>>
                        <?php esc_html_e('Do not send this Notification to the User that triggered it', 'bnfw'); ?>
                    </label>
                </td>
            </tr>

            <?php do_action('bnfw_after_disable_current_user', $setting); ?>

            <tr valign="top" id="users">
                <th scope="row">
                    <?php esc_html_e('Send To', 'bnfw'); ?>
                    <div class="bnfw-help-tip">
                        <p><?php esc_html_e('Choose the users and/or user roles to send this email notification to.', 'bnfw'); ?></p>
                    </div>
                </th>
                <td>
                    <select multiple id="users-select" name="users[]"
                            class="<?php echo sanitize_html_class(bnfw_get_user_select_class()); ?>"
                            data-placeholder="<?php echo apply_filters('bnfw_email_dropdown_placeholder', 'Select User Roles / Users'); ?>" style="width:75%">
                        <?php bnfw_render_users_dropdown($setting['users']); ?>
                    </select>
                </td>
            </tr>

            <?php
            $display = 'none';

            if (self::should_show_users_count_msg($setting)) {
                $display = 'table-row';
            }
            ?>
            <tr valign="top" id="users-count-msg" style="display: <?php echo esc_attr($display); ?>">
                <th scope="row">&nbsp;</th>
                <td>
                    <div>
                        <p>
                            <?php _e('You have chosen to send this notification to over 200 users. Please check the email sending rate limit at your host before sending.', 'bnfw'); ?>
                        </p>
                    </div>
                </td>
            </tr>

            <?php do_action('bnfw_after_send_to', $setting); ?>

            <tr valign="top" id="subject-wrapper">
                <th scope="row">
                    <?php esc_html_e('Subject', 'bnfw'); ?>
                    <div class="bnfw-help-tip">
                        <p><?php esc_html_e('Notification subject. You can use ', 'bnfw'); ?>
                            <a href="https://betternotificationsforwp.com/documentation/notifications/shortcodes/" target="_blank">shortcodes</a><?php esc_html_e(' here.', 'bnfw'); ?>
                        </p>
                    </div>
                </th>
                <td>
                    <input type="text" name="subject" id="subject" value="<?php echo esc_attr($setting['subject']); ?>"
                            style="width:75%;">
                </td>
            </tr>

            <?php do_action('bnfw_after_user_dropdown', $setting); ?>

            <?php do_action('bnfw_before_message_body', $setting); ?>
            <tr valign="top">
                <th scope="row">
                    <?php esc_html_e('Message Body', 'bnfw'); ?>
                    <div class="bnfw-help-tip">
                        <p><?php esc_html_e('Notification message. You can use ', 'bnfw'); ?>
                            <a href="https://betternotificationsforwp.com/documentation/notifications/shortcodes/" target="_blank">shortcodes</a><?php esc_html_e(' here.', 'bnfw'); ?>
                        </p>
                    </div>

                    <div class="wp-ui-text-highlight">
                        <p>
                            <br>
                            <br>
                            <br>
                            <br>
                            <?php esc_html_e('Need some more help?', 'bnfw'); ?>
                        </p>
                        <?php
                        $doc_url = 'https://betternotificationsforwp.com/documentation/';

                        if (bnfw_is_tracking_allowed()) {
                            $doc_url .= "?utm_source=WP%20Admin%20Notification%20Editor%20-%20'Documentation'&amp;utm_medium=referral";
                        }
                        ?>
                        <p>
                            <a href="#" class="button-secondary" id="insert-default-msg"><?php esc_html_e('Insert Default Content', 'bnfw'); ?></a>
                        </p>
                        <p>
                            <a href="<?php echo $doc_url; ?>"
                                    target="_blank" class="button-secondary"><?php esc_html_e('Read Documentation', 'bnfw'); ?></a>
                        </p>
                        <p>
                            <a href="" target="_blank" id="shortcode-help"
                                    class="button-secondary"><?php esc_html_e('Find Shortcodes', 'bnfw'); ?></a>
                        </p>
                    </div>
                </th>
                <td>
                    <?php wp_editor($setting['message'], 'notification_message', array('media_buttons' => true)); ?>
                    <p> &nbsp; </p>
                    <div id="disable-autop">
                        <label>
                            <input type="checkbox" name="disable-autop"
                                    value="true" <?php checked('true', $setting['disable-autop']); ?>>
                            <?php esc_html_e('Stop additional paragraph and line break HTML from being inserted into my notifications', 'bnfw'); ?>
                        </label>
                    </div>
                </td>
            </tr>

            </tbody>
        </table>
        <?php
    }

    /**
     * Should the users count message be shown?
     *
     * @param array $setting Notification Setting.
     *
     * @return bool True if message should be shown.
     * @since 1.7
     *
     */
    protected function should_show_users_count_msg($setting) {
        $users = $setting['users'];

        if (count($users) > 200) {
            return true;
        }

        $emails = BNFW::factory()->engine->get_emails_from_users($users);

        if (count($emails) > 200) {
            return true;
        }

        return false;
    }

    /**
     * Returns the singleton instance of this class.
     *
     * @return M16_Events_Event The singleton instance.
     */
    public static function instance() {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Private clone method to prevent cloning of the instance of the
     * singleton instance.
     *
     * @return void
     */
    private function __clone() {
    }

    /**
     * Private unserialize method to prevent unserializing of the singleton
     * instance.
     *
     * @return void
     */
    private function __wakeup() {
    }

}

M16_Events_Notification::instance();