<?php
/*
Plugin Name: Admin Menu Editor
Plugin URI: http://w-shadow.com/blog/2008/12/20/admin-menu-editor-for-wordpress/
Description: Lets you directly edit the WordPress admin menu. You can re-order, hide or rename existing menus, add custom menus and more. 
Version: 999 (1.4.5)
Author: Janis Elsts
Author URI: http://w-shadow.com/blog/
*/

// Modified: place in theme directory instead of plugins
define('MEPATH',    get_stylesheet_directory() . '/plugins/admin-menu-editor/');
define('MEURL',     get_stylesheet_directory_uri() . '/plugins/admin-menu-editor/');

if ( include( dirname( __FILE__ ) . '/includes/version-conflict-check.php' ) ) {
	return;
}

//Are we running in the Dashboard?
if ( is_admin() ) {
    //Load the plugin
    require 'includes/menu-editor-core.php';
    $wp_menu_editor = new WPMenuEditor(__FILE__, 'ws_menu_editor');

}//is_admin()