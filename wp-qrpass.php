<?php
/**
* Plugin Name: QR Pass
* Plugin URI:
* Description: Verifies the autheticity of generated QR Code for COVID-19 entry form
* Version: 1.2
* Author: RomartM
* Author URI:
* TextDomain: qr-pass
**/

if (! defined( 'ABSPATH' ) ){
    exit;
}

// Declare some global constants
define( 'WP_QRP_VERSION', '1.2' );
define( 'WP_QRP_TABLE_LOG_VERSION', '1.2' );
define( 'WP_QRP_TABLE_USER_LIST_VERSION', '1.3' );
define( 'WP_QRP_ROOT', dirname( __FILE__ ) );
define( 'WP_QRP_URL', plugins_url( '/', __FILE__ ) );
define( 'WP_QRP_BASE_FILE', basename( dirname( __FILE__ ) ) . '/wp-qrpass.php' );
define( 'WP_QRP_BASE_NAME', plugin_basename( __FILE__ ) );
define( 'WP_QRP_PATH', plugin_dir_path( __FILE__ ) ); //use for include files to other files
define( 'WP_QRP_PRODUCT_NAME', 'QR Pass integration for Caldera Forms' );
define( 'WP_QRP_OPTION_PREFIX', 'wp_qrp_' );
define( 'WP_QRP_STORAGE_PATH', WP_CONTENT_URL . '/uploads/' );

/*
 * include classes
 */

if ( ! class_exists( 'QRPUtils' ) ) {
    include( WP_QRP_ROOT . '/includes/QRPUtility.php' );
}

if ( ! class_exists( 'QRPAdminPages' ) ) {
    include( WP_QRP_ROOT . '/classes/views/QRPAdminPages.php' );
}

if ( ! class_exists( 'QRPInit' ) ) {
    include( WP_QRP_ROOT . '/classes/init/QRPInit.php' );
}

// Initialize the QR Pass class
$init = new QRPInit();

