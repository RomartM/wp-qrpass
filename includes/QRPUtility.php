<?php

/*
 * Utilities class for Google Sheet Integration for Caldera Forms
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Utilities class - singleton class
 * @since 1.0
 */
class QRPUtility {

    private function __construct() {
        // Do Nothing
    }

    /**
     * Get the singleton instance of the QRPUtility class
     *
     * @return QRPUtility instance of QRPUtility
     */
    public static function instance() {

        static $instance = NULL;
        if ( is_null( $instance ) ) {
            $instance = new QRPUtility();
        }
        return $instance;
    }

    /**
     * Format group name to underscores
     *
     * @param $group_name
     * @return string|string[]
     */
    public function format_group_name($group_name){
        return str_replace(' ', '_', strtolower($group_name));
    }

    /**
     * Load Plugin Classes
     *
     * @param array $classes_dir
     */
    public function load_classes($classes_dir = array()){
        forEach($classes_dir as $dir){
            $dir_path = WP_QRP_ROOT . '/' . $dir;
            if(file_exists($dir_path)){
                $array = explode('/', $dir);
                if ( ! class_exists( explode('.',end($array))[0] ) ) {
                    include( WP_QRP_ROOT . '/' . $dir );
                }
            }
        }
    }

    /**
     * Get current storage url
     */
    public function get_current_storage(){
        $is_remote = get_option( WP_QRP_OPTION_PREFIX . "is_storage_remote'");
        if(!empty($is_remote)){
            return get_option( WP_QRP_OPTION_PREFIX . "storage_remote_url'") . '/';
        }else{
            return WP_QRP_STORAGE_PATH;
        }
    }

    /**
     * Get current datetime
     *
     * @return false|string
     */
    function get_date() {
        $date_format = get_option('date_format');
        $time_format = get_option('time_format');
        return date("{$date_format} {$time_format}", current_time('timestamp'));
    }

    /**
     * Prints message (string or array) in the debug.log file
     *
     * @param mixed $message
     */
    public function logger( $message ) {
        if ( WP_DEBUG === true ) {
            if ( is_array( $message ) || is_object( $message ) ) {
                error_log( print_r( $message, true ) );
            } else {
                error_log( $message );
            }
        }
    }

    /**
     * Display error or success message in the admin section
     *
     * @param array $data containing type and message
     * @return string with html containing the error message
     *
     * @since 1.0
     */
    public function admin_notice( $data = array() ) {
        // extract message and type from the $data array
        $message = isset( $data['message'] ) ? $data['message'] : "";
        $message_type = isset( $data['type'] ) ? $data['type'] : "";
        switch ( $message_type ) {
            case 'error':
                $admin_notice = '<div id="message" class="error notice is-dismissible">';
                break;
            case 'update':
                $admin_notice = '<div id="message" class="updated notice is-dismissible">';
                break;
            case 'update-nag':
                $admin_notice = '<div id="message" class="update-nag">';
                break;
            default:
                $message = __( 'There\'s something wrong with your code...', 'wp-qrp' );
                $admin_notice = "<div id=\"message\" class=\"error\">\n";
                break;
        }

        $admin_notice .= "    <p>" . __( $message, 'wp-qrp' ) . "</p>\n";
        $admin_notice .= "</div>\n";
        return $admin_notice;
    }

    /**
     * Utility function to get the current user's role
     *
     * @since 1.0
     */
    public function get_current_user_role() {
        global $wp_roles;
        foreach ( $wp_roles->role_names as $role => $name ) :
            if ( current_user_can( $role ) )
                return $role;
        endforeach;
    }

    /**
     * Utility function to get the current user's role
     *
     * @param $error
     * @since 1.0
     */
    public static function debug_log($error){
        try {
            if( ! is_dir( WP_QRP_PATH.'logs' ) ) {
                mkdir( WP_QRP_PATH . 'logs', 0755, true );
            }
        } catch (Exception $e) {

        }
        try {
            $log = fopen( WP_QRP_PATH . "logs/log.txt", 'a');
            if ( is_array( $error ) ) {
                fwrite($log, print_r(date_i18n( 'j F Y H:i:s', current_time( 'timestamp' ) )." \t PHP ".phpversion(), TRUE));
                fwrite( $log, print_r($error, TRUE));
            } else {
                $result = fwrite($log, print_r(date_i18n( 'j F Y H:i:s', current_time( 'timestamp' ) )." \t PHP ".phpversion()." \t $error \r\n", TRUE));
            }
            fclose( $log );
        } catch (Exception $e) {

        }
    }
}