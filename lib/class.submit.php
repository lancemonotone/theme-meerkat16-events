<?php

class M16_Events_Submit {
    private static $instance;

    protected function __construct() {
        add_action('tribe_ce_before_event_list_page_template', array(&$this, 'restore_status'), 20, 0);
        add_action('tribe_community_events_list_columns', array(&$this, 'get_columns'), 20, 1);
    }

    /**
     * Modify which columns appear on the user's submitted events table
     *
     * @param $columns
     *
     * @return mixed
     */
    public static function get_columns($columns) {
        unset($columns['organizer']);
        unset($columns['venue']);
        unset($columns['end_date']);
        $columns['start_date'] = 'Event Start Date';
        $columns['dm_dates']   = 'Daily Message Run';

        return $columns;
    }

    /**
     * Buttons for toggling future or past events
     *
     * @since 4.5
     */
    public static function tribe_community_events_prev_next_nav() {

        add_filter('get_pagenum_link', array(tribe('community.main'), 'fix_pagenum_link'));

        $link = get_pagenum_link(1);
        $link = remove_query_arg('eventDisplay', $link);

        if (isset($_GET['eventDisplay']) && 'past' == $_GET['eventDisplay']) {
            $upcoming_button_class = '';
            $past_button_class     = 'current';
        } else {
            $upcoming_button_class = 'current';
            $past_button_class     = '';
        }
        ?>
        <a
                href="<?php echo esc_url($link . '?eventDisplay=list'); ?>"
                class="home-btn <?php echo esc_attr($upcoming_button_class); ?>"
        >
            <?php esc_html_e('Show Upcoming Events', 'tribe-events-community'); ?>
        </a>
        <a
                href="<?php echo esc_url($link . '?eventDisplay=past'); ?>"
                class="home-btn <?php echo esc_attr($past_button_class); ?>"
        >
            <?php esc_html_e('Show Past Events', 'tribe-events-community'); ?>
        </a>
        <?php
    }

    /**
     * Return status of events to correct status (based on
     * permissions level) after setting to 'draft' to prevent
     * moderator approval while user is previewing/editing.
     *
     * @see self::get_edit_form()
     */
    public static function restore_status() {
        if ($event_id = $_REQUEST['event_id']) {
            // Only do this if we have previously set the status to draft.
            // Tribe sets 'auto-draft' so if it's 'draft' it's ours.
            $status = get_post_status($event_id);
            if ($status === 'draft') {
                if (isset($_REQUEST['delete'])/* && $_REQUEST['status'] !== 'publish'*/) {
                    tribe_delete_event($event_id, true);
                } else {
                    // If any of the roles are in user's caps, set status to pending
                    $roles = array('subscriber', 'contributor', 'author');
                    $caps = array_keys(wp_get_current_user()->caps);
                    if (count(array_intersect($roles, $caps)) > 0) {
                        $status = 'pending';
                    } else {
                        $status = 'publish';
                    }
                    self::set_status($event_id, $status);
                }
            }
        }
    }

    public static function set_status($id, $status) {
        $args['post_status'] = $status;
        Tribe__Events__API::updateEvent($id, $args);
    }

    public static function load_preview(&$event) {
        if (intval($_REQUEST['do_preview'])) {
            $event->do_preview = true;
            $event->css        = M16_Events_Daily_Messages::$css;
            $event->preview    = self::get_preview($event);
            $event->edit_form  = self::get_edit_form($event);
        }
    }

    /**
     * Show previews of submissions.
     *
     * @param $event
     *
     * @return bool|string
     */
    public static function get_preview($event) {
        $tabs    = array();
        $tabs [] = array('Events Site' => \Timber\Timber::fetch('page-single-event.twig', array('event' => $event)));
        if ($event->dm_dates) {
            $tabs [] = array('Daily Messages' => \Timber\Timber::fetch('daily-messages/daily-message-body.twig', array('event' => $event)));
        }
        $out = '[tabs hide_anchor="true"]';
        foreach ($tabs as $tab) {
            foreach ($tab as $k => $v) {
                $out .= '[tab title="' . $k . '"]' . $v . '[/tab]';
            }
        }
        $out .= '[/tabs]';

        return do_shortcode($out);
    }

    public static function get_edit_form($event) {
        $id = $event->ID;
        // Set to auto-draft before review so Moderators won't approve it.
        // We'll set it back when user confirms.
        $status = get_post_status($id);
        self::set_status($id, 'draft');

        $edit_link     = $view_link = '';
        $hidden_id     = sprintf('<input type="hidden" name="event_id" value="%s">', $id);
        $hidden_status = sprintf('<input type="hidden" name="status" value="%s">', $status);
        $separator     = '<span class="sep"> &nbsp; </span>';

        if (current_user_can('edit_post', $id)) {
            $edit_link = sprintf('<a href="%s" class="home-btn edit-event">%s</a>',
                    esc_url(tribe_community_events_edit_event_link($id)),
                    __('Edit', 'tribe-events-community')
                ) . $separator;
        }

        if (current_user_can('edit_post', $id)) {
            $cancel_link = sprintf('<input type="submit" name="delete" value="%s" class="home-btn cancel-event">',
                    __('Delete', 'tribe-events-community')
                ) . $separator;
        }

        $view_link = sprintf('<input type="submit" name="confirm" value="%s" class="home-btn view-event">',
            __('Submit', 'tribe-events-community')
        );

        $fields = $edit_link . $cancel_link . $view_link;

        $form = sprintf('<form id="confirm-submission" class="tribe-community-notice" action="%s">%s%s%s</form>',
            esc_url(home_url() . '/events/community/list'),
            $hidden_id,
            $hidden_status,
            $fields
        );

        return $form;
    }

    /**
     * Returns the singleton instance of this class.
     *
     * @return M16_Events_Submit The singleton instance.
     */
    public
    static function instance() {
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
    private
    function __clone() {
    }

    /**
     * Private unserialize method to prevent unserializing of the singleton
     * instance.
     *
     * @return void
     */
    private
    function __wakeup() {
    }
}

M16_Events_Submit::instance();