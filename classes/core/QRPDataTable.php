<?php

/**
 * Class QRPDataTable
 */

if (! defined( 'ABSPATH' ) ){
    exit;
}

class QRPDataTable
{
    public $logger_table_name;
    public $user_list_table_name;
    private $charset_collate;

    /**
     * QRPDataTable constructor.
     */
    public function __construct()
    {
        global $wpdb;
        $this->logger_table_name = $wpdb ->prefix. 'qrp_logger';
        $this->user_list_table_name = $wpdb->prefix . 'qrp_user_list';
        $this->charset_collate = $wpdb->get_charset_collate();
    }

    /**
     *  Install Plugin Data Tables
     */
    public function install(){
        $this->loggerInstall();
        $this->userListInstall();
    }

    /**
     *  Uninstall Plugin Data Tables
     */
    public function uninstall(){
        $this->loggerUninstall();
        $this->userListUninstall();
    }

    /**
     * loggerInstall
     */
    protected function loggerInstall(){
        $installed_ver = get_option( "qrp_logger_dt_version" );

        if ( $installed_ver != QRP_LOGGER_TABLE ) {

            $this->loggerUpdate();

        }else{

            $sql = "CREATE TABLE $this->logger_table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                id_number tinytext NULL,
                time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                type tinytext NULL,
                details text NULL,
                PRIMARY KEY  (id)
                ) $this->charset_collate;";

            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );

            add_option( 'qrp_logger_dt_version', QRP_LOGGER_TABLE );
        }
    }

    /**
     * loggerUpdate
     */
    protected function loggerUpdate(){
        $sql = "CREATE TABLE $this->logger_table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                id_number tinytext NULL,
                time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                type tinytext NULL,
                details text NULL,
                PRIMARY KEY  (id)
                ) $this->charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );

        update_option( 'qrp_logger_dt_version', QRP_LOGGER_TABLE );
    }

    /**
     *  Drop logger data table
     */
    protected function loggerUninstall(){
        global $wpdb;

        $wpdb->query( "DROP TABLE IF EXISTS  $this->logger_table_name" );
        delete_option( 'qrp_logger_dt_version' );
    }

    /**
     * userListInstall
     */
    protected function userListInstall(){
        $installed_ver = get_option( "qrp_user_list_dt_version" );

        if ( $installed_ver != QRP_USER_LIST_TABLE ) {
            $this->userListUpdate();
        }else{

            $sql = "CREATE TABLE $this->user_list_table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                id_number tinytext NULL,
                qrp_group tinytext NULL,
                qrp_type tinytext NULL,
                first_name varchar(55) NULL,
                middle_name varchar(55) NULL,
                last_name varchar(55) NULL,
                name_ext varchar(55) NULL,
                status text NULL,
                PRIMARY KEY  (id)
                ) $this->charset_collate;";

            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );

            add_option( 'qrp_user_list_dt_version', QRP_USER_LIST_TABLE );
        }
    }

    /**
     * userListUpdate
     */
    protected function userListUpdate(){
        $sql = "CREATE TABLE $this->user_list_table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                id_number tinytext NULL,
                qrp_group tinytext NULL,
                qrp_type tinytext NULL,
                first_name text NULL,
                middle_name text NULL,
                last_name text NULL,
                name_ext text NULL,
                status text NULL,
                PRIMARY KEY  (id)
                ) $this->charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );

        update_option( 'qrp_user_list_dt_version', QRP_USER_LIST_TABLE );
    }

    /**
     *  Drop user-list data table
     */
    protected function userListUninstall(){
        global $wpdb;

        $wpdb->query( "DROP TABLE IF EXISTS  $this->user_list_table_name" );
        delete_option( 'qrp_user_list_dt_version' );
    }

    /**
     * Returns action method status
     *
     * @param string $method Function name of the method
     * @param $is_status
     * @return array
     */
    protected function getActionStatus( $method, $is_status ){
        global $wpdb;

        $this->legacyLogger($method); // Log all actions into a file

        if( 0 == $is_status ){
            return array(
                'method'   => $method,
                'status'   => 'error' );
        }
        return array(
            'method'    => $method,
            'status'    => 'success',
            'id'        => $wpdb -> insert_id );
    }

    /**
     * Get current datetime
     *
     * @return false|string
     */
    function get_date() {
        $date_format = get_option('date_format');
        $time_format = get_option('time_format');
        $date = date("{$date_format} {$time_format}", current_time('timestamp'));
        return $date;
    }

    /**
     *  Logs all data modification actions
     *
     * @param $action
     */
    public function legacyLogger($action){
        if (is_user_logged_in()) {
            global $current_user;
            wp_get_current_user();
            $username = $current_user->user_login;
        } else {
            $username = "anonymous";
        }
        $logMsg = "QRPDT, user: (" . $username . "), timestamp:" . $this->get_date() . ', action:'. $action . PHP_EOL;
        file_put_contents('qrdt-legacy.logs', $logMsg, FILE_APPEND | LOCK_EX);
    }

    /**
     * insertLog
     *
     * @param $id
     * @param $type
     * @param $details
     * @return array|string[]
     */
    public function insertLog($id, $type, $details){
        global $wpdb;

        $action = $wpdb->insert(
            $this->logger_table_name,
            array(
                'id_number' => $id,
                'time' => current_time( 'mysql' ),
                'type' => $type,
                'details' => $details,
            )
        );

        return $this->getActionStatus(__FUNCTION__, $action);
    }

    /**
     * Get Logs by type
     *
     * @param $type
     * @return mixed
     */
    public function getLog($type){
        global $wpdb;

        return $wpdb->get_row("SELECT * FROM ".  $this->logger_table_name ." WHERE type LIKE BINARY '".$type."'", ARRAY_A);
    }

    /**
     * Reset Logs by type
     *
     * @param $type
     * @return array|string[]
     */
    public function resetLog($type){
        global $wpdb;

        $action = $wpdb->delete( $this->logger_table_name, array( 'type' => $type ), array( '%d' ) );

        return $this->getActionStatus(__FUNCTION__, $action);
    }

    /**
     * insertUser
     *
     * @param $id_number
     * @param $group
     * @param $type
     * @param string $first_name
     * @param string $middle_name
     * @param string $last_name
     * @param string $name_ext
     * @param string $status
     * @return array|string[]
     */
    public function insertUser($id_number , $group, $type, $first_name='', $middle_name='', $last_name='', $name_ext='', $status=''){
        global $wpdb;

        $action = $wpdb->insert(
            $this->user_list_table_name,
            array(
                'id_number' => $id_number,
                'qrp_group' => $group,
                'qrp_type' => $type,
                'first_name' => $first_name,
                'middle_name' => $middle_name,
                'last_name' => $last_name,
                'name_ext' => $name_ext,
                'status' => $status
            )
        );

        return $this->getActionStatus(__FUNCTION__, $action);
    }

    public function deleteUser($id_number){
        global $wpdb;

        $action = $wpdb->delete( $this->user_list_table_name, array( 'id_number' => $id_number ) );

        return $this->getActionStatus(__FUNCTION__, $action);
    }

    /**
     * Update user status
     *
     * @param $id_number
     * @param $status
     * @return array|string[]
     */
    public function updateUserStatus($id_number, $status){
        global $wpdb;

        $action = $wpdb-> update(
            $this->user_list_table_name,
            array( 'status' => $status ),
            array( 'id_number' => ucwords($id_number) ),
            array( '%s', '%s' )
        );

        return $this->getActionStatus(__FUNCTION__, $action);
    }

    /**
     * Get user data
     *
     * @param $id_number
     * @return mixed
     */
    public function getUserData($id_number){
        global $wpdb;

        return $wpdb->get_row("SELECT * FROM ".  $this->user_list_table_name ." WHERE id_number LIKE BINARY '".$id_number."'", ARRAY_A);
    }

    public function isUserDataExist($id_number){
        global $wpdb;

        $results  = $wpdb->get_results("SELECT * FROM ".  $this->user_list_table_name ." WHERE id_number LIKE BINARY '".$id_number."'", ARRAY_N);

        return (count($results) > 0);
    }

}