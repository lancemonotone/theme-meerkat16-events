<?php
/*
 * Plugin Name: WP Crontrol
 * Plugin URI:  https://wordpress.org/plugins/wp-crontrol/
 * Description: WP Crontrol lets you view and control what's happening in the WP-Cron system.
 * Author:      John Blackbourn & contributors
 * Author URI:  https://github.com/johnbillion/wp-crontrol/graphs/contributors
 * Version:     1.5
 * Text Domain: wp-crontrol
 * Domain Path: /languages/
 * License:     GPL v2 or later
 */

/**
 * WP Crontrol lets you view and control what's happening in the WP-Cron system.
 *
 * LICENSE
 * This file is part of WP Crontrol.
 *
 * WP Crontrol is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @package    WP Crontrol
 * @author     Edward Dale <scompt@scompt.com> & John Blackbourn <john@johnblackbourn.com>
 * @copyright  Copyright 2008 Edward Dale, 2012-2017 John Blackbourn
 * @license    http://www.gnu.org/licenses/gpl.txt GPL 2.0
 * @link       https://wordpress.org/plugins/wp-crontrol/
 * @since      0.2
 */

defined('ABSPATH') or die();

class Crontrol {

    /**
     * Hook onto all of the actions and filters needed by the plugin.
     */
    protected function __construct() {

        $plugin_file = plugin_basename(__FILE__);

        add_action('init', array($this, 'action_init'));
        add_action('init', array($this, 'action_handle_posts'));
        add_action('admin_menu', array($this, 'action_admin_menu'));
        add_filter("plugin_action_links_{$plugin_file}", array($this, 'plugin_action_links'), 10, 4);

        register_activation_hook(__FILE__, array($this, 'action_activate'));

        add_filter('cron_schedules', array($this, 'filter_cron_schedules'));
        add_action('crontrol_cron_job', array($this, 'action_php_cron_event'));
    }

    /**
     * Evaluates the provided code using eval.
     */
    public function action_php_cron_event($code) {
        eval($code);
    }

    /**
     * Run using the 'init' action.
     */
    public function action_init() {
        load_plugin_textdomain('wp-crontrol', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    /**
     * Handles any POSTs made by the plugin. Run using the 'init' action.
     */
    public function action_handle_posts() {
        if (isset($_POST['new_cron'])) {
            if ( ! current_user_can('manage_options')) {
                wp_die(esc_html__('You are not allowed to add new cron events.', 'wp-crontrol'));
            }
            check_admin_referer('new-cron');
            extract(wp_unslash($_POST), EXTR_PREFIX_ALL, 'in');
            $in_args  = json_decode($in_args, true);
            $next_run = $in_next_run_date . ' ' . $in_next_run_time;
            $this->add_cron($next_run, $in_schedule, $in_hookname, $in_args);
            $redirect = array(
                'page'             => 'crontrol_admin_manage_page',
                'crontrol_message' => '5',
                'crontrol_name'    => urlencode($in_hookname),
            );
            wp_redirect(add_query_arg($redirect, admin_url('tools.php')));
            exit;

        } else if (isset($_POST['new_php_cron'])) {
            if ( ! current_user_can('edit_files')) {
                wp_die(esc_html__('You are not allowed to add new PHP cron events.', 'wp-crontrol'));
            }
            check_admin_referer('new-cron');
            extract(wp_unslash($_POST), EXTR_PREFIX_ALL, 'in');
            $args     = array(
                'code' => $in_hookcode,
                'name' => $in_eventname,
            );
            $next_run = $in_next_run_date . ' ' . $in_next_run_time;
            $this->add_cron($next_run, $in_schedule, 'crontrol_cron_job', $args);
            $hookname = ( ! empty($in_eventname)) ? $in_eventname : __('PHP Cron', 'wp-crontrol');
            $redirect = array(
                'page'             => 'crontrol_admin_manage_page',
                'crontrol_message' => '5',
                'crontrol_name'    => urlencode($hookname),
            );
            wp_redirect(add_query_arg($redirect, admin_url('tools.php')));
            exit;

        } else if (isset($_POST['edit_cron'])) {
            if ( ! current_user_can('manage_options')) {
                wp_die(esc_html__('You are not allowed to edit cron events.', 'wp-crontrol'));
            }

            extract(wp_unslash($_POST), EXTR_PREFIX_ALL, 'in');
            check_admin_referer("edit-cron_{$in_original_hookname}_{$in_original_sig}_{$in_original_next_run}");
            $in_args  = json_decode($in_args, true);
            $i        = $this->delete_cron($in_original_hookname, $in_original_sig, $in_original_next_run);
            $next_run = $in_next_run_date . ' ' . $in_next_run_time;
            $i        = $this->add_cron($next_run, $in_schedule, $in_hookname, $in_args);
            $redirect = array(
                'page'             => 'crontrol_admin_manage_page',
                'crontrol_message' => '4',
                'crontrol_name'    => urlencode($in_hookname),
            );
            wp_redirect(add_query_arg($redirect, admin_url('tools.php')));
            exit;

        } else if (isset($_POST['edit_php_cron'])) {
            if ( ! current_user_can('edit_files')) {
                wp_die(esc_html__('You are not allowed to edit cron events.', 'wp-crontrol'));
            }

            extract(wp_unslash($_POST), EXTR_PREFIX_ALL, 'in');
            check_admin_referer("edit-cron_{$in_original_hookname}_{$in_original_sig}_{$in_original_next_run}");
            $args['code'] = $in_hookcode;
            $args['name'] = $in_eventname;
            $args         = array(
                'code' => $in_hookcode,
                'name' => $in_eventname,
            );
            $i            = $this->delete_cron($in_original_hookname, $in_original_sig, $in_original_next_run);
            $next_run     = $in_next_run_date . ' ' . $in_next_run_time;
            $i            = $this->add_cron($next_run, $in_schedule, 'crontrol_cron_job', $args);
            $hookname     = ( ! empty($in_eventname)) ? $in_eventname : __('PHP Cron', 'wp-crontrol');
            $redirect     = array(
                'page'             => 'crontrol_admin_manage_page',
                'crontrol_message' => '4',
                'crontrol_name'    => urlencode($hookname),
            );
            wp_redirect(add_query_arg($redirect, admin_url('tools.php')));
            exit;

        } else if (isset($_POST['new_schedule'])) {
            if ( ! current_user_can('manage_options')) {
                wp_die(esc_html__('You are not allowed to add new cron schedules.', 'wp-crontrol'));
            }
            check_admin_referer('new-sched');
            $name     = wp_unslash($_POST['internal_name']);
            $interval = wp_unslash($_POST['interval']);
            $display  = wp_unslash($_POST['display_name']);

            // The user entered something that wasn't a number.
            // Try to convert it with strtotime
            if ( ! is_numeric($interval)) {
                $now    = time();
                $future = strtotime($interval, $now);
                if (false === $future || -1 == $future || $now > $future) {
                    $redirect = array(
                        'page'             => 'crontrol_admin_options_page',
                        'crontrol_message' => '7',
                        'crontrol_name'    => urlencode($interval),
                    );
                    wp_redirect(add_query_arg($redirect, admin_url('options-general.php')));
                    exit;
                }
                $interval = $future - $now;
            } else if ($interval <= 0) {
                $redirect = array(
                    'page'             => 'crontrol_admin_options_page',
                    'crontrol_message' => '7',
                    'crontrol_name'    => urlencode($interval),
                );
                wp_redirect(add_query_arg($redirect, admin_url('options-general.php')));
                exit;
            }

            $this->add_schedule($name, $interval, $display);
            $redirect = array(
                'page'             => 'crontrol_admin_options_page',
                'crontrol_message' => '3',
                'crontrol_name'    => urlencode($name),
            );
            wp_redirect(add_query_arg($redirect, admin_url('options-general.php')));
            exit;

        } else if (isset($_GET['action']) && 'delete-sched' == $_GET['action']) {
            if ( ! current_user_can('manage_options')) {
                wp_die(esc_html__('You are not allowed to delete cron schedules.', 'wp-crontrol'));
            }
            $id = wp_unslash($_GET['id']);
            check_admin_referer("delete-sched_{$id}");
            $this->delete_schedule($id);
            $redirect = array(
                'page'             => 'crontrol_admin_options_page',
                'crontrol_message' => '2',
                'crontrol_name'    => urlencode($id),
            );
            wp_redirect(add_query_arg($redirect, admin_url('options-general.php')));
            exit;

        } else if (isset($_GET['action']) && 'delete-cron' == $_GET['action']) {
            if ( ! current_user_can('manage_options')) {
                wp_die(esc_html__('You are not allowed to delete cron events.', 'wp-crontrol'));
            }
            $id       = wp_unslash($_GET['id']);
            $sig      = wp_unslash($_GET['sig']);
            $next_run = $_GET['next_run'];
            check_admin_referer("delete-cron_{$id}_{$sig}_{$next_run}");
            if ($this->delete_cron($id, $sig, $next_run)) {
                $redirect = array(
                    'page'             => 'crontrol_admin_manage_page',
                    'crontrol_message' => '6',
                    'crontrol_name'    => urlencode($id),
                );
                wp_redirect(add_query_arg($redirect, admin_url('tools.php')));
                exit;
            } else {
                $redirect = array(
                    'page'             => 'crontrol_admin_manage_page',
                    'crontrol_message' => '7',
                    'crontrol_name'    => urlencode($id),
                );
                wp_redirect(add_query_arg($redirect, admin_url('tools.php')));
                exit;

            };

        } else if (isset($_GET['action']) && 'run-cron' == $_GET['action']) {
            if ( ! current_user_can('manage_options')) {
                wp_die(esc_html__('You are not allowed to run cron events.', 'wp-crontrol'));
            }
            $id  = wp_unslash($_GET['id']);
            $sig = wp_unslash($_GET['sig']);
            check_admin_referer("run-cron_{$id}_{$sig}");
            if ($this->run_cron($id, $sig)) {
                $redirect = array(
                    'page'             => 'crontrol_admin_manage_page',
                    'crontrol_message' => '1',
                    'crontrol_name'    => urlencode($id),
                );
                wp_redirect(add_query_arg($redirect, admin_url('tools.php')));
                exit;
            } else {
                $redirect = array(
                    'page'             => 'crontrol_admin_manage_page',
                    'crontrol_message' => '8',
                    'crontrol_name'    => urlencode($id),
                );
                wp_redirect(add_query_arg($redirect, admin_url('tools.php')));
                exit;
            }
        }
    }

    /**
     * Executes a cron event immediately.
     *
     * Executes an event by scheduling a new single event with the same arguments.
     *
     * @param string $hookname The hookname of the cron event to run
     */
    public function run_cron($hookname, $sig) {
        $crons = _get_cron_array();
        foreach ($crons as $time => $cron) {
            if (isset($cron[ $hookname ][ $sig ])) {
                $args = $cron[ $hookname ][ $sig ]['args'];
                delete_transient('doing_cron');
                wp_schedule_single_event(time() - 1, $hookname, $args);
                spawn_cron();

                return true;
            }
        }

        return false;
    }

    /**
     * Adds a new cron event.
     *
     * @param string $next_run A human-readable (strtotime) time that the event should be run at, in the local timezone
     * @param string $schedule The recurrence of the cron event
     * @param string $hookname The name of the hook to execute
     * @param array  $args Arguments to add to the cron event
     */
    public function add_cron($next_run, $schedule, $hookname, $args) {
        $next_run = strtotime($next_run);
        if (false === $next_run) {
            $next_run = time();
        } else {
            $next_run = get_gmt_from_date(date('Y-m-d H:i:s', $next_run), 'U');
        }
        if ( ! is_array($args)) {
            $args = array();
        }
        if ('_oneoff' == $schedule) {
            return wp_schedule_single_event($next_run, $hookname, $args) === null;
        } else {
            return wp_schedule_event($next_run, $schedule, $hookname, $args) === null;
        }
    }

    /**
     * Deletes a cron event.
     *
     * @param string $name The hookname of the event to delete.
     */
    public function delete_cron($to_delete, $sig, $next_run) {
        $crons = _get_cron_array();
        if (isset($crons[ $next_run ][ $to_delete ][ $sig ])) {
            $args = $crons[ $next_run ][ $to_delete ][ $sig ]['args'];
            wp_unschedule_event($next_run, $to_delete, $args);

            return true;
        }

        return false;
    }

    /**
     * Adds a new custom cron schedule.
     *
     * @param string $name The internal name of the schedule
     * @param int    $interval The interval between executions of the new schedule
     * @param string $display The display name of the schedule
     */
    public function add_schedule($name, $interval, $display) {
        $old_scheds          = get_option('crontrol_schedules', array());
        $old_scheds[ $name ] = array('interval' => $interval, 'display' => $display);
        update_option('crontrol_schedules', $old_scheds);
    }

    /**
     * Deletes a custom cron schedule.
     *
     * @param string $name The internal_name of the schedule to delete.
     */
    public function delete_schedule($name) {
        $scheds = get_option('crontrol_schedules', array());
        unset($scheds[ $name ]);
        update_option('crontrol_schedules', $scheds);
    }

    /**
     * Sets up the plugin environment upon first activation.
     *
     * Run using the 'activate_' action.
     */
    public function action_activate() {
        add_option('crontrol_schedules', array());

        // if there's never been a cron event, _get_cron_array will return false
        if (_get_cron_array() === false) {
            _set_cron_array(array());
        }
    }

    /**
     * Adds options & management pages to the admin menu.
     *
     * Run using the 'admin_menu' action.
     */
    public function action_admin_menu() {
        add_options_page(esc_html__('Cron Schedules', 'wp-crontrol'), esc_html__('Cron Schedules', 'wp-crontrol'), 'manage_options', 'crontrol_admin_options_page', array($this, 'admin_options_page'));
        add_management_page(esc_html__('Cron Events', 'wp-crontrol'), esc_html__('Cron Events', 'wp-crontrol'), 'manage_options', 'crontrol_admin_manage_page', array($this, 'admin_manage_page'));
    }

    public function plugin_action_links($actions, $plugin_file, $plugin_data, $context) {
        $actions['crontrol-events']    = sprintf(
            '<a href="%s">%s</a>',
            admin_url('tools.php?page=crontrol_admin_manage_page'),
            esc_html__('Cron Events', 'wp-crontrol')
        );
        $actions['crontrol-schedules'] = sprintf(
            '<a href="%s">%s</a>',
            admin_url('options-general.php?page=crontrol_admin_options_page'),
            esc_html__('Cron Schedules', 'wp-crontrol')
        );

        return $actions;
    }

    /**
     * Gives WordPress the plugin's set of cron schedules.
     *
     * Called by the 'cron_schedules' filter.
     *
     * @param array $scheds The current cron schedules. Usually an empty array.
     *
     * @return array The existing cron schedules along with the plugin's schedules.
     */
    public function filter_cron_schedules($scheds) {
        $new_scheds = get_option('crontrol_schedules', array());

        return array_merge($new_scheds, $scheds);
    }

    /**
     * Displays the options page for the plugin.
     */
    public function admin_options_page() {
        $schedules        = $this->get_schedules();
        $custom_schedules = get_option('crontrol_schedules', array());
        $custom_keys      = array_keys($custom_schedules);

        $messages = array(
            /* translators: 1: The name of the cron schedule. */
            '2' => __('Successfully deleted the cron schedule %s.', 'wp-crontrol'),
            /* translators: 1: The name of the cron schedule. */
            '3' => __('Successfully added the cron schedule %s.', 'wp-crontrol'),
            /* translators: 1: The name of the cron schedule. */
            '7' => __('Cron schedule not added because there was a problem parsing %s.', 'wp-crontrol'),
        );
        if (isset($_GET['crontrol_message']) && isset($_GET['crontrol_name']) && isset($messages[ $_GET['crontrol_message'] ])) {
            $hook = wp_unslash($_GET['crontrol_name']);
            $msg  = sprintf(esc_html($messages[ $_GET['crontrol_message'] ]), '<strong>' . esc_html($hook) . '</strong>');

            printf('<div id="message" class="updated notice is-dismissible"><p>%s</p></div>', $msg); // WPCS:: XSS ok.
        }

        ?>
        <div class="wrap">
            <h1><?php esc_html_e('WP-Cron Schedules', 'wp-crontrol'); ?></h1>
            <p><?php esc_html_e('WP-Cron schedules are the time intervals that are available for scheduling events. You can only delete custom schedules.', 'wp-crontrol'); ?></p>
            <table class="widefat striped">
                <thead>
                <tr>
                    <th scope="col"><?php esc_html_e('Name', 'wp-crontrol'); ?></th>
                    <th scope="col"><?php esc_html_e('Interval', 'wp-crontrol'); ?></th>
                    <th scope="col"><?php esc_html_e('Display Name', 'wp-crontrol'); ?></th>
                    <th>&nbsp;</th>
                </tr>
                </thead>
                <tbody>
                <?php
                if (empty($schedules)) {
                    ?>
                    <tr colspan="4">
                        <td><?php esc_html_e('You currently have no schedules. Add one below.', 'wp-crontrol'); ?></td>
                    </tr>
                    <?php
                } else {
                    foreach ($schedules as $name => $data) {
                        printf('<tr id="sched-%s">',
                            esc_attr($name)
                        );
                        printf('<td>%s</td>',
                            esc_html($name)
                        );
                        printf('<td>%s (%s)</td>',
                            esc_html($data['interval']),
                            esc_html($this->interval($data['interval']))
                        );
                        printf('<td>%s</td>',
                            esc_html($data['display'])
                        );
                        if (in_array($name, $custom_keys)) {
                            $url = add_query_arg(array(
                                'page'   => 'crontrol_admin_options_page',
                                'action' => 'delete-sched',
                                'id'     => urlencode($name),
                            ), admin_url('options-general.php'));
                            $url = wp_nonce_url($url, 'delete-sched_' . $name);
                            printf('<td><span class="row-actions visible"><span class="delete"><a href="%s">%s</a></span></span></td>',
                                esc_url($url),
                                esc_html__('Delete', 'wp-crontrol')
                            );
                        } else {
                            echo '<td>&nbsp;</td>';
                        }
                        echo '</tr>';
                    }
                }
                ?>
                </tbody>
            </table>
        </div>
        <div class="wrap narrow">
            <h2 class="title"><?php esc_html_e('Add new cron schedule', 'wp-crontrol'); ?></h2>
            <p><?php esc_html_e('Adding a new cron schedule will allow you to schedule events that re-occur at the given interval.', 'wp-crontrol'); ?></p>
            <form method="post" action="options-general.php?page=crontrol_admin_options_page">
                <table class="form-table">
                    <tbody>
                    <tr>
                        <th valign="top" scope="row">
                            <label for="cron_internal_name"><?php esc_html_e('Internal name', 'wp-crontrol'); ?></label>
                        </th>
                        <td>
                            <input type="text" class="regular-text" value="" id="cron_internal_name" name="internal_name" required/>
                        </td>
                    </tr>
                    <tr>
                        <th valign="top" scope="row">
                            <label for="cron_interval"><?php esc_html_e('Interval (seconds)', 'wp-crontrol'); ?></label>
                        </th>
                        <td>
                            <input type="number" class="regular-text" value="" id="cron_interval" name="interval" min="1" step="1" required/>
                        </td>
                    </tr>
                    <tr>
                        <th valign="top" scope="row">
                            <label for="cron_display_name"><?php esc_html_e('Display name', 'wp-crontrol'); ?></label>
                        </th>
                        <td>
                            <input type="text" class="regular-text" value="" id="cron_display_name" name="display_name" required/>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <p class="submit">
                    <input id="schedadd-submit" type="submit" class="button-primary" value="<?php esc_attr_e('Add Cron Schedule', 'wp-crontrol'); ?>" name="new_schedule"/>
                </p>
                <?php wp_nonce_field('new-sched'); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Gets a sorted (according to interval) list of the cron schedules
     */
    public function get_schedules() {
        $schedules = wp_get_schedules();
        uasort($schedules, create_function('$a, $b', 'return $a["interval"] - $b["interval"];'));

        return $schedules;
    }

    /**
     * Displays a dropdown filled with the possible schedules, including non-repeating.
     *
     * @param boolean $current The currently selected schedule
     */
    public function schedules_dropdown($current = false) {
        $schedules = $this->get_schedules();
        ?>
        <select class="postform" name="schedule" id="schedule" required>
            <option <?php selected($current, '_oneoff'); ?> value="_oneoff"><?php esc_html_e('Non-repeating', 'wp-crontrol'); ?></option>
            <?php foreach ($schedules as $sched_name => $sched_data) { ?>
                <option <?php selected($current, $sched_name); ?> value="<?php echo esc_attr($sched_name); ?>">
                    <?php
                    printf(
                        '%s (%s)',
                        esc_html($sched_data['display']),
                        esc_html($this->interval($sched_data['interval']))
                    );
                    ?>
                </option>
            <?php } ?>
        </select>
        <?php
    }

    /**
     * Gets the status of WP-Cron functionality on the site by performing a test spawn. Cached for one hour when all is well.
     *
     */
    public function test_cron_spawn($cache = true) {
        global $wp_version;

        if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) {
            /* translators: 1: The name of the PHP constant that is set. */
            return new WP_Error('crontrol_info', sprintf(__('The %s constant is set to true. WP-Cron spawning is disabled.', 'wp-crontrol'), 'DISABLE_WP_CRON'));
        }

        if (defined('ALTERNATE_WP_CRON') && ALTERNATE_WP_CRON) {
            /* translators: 1: The name of the PHP constant that is set. */
            return new WP_Error('crontrol_info', sprintf(__('The %s constant is set to true.', 'wp-crontrol'), 'ALTERNATE_WP_CRON'));
        }

        $cached_status = get_transient('wp-cron-test-ok');

        if ($cache && $cached_status) {
            return true;
        }

        $sslverify     = version_compare($wp_version, 4.0, '<');
        $doing_wp_cron = sprintf('%.22F', microtime(true));

        $cron_request = apply_filters('cron_request', array(
            'url'  => site_url('wp-cron.php?doing_wp_cron=' . $doing_wp_cron),
            'key'  => $doing_wp_cron,
            'args' => array(
                'timeout'   => 3,
                'blocking'  => true,
                'sslverify' => apply_filters('https_local_ssl_verify', $sslverify),
            ),
        ));

        $cron_request['args']['blocking'] = true;

        $result = wp_remote_post($cron_request['url'], $cron_request['args']);

        if (is_wp_error($result)) {
            return $result;
        } else if (wp_remote_retrieve_response_code($result) >= 300) {
            return new WP_Error('unexpected_http_response_code', sprintf(
            /* translators: 1: The HTTP response code. */
                __('Unexpected HTTP response code: %s', 'wp-crontrol'),
                intval(wp_remote_retrieve_response_code($result))
            ));
        } else {
            set_transient('wp-cron-test-ok', 1, 3600);

            return true;
        }

    }

    /**
     * Shows the status of WP-Cron functionality on the site. Only displays a message when there's a problem.
     *
     */
    public function show_cron_status() {

        $status = $this->test_cron_spawn();

        if (is_wp_error($status)) {
            if ('crontrol_info' === $status->get_error_code()) {
                ?>
                <div id="cron-status-notice" class="notice notice-info">
                    <p><?php echo esc_html($status->get_error_message()); ?></p>
                </div>
                <?php
            } else {
                ?>
                <div id="cron-status-error" class="error">
                    <p><?php
                        printf(
                        /* translators: 1: Error message text. */
                            esc_html__('There was a problem spawning a call to the WP-Cron system on your site. This means WP-Cron events on your site may not work. The problem was: %s', 'wp-crontrol'),
                            '<br><strong>' . esc_html($status->get_error_message()) . '</strong>');
                        ?></p>
                </div>
                <?php
            }
        }

    }

    /**
     * Get the display name for the site's timezone.
     *
     * @return string The name and UTC offset for the site's timezone.
     */
    public function get_timezone_name() {
        $timezone_string = get_option('timezone_string', '');
        $gmt_offset      = get_option('gmt_offset', 0);

        if ($gmt_offset >= 0) {
            $gmt_offset = '+' . $gmt_offset;
        }

        if ('' === $timezone_string) {
            $name = sprintf('UTC%s', $gmt_offset);
        } else {
            $name = sprintf('%s (UTC%s)', str_replace('_', ' ', $timezone_string), $gmt_offset);
        }

        return $name;
    }

    /**
     * Shows the form used to add/edit cron events.
     *
     * @param boolean $is_php Whether this is a PHP cron event
     * @param mixed   $existing An array of existing values for the cron event, or null
     */
    public function show_cron_form($is_php, $existing) {
        $new_tabs     = array(
            'cron'     => __('Add Cron Event', 'wp-crontrol'),
            'php-cron' => __('Add PHP Cron Event', 'wp-crontrol'),
        );
        $modify_tabs  = array(
            'cron'     => __('Modify Cron Event', 'wp-crontrol'),
            'php-cron' => __('Modify PHP Cron Event', 'wp-crontrol'),
        );
        $new_links    = array(
            'cron'     => admin_url('tools.php?page=crontrol_admin_manage_page&action=new-cron') . '#crontrol_form',
            'php-cron' => admin_url('tools.php?page=crontrol_admin_manage_page&action=new-php-cron') . '#crontrol_form',
        );
        $display_args = '';
        if ($is_php) {
            $helper_text = esc_html__('Cron events trigger actions in your code. Enter the schedule of the event, as well as the PHP code to execute when the action is triggered.', 'wp-crontrol');
        } else {
            $helper_text = sprintf(
                esc_html__('Cron events trigger actions in your code. A cron event needs a corresponding action hook somewhere in code, e.g. the %1$s file in your theme.', 'wp-crontrol'),
                '<code>functions.php</code>'
            );
        }
        if (is_array($existing)) {
            $other_fields = wp_nonce_field("edit-cron_{$existing['hookname']}_{$existing['sig']}_{$existing['next_run']}", '_wpnonce', true, false);
            $other_fields .= sprintf('<input name="original_hookname" type="hidden" value="%s" />',
                esc_attr($existing['hookname'])
            );
            $other_fields .= sprintf('<input name="original_sig" type="hidden" value="%s" />',
                esc_attr($existing['sig'])
            );
            $other_fields .= sprintf('<input name="original_next_run" type="hidden" value="%s" />',
                esc_attr($existing['next_run'])
            );
            if ( ! empty($existing['args'])) {
                $display_args = wp_json_encode($existing['args']);
            }
            $action        = $is_php ? 'edit_php_cron' : 'edit_cron';
            $button        = $is_php ? $modify_tabs['php-cron'] : $modify_tabs['cron'];
            $show_edit_tab = true;
            list($next_run_date, $next_run_time) = explode(' ', get_date_from_gmt(date('Y-m-d H:i:s', $existing['next_run']), 'Y-m-d H:i:s'));
        } else {
            $other_fields  = wp_nonce_field('new-cron', '_wpnonce', true, false);
            $existing      = array('hookname' => '', 'args' => array(), 'next_run' => 'now', 'schedule' => false);
            $action        = $is_php ? 'new_php_cron' : 'new_cron';
            $button        = $is_php ? $new_tabs['php-cron'] : $new_tabs['cron'];
            $show_edit_tab = false;
            $next_run_time = $next_run_date = '';
        }
        if ($is_php) {
            if ( ! isset($existing['args']['code'])) {
                $existing['args']['code'] = '';
            }
            if ( ! isset($existing['args']['name'])) {
                $existing['args']['name'] = '';
            }
        }

        $allowed = ( ! $is_php || current_user_can('edit_files'));
        ?>
        <div id="crontrol_form" class="wrap narrow">
            <h2 class="nav-tab-wrapper">
                <a href="<?php echo esc_url($new_links['cron']); ?>" class="nav-tab<?php if ( ! $show_edit_tab && ! $is_php) {
                    echo ' nav-tab-active';
                } ?>"><?php echo esc_html($new_tabs['cron']); ?></a>
                <a href="<?php echo esc_url($new_links['php-cron']); ?>" class="nav-tab<?php if ( ! $show_edit_tab && $is_php) {
                    echo ' nav-tab-active';
                } ?>"><?php echo esc_html($new_tabs['php-cron']); ?></a>
                <?php if ($show_edit_tab) { ?>
                    <span class="nav-tab nav-tab-active"><?php echo esc_html($button); ?></span>
                <?php } ?>
            </h2>
            <?php if ($allowed) { ?>
                <p><?php echo $helper_text; // WPCS:: XSS ok. ?></p>
                <form method="post" action="<?php echo esc_url(admin_url('tools.php?page=crontrol_admin_manage_page')); ?>">
                    <?php echo $other_fields; // WPCS:: XSS ok. ?>
                    <table class="form-table">
                        <tbody>
                        <?php if ($is_php) : ?>
                            <tr>
                                <th valign="top" scope="row">
                                    <label for="hookcode"><?php esc_html_e('PHP Code', 'wp-crontrol'); ?></label>
                                </th>
                                <td>
                                    <textarea class="large-text code" rows="10" cols="50" id="hookcode" name="hookcode" required><?php echo esc_textarea($existing['args']['code']); ?></textarea>
                                </td>
                            </tr>
                            <tr>
                                <th valign="top" scope="row">
                                    <label for="eventname"><?php esc_html_e('Event Name (optional)', 'wp-crontrol'); ?></label>
                                </th>
                                <td>
                                    <input type="text" class="regular-text" id="eventname" name="eventname" value="<?php echo esc_attr($existing['args']['name']); ?>"/>
                                </td>
                            </tr>
                        <?php else : ?>
                            <tr>
                                <th valign="top" scope="row">
                                    <label for="hookname"><?php esc_html_e('Hook Name', 'wp-crontrol'); ?></label>
                                </th>
                                <td>
                                    <input type="text" class="regular-text" id="hookname" name="hookname" value="<?php echo esc_attr($existing['hookname']); ?>" required/>
                                </td>
                            </tr>
                            <tr>
                                <th valign="top" scope="row">
                                    <label for="args"><?php esc_html_e('Arguments (optional)', 'wp-crontrol'); ?></label>
                                </th>
                                <td>
                                    <input type="text" class="regular-text" id="args" name="args" value="<?php echo esc_attr($display_args); ?>"/>
                                    <p class="description"><?php
                                        /* translators: 1, 2, and 3: Example values for an input field. */
                                        echo esc_html(sprintf(
                                            __('e.g. %s, %s, or %s', 'wp-crontrol'),
                                            '[25]',
                                            '["asdf"]',
                                            '["i","want",25,"cakes"]'
                                        ));
                                        ?></p>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <tr>
                            <th valign="top" scope="row">
                                <label for="next_run_date"><?php esc_html_e('Next Run', 'wp-crontrol'); ?></label>
                            </th>
                            <td>
                                <script>
                                  jQuery( function ( $ ) {
                                    var isDateInputSupported = function () {
                                      var elem = document.createElement( 'input' );
                                      elem.setAttribute( 'type', 'date' );
                                      elem.value = 'foo';
                                      return (elem.type == 'date' && elem.value != 'foo');
                                    }

                                    if ( !isDateInputSupported() ) {
                                      $( '.datetime-fallback' ).show();
                                    }
                                  } );
                                </script>
                                <input type="date" placeholder="YYYY-MM-DD" id="next_run_date" name="next_run_date" value="<?php echo esc_attr($next_run_date); ?>" maxlength="10" pattern="\d{4}\-\d{2}\-\d{2}" required/>
                                <input type="time" step="1" placeholder="HH:MM:SS" id="next_run_time" name="next_run_time" value="<?php echo esc_attr($next_run_time); ?>" maxlength="8" pattern="\d{2}:\d{2}:\d{2}" required/>
                                <?php printf(
                                /* translators: %s Timezone name. */
                                    esc_html__('Timezone: %s', 'wp-crontrol'),
                                    '<code>' . esc_html($this->get_timezone_name()) . '</code>'
                                ); ?>
                                <p class="description datetime-fallback hidden"><?php
                                    /* translators: %s Date/time format for an input field. */
                                    echo esc_html(sprintf(
                                        __('Format: %s', 'wp-crontrol'),
                                        date('Y') . '-02-25 12:34:00'
                                    ));
                                    ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th valign="top" scope="row">
                                <label for="schedule"><?php esc_html_e('Recurrence', 'wp-crontrol'); ?></label>
                            </th>
                            <td>
                                <?php $this->schedules_dropdown($existing['schedule']); ?>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                    <p class="submit">
                        <input type="submit" class="button-primary" value="<?php echo esc_attr($button); ?>" name="<?php echo esc_attr($action); ?>"/>
                    </p>
                </form>
            <?php } else { ?>
                <div class="error inline">
                    <p><?php esc_html_e('You cannot add or edit PHP cron events because your user account does not have the ability to edit files.', 'wp-crontrol'); ?></p>
                </div>
            <?php } ?>
        </div>
        <?php
    }

    public function get_cron_events() {

        $crons  = _get_cron_array();
        $events = array();

        if (empty($crons)) {
            return new WP_Error(
                'no_events',
                __('You currently have no scheduled cron events.', 'wp-crontrol')
            );
        }

        foreach ($crons as $time => $cron) {
            foreach ($cron as $hook => $dings) {
                foreach ($dings as $sig => $data) {

                    # This is a prime candidate for a Crontrol_Event class but I'm not bothering currently.
                    $events["$hook-$sig-$time"] = (object) array(
                        'hook'     => $hook,
                        'time'     => $time,
                        'sig'      => $sig,
                        'args'     => $data['args'],
                        'schedule' => $data['schedule'],
                        'interval' => isset($data['interval']) ? $data['interval'] : null,
                    );

                }
            }
        }

        return $events;

    }

    /**
     * Displays the manage page for the plugin.
     */
    public function admin_manage_page() {
        $messages = array(
            /* translators: 1: The name of the cron event. */
            '1' => __('Successfully executed the cron event %s.', 'wp-crontrol'),
            /* translators: 1: The name of the cron event. */
            '4' => __('Successfully edited the cron event %s.', 'wp-crontrol'),
            /* translators: 1: The name of the cron event. */
            '5' => __('Successfully created the cron event %s.', 'wp-crontrol'),
            /* translators: 1: The name of the cron event. */
            '6' => __('Successfully deleted the cron event %s.', 'wp-crontrol'),
            /* translators: 1: The name of the cron event. */
            '7' => __('Failed to the delete the cron event %s.', 'wp-crontrol'),
            /* translators: 1: The name of the cron event. */
            '8' => __('Failed to the execute the cron event %s.', 'wp-crontrol'),
        );
        if (isset($_GET['crontrol_name']) && isset($_GET['crontrol_message']) && isset($messages[ $_GET['crontrol_message'] ])) {
            $hook = wp_unslash($_GET['crontrol_name']);
            $msg  = sprintf(esc_html($messages[ $_GET['crontrol_message'] ]), '<strong>' . esc_html($hook) . '</strong>');

            printf('<div id="message" class="updated notice is-dismissible"><p>%s</p></div>', $msg); // WPCS:: XSS ok.
        }
        $events      = $this->get_cron_events();
        $doing_edit  = (isset($_GET['action']) && 'edit-cron' == $_GET['action']) ? wp_unslash($_GET['id']) : false;
        $time_format = 'Y-m-d H:i:s';

        $core_hooks = array(
            'wp_version_check',
            'wp_update_plugins',
            'wp_update_themes',
            'wp_scheduled_delete',
            'wp_scheduled_auto_draft_delete',
            'update_network_counts',
        );

        $this->show_cron_status();

        ?>
        <div class="wrap">
            <h1><?php esc_html_e('WP-Cron Events', 'wp-crontrol'); ?></h1>
            <table class="widefat striped">
                <thead>
                <tr>
                    <th scope="col"><?php esc_html_e('Hook Name', 'wp-crontrol'); ?></th>
                    <th scope="col"><?php esc_html_e('Arguments', 'wp-crontrol'); ?></th>
                    <th scope="col"><?php esc_html_e('Actions', 'wp-crontrol'); ?></th>
                    <th scope="col"><?php esc_html_e('Next Run', 'wp-crontrol'); ?></th>
                    <th scope="col"><?php esc_html_e('Recurrence', 'wp-crontrol'); ?></th>
                    <th scope="col">
                        <span class="screen-reader-text"><?php esc_html_e('Actions', 'wp-crontrol'); ?></span>
                    </th>
                </tr>
                </thead>
                <tbody>
                <?php
                if (is_wp_error($events)) {
                    ?>
                    <tr>
                        <td colspan="6"><?php echo esc_html($events->get_error_message()); ?></td>
                    </tr>
                    <?php
                } else {
                    foreach ($events as $id => $event) {

                        if ($doing_edit && $doing_edit == $event->hook && $event->time == $_GET['next_run'] && $event->sig == $_GET['sig']) {
                            $doing_edit = array(
                                'hookname' => $event->hook,
                                'next_run' => $event->time,
                                'schedule' => ($event->schedule ? $event->schedule : '_oneoff'),
                                'sig'      => $event->sig,
                                'args'     => $event->args,
                            );
                        }

                        if (empty($event->args)) {
                            $args = '<em>' . esc_html__('None', 'wp-crontrol') . '</em>';
                        } else {
                            if (defined('JSON_UNESCAPED_SLASHES')) {
                                $args = '<code>' . wp_json_encode($event->args, JSON_UNESCAPED_SLASHES) . '</code>';
                            } else {
                                $args = '<code>' . stripslashes(wp_json_encode($event->args)) . '</code>';
                            }
                        }

                        echo '<tr id="cron-' . esc_attr($id) . '" class="">';

                        if ('crontrol_cron_job' == $event->hook) {
                            if ( ! empty($event->args['name'])) {
                                /* translators: 1: The name of the PHP cron event. */
                                echo '<td><em>' . esc_html(sprintf(__('PHP Cron (%s)', 'wp-crontrol'), $event->args['name'])) . '</em></td>';
                            } else {
                                echo '<td><em>' . esc_html__('PHP Cron', 'wp-crontrol') . '</em></td>';
                            }
                            echo '<td><em>' . esc_html__('PHP Code', 'wp-crontrol') . '</em></td>';
                            echo '<td><em>' . esc_html__('WP Crontrol', 'wp-crontrol') . '</em></td>';
                        } else {
                            echo '<td>' . esc_html($event->hook) . '</td>';
                            echo '<td>' . $args . '</td>'; // WPCS:: XSS ok.
                            echo '<td>';
                            $callbacks = array();
                            foreach ($this->get_action_callbacks($event->hook) as $callback) {
                                $callbacks[] = '<code>' . esc_html($callback['callback']['name']) . '</code>';
                            }
                            echo implode('<br>', $callbacks); // WPCS:: XSS ok.
                            echo '</td>';
                        }

                        echo '<td>';
                        printf('%s (%s)',
                            esc_html(get_date_from_gmt(date('Y-m-d H:i:s', $event->time), $time_format)),
                            esc_html($this->time_since(time(), $event->time))
                        );
                        echo '</td>';

                        if ($event->schedule) {
                            echo '<td>';
                            echo esc_html($this->interval($event->interval));
                            echo '</td>';
                        } else {
                            echo '<td>';
                            esc_html_e('Non-repeating', 'wp-crontrol');
                            echo '</td>';
                        }

                        $links = array();

                        echo '<td><span class="row-actions visible">';

                        $link    = array(
                            'page'     => 'crontrol_admin_manage_page',
                            'action'   => 'edit-cron',
                            'id'       => urlencode($event->hook),
                            'sig'      => urlencode($event->sig),
                            'next_run' => urlencode($event->time),
                        );
                        $link    = add_query_arg($link, admin_url('tools.php')) . '#crontrol_form';
                        $links[] = "<a href='" . esc_url($link) . "'>" . esc_html__('Edit', 'wp-crontrol') . '</a>';

                        $link    = array(
                            'page'     => 'crontrol_admin_manage_page',
                            'action'   => 'run-cron',
                            'id'       => urlencode($event->hook),
                            'sig'      => urlencode($event->sig),
                            'next_run' => urlencode($event->time),
                        );
                        $link    = add_query_arg($link, admin_url('tools.php'));
                        $link    = wp_nonce_url($link, "run-cron_{$event->hook}_{$event->sig}");
                        $links[] = "<a href='" . esc_url($link) . "'>" . esc_html__('Run Now', 'wp-crontrol') . '</a>';

                        if ( ! in_array($event->hook, $core_hooks, true)) {
                            $link    = array(
                                'page'     => 'crontrol_admin_manage_page',
                                'action'   => 'delete-cron',
                                'id'       => urlencode($event->hook),
                                'sig'      => urlencode($event->sig),
                                'next_run' => urlencode($event->time),
                            );
                            $link    = add_query_arg($link, admin_url('tools.php'));
                            $link    = wp_nonce_url($link, "delete-cron_{$event->hook}_{$event->sig}_{$event->time}");
                            $links[] = "<span class='delete'><a href='" . esc_url($link) . "'>" . esc_html__('Delete', 'wp-crontrol') . '</a></span>';
                        }

                        echo implode(' | ', $links); // WPCS:: XSS ok.
                        echo '</span></td>';
                        echo '</tr>';

                    }
                }
                ?>
                </tbody>
            </table>

        </div>
        <?php
        if (is_array($doing_edit)) {
            $this->show_cron_form('crontrol_cron_job' == $doing_edit['hookname'], $doing_edit);
        } else {
            $this->show_cron_form((isset($_GET['action']) and 'new-php-cron' == $_GET['action']), false);
        }
    }

    protected function get_action_callbacks($name) {
        global $wp_filter;

        $actions = array();

        if (isset($wp_filter[ $name ])) {

            # http://core.trac.wordpress.org/ticket/17817
            $action = $wp_filter[ $name ];

            foreach ($action as $priority => $callbacks) {

                foreach ($callbacks as $callback) {

                    $callback = self::populate_callback($callback);

                    $actions[] = array(
                        'priority' => $priority,
                        'callback' => $callback,
                    );

                }

            }

        }

        return $actions;

    }

    public static function populate_callback(array $callback) {

        // If Query Monitor is installed, use its rich callback analysis:
        if (method_exists('QM_Util', 'populate_callback')) {
            return QM_Util::populate_callback($callback);
        }

        if (is_string($callback['function']) && (false !== strpos($callback['function'], '::'))) {
            $callback['function'] = explode('::', $callback['function']);
        }

        if (is_array($callback['function'])) {

            if (is_object($callback['function'][0])) {
                $class  = get_class($callback['function'][0]);
                $access = '->';
            } else {
                $class  = $callback['function'][0];
                $access = '::';
            }

            $callback['name'] = $class . $access . $callback['function'][1] . '()';

        } elseif (is_object($callback['function'])) {

            if (is_a($callback['function'], 'Closure')) {
                $callback['name'] = 'Closure';
            } else {
                $class            = get_class($callback['function']);
                $callback['name'] = $class . '->__invoke()';
            }

        } else {

            $callback['name'] = $callback['function'] . '()';

        }

        return $callback;

    }

    /**
     * Pretty-prints the difference in two times.
     *
     * @param int $older_date Unix timestamp.
     * @param int $newer_date Unix timestamp.
     *
     * @return string The pretty time_since value
     * @link http://binarybonsai.com/code/timesince.txt
     */
    public function time_since($older_date, $newer_date) {
        return $this->interval($newer_date - $older_date);
    }

    /**
     * Converts a period of time in seconds into a human-readable format representing the interval.
     *
     * Example:
     *
     *     echo self::interval( 90 );
     *     // 1 minute 30 seconds
     *
     * @param int $since A period of time in seconds.
     *
     * @return string        An interval represented as a string.
     */
    public function interval($since) {
        // array of time period chunks
        $chunks = array(
            /* translators: 1: The number of years in an interval of time. */
            array(60 * 60 * 24 * 365, _n_noop('%s year', '%s years', 'wp-crontrol')),
            /* translators: 1: The number of months in an interval of time. */
            array(60 * 60 * 24 * 30, _n_noop('%s month', '%s months', 'wp-crontrol')),
            /* translators: 1: The number of weeks in an interval of time. */
            array(60 * 60 * 24 * 7, _n_noop('%s week', '%s weeks', 'wp-crontrol')),
            /* translators: 1: The number of days in an interval of time. */
            array(60 * 60 * 24, _n_noop('%s day', '%s days', 'wp-crontrol')),
            /* translators: 1: The number of hours in an interval of time. */
            array(60 * 60, _n_noop('%s hour', '%s hours', 'wp-crontrol')),
            /* translators: 1: The number of minutes in an interval of time. */
            array(60, _n_noop('%s minute', '%s minutes', 'wp-crontrol')),
            /* translators: 1: The number of seconds in an interval of time. */
            array(1, _n_noop('%s second', '%s seconds', 'wp-crontrol')),
        );

        if ($since <= 0) {
            return __('now', 'wp-crontrol');
        }

        // we only want to output two chunks of time here, eg:
        // x years, xx months
        // x days, xx hours
        // so there's only two bits of calculation below:

        // step one: the first chunk
        for ($i = 0, $j = count($chunks); $i < $j; $i++) {
            $seconds = $chunks[ $i ][0];
            $name    = $chunks[ $i ][1];

            // finding the biggest chunk (if the chunk fits, break)
            if (($count = floor($since / $seconds)) != 0) {
                break;
            }
        }

        // set output var
        $output = sprintf(translate_nooped_plural($name, $count, 'wp-crontrol'), $count);

        // step two: the second chunk
        if ($i + 1 < $j) {
            $seconds2 = $chunks[ $i + 1 ][0];
            $name2    = $chunks[ $i + 1 ][1];

            if (($count2 = floor(($since - ($seconds * $count)) / $seconds2)) != 0) {
                // add to output var
                $output .= ' ' . sprintf(translate_nooped_plural($name2, $count2, 'wp-crontrol'), $count2);
            }
        }

        return $output;
    }

    public static function init() {

        static $instance = null;

        if ( ! $instance) {
            $instance = new Crontrol;
        }

        return $instance;

    }
}

// Get this show on the road
Crontrol::init();
