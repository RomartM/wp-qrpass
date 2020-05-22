<?php

if (! defined( 'ABSPATH' ) ){
    exit;
}
/*
 * qrp_get_path
 *
 * Returns specified file path
 *
 * @since  1.0.0
 *
 * @param  string $filename, Specified file path
 * @return string
 */
function qrp_get_path( $filename = '' )
{
    return QRP_PLUGIN_PATH . ltrim( $filename, '/' );
}

/*
 * qrp_include
 *
 * Includes a file within the qrp plugin
 *
 * @since  2.0.0
 *
 * @param  string $filename, Specified file path
 * @return void
 */
function qrp_include( $filename = '' )
{
    $file_path = qrp_get_path( $filename );
    if ( file_exists( $file_path ) ) {
        include_once( $file_path );
    }
}

/*
 * qrp_require
 *
 * Require a file within the qrp plugin
 *
 * @since  2.0.0
 *
 * @param  string $filename, Specified file path
 * @return void
 */
function qrp_require( $filename = '' )
{
    $file_path = qrp_get_path( $filename );
    if ( file_exists( $file_path ) ) {
        require_once( $file_path );
    }
}

/*
 * qrp_plugin_home_url
 *
 * Returns plugin home url
 *
 * @since  2.0.0
 *
 * @param  void
 * @return string
 */
function qrp_plugin_home_url( )
{
    return add_query_arg( array(
        'page' => 'qrp-entries-manager'
    ), admin_url( 'options-general.php' ) );
}
