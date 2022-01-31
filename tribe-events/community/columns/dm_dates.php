<?php
// Don't load directly
defined('WPINC') or die;

$dates = array();
foreach($event->dm_dates as $date){
    array_push($dates, esc_html(date('D, M j, Y ', strtotime($date))));
}

echo join('<br>', $dates);