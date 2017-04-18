<?php
/*
Plugin Name: WP Thor Post Template
Plugin URI:
Description: This plugin allows theme authors to create a post template as well as page template for the single post.
Version: 1.0.0
Author: ThunderBear Design
Author URI: http://thunderbeardesign.com

Build: 1.0.0
*/

// Prevent direct access to this file.
if ( ! defined( 'ABSPATH' ) ) {
    header( 'HTTP/1.0 403 Forbidden' );
    echo 'This file should not be accessed directly!';
    exit; // Exit if accessed directly
}

//
define('THORCPTEMP_VERSION', '1.0.0');
define('THORCPTEMP_PLUGIN_URL', WP_PLUGIN_URL . '/' . dirname(plugin_basename(__FILE__)));
define('THORCPTEMP_PLUGIN_PATH', WP_PLUGIN_DIR . '/' . dirname(plugin_basename(__FILE__)));
define('THORCPTEMP_PLUGIN_FILE_PATH', WP_PLUGIN_DIR . '/' . plugin_basename(__FILE__));

//Load The Admin Class
if (!class_exists('ThorCPTempAdmin')) {
    require_once THORCPTEMP_PLUGIN_PATH . '/app/classes/ThorCPTempAdmin.class.php';
}

$obj = new ThorCPTempAdmin(); //initiate admin object    

?>