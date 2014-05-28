<?php

/*
 * Plugin Name: XMLRPC Application Password
 * Plugin URI: http://github.com/blobaugh/xmlrpc-application-password
 * Description: Adds per user application passwords to XMLRPC. This is useful when 2 factor login is enabled in the browser but not native client applications
 * Version: 1.0
 * Author: Ben Lobaugh
 * Author URI: http://ben.lobaugh.net
 */

define( 'XAP_USER_META_KEY', '_application_passwords' );

// This is only for setting up testing data
// require_once( plugin_dir_path( __FILE__ ) . 'test/test_data.php' );
// add_action( 'init', array( new xap_test_data(), 'insert_test_data' ), 5 );
// add_action( 'init', array( new xap_test_data(), 'delete_test_data' ), 5 );

require_once( plugin_dir_path( __FILE__ ) . 'class.xap.php' );
Xap::get_instance(); // Start the engines!
