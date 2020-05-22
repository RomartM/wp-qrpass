<?php
/**
* Plugin Name: QR Pass
* Plugin URI:
* Description: Verifies the autheticity of generated QR Code for COVID-19 entry form
* Version: 1.1
* Author: RomartM
* Author URI:
* TextDomain: qr-pass
**/

if (! defined( 'ABSPATH' ) ){
    exit;
}

define('QRP_PLUGIN_PATH', plugin_dir_url( __FILE__ ));
define( 'QRP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

define('PHOTO_CLOUD_STORAGE_URL', 'https://site.buksu.edu.ph/fetch-photo.php?photo_id=');
define('FALLBACK_IMAGE', 'https://www.w3schools.com/howto/img_avatar.png');
define('IMAGE_STORAGE_PATH', WP_CONTENT_DIR.'/uploads/qrp-assets'); // TODO: Check if dir exists

define('QRP_LOGGER_TABLE', '1.1');
define('QRP_USER_LIST_TABLE', '1.1');

function qrp_init(){
    include 'classes/core/QRPDataTable.php';
    include 'classes/core/QRPCrypto.php';
    include 'classes/core/QRPHashValidator.php';
    include 'classes/core/QRPGenerator.php';
    include 'classes/core/QRPResponseFilter.php';
    include 'classes/core/QRPResultGenerator.php';
    include 'classes/core/QRPActivityCollector.php';
    include 'classes/core/QRPEntriesManager.php';
    include 'classes/views/QRPEntriesTable.php';
    include 'overrides/caldera-hooks.php';
    include 'views/entries-manager.php';
    include 'views/validate.php';
    include 'views/image-resource.php';
}

add_action('plugins_loaded', 'qrp_init');
