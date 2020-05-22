<?php

/**
 * Class QRPInit
 */

if (! defined( 'ABSPATH' ) ){
    exit;
}

class QRPInit
{
    public function __construct()
    {
    }


}

function myplugin_update_db_check() {
    global $jal_db_version;
    if ( get_site_option( 'jal_db_version' ) != $jal_db_version ) {
        jal_install();
    }
}
add_action( 'plugins_loaded', 'myplugin_update_db_check' );

register_activation_hook( __FILE__, 'jal_install' );
register_activation_hook( __FILE__, 'jal_install_data' );