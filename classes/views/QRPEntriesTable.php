<?php

if (! defined( 'ABSPATH' ) ){
    exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Class QRPEntriesTable
 */
class QRPEntriesTable extends WP_List_Table
{

    private $type;
    private $form;
    private $form_id;
    private $data;
    private $data_table;
    private static $instance;

    function __construct($form){
        parent::__construct( [
            'singular' => __( 'User Entry', 'qr-pass' ), //singular name of the listed records
            'plural'   => __( 'User Entries', 'qr-pass' ), //plural name of the listed records
            'ajax'     => true //should this table support ajax?
        ] );
        self::$instance = $this;
        $this->type = '';
        $this->form = $form;
        $this->form_id = $form['ID'];
        $this->data = QRPEntriesManager::get_cf_entries( $this->form_id, 1, 1 );
        $this->data_table = new QRPDataTable();
    }

    protected function verifyIfEmpty(){
        return empty($this->data['entries']);
    }

    protected function getDataLabel($slug_name){
        $field_meta = Caldera_Forms_Field_Util::get_field_by_slug($slug_name, $this->form);
        return $field_meta['label'];
    }

    protected function getDataKeys(){
        return array_keys(array_values($this->data['entries'])[0]['data']);
    }

    protected function formatEntry($formatted, $index, $entry){
        $keys = $this->getDataKeys();
        foreach ($keys as $key){
            $formatted[$index][$key] = ucwords($entry['data'][$key]);
        }
        $formatted[$index]['date_entry'] = $entry['_date'];

        return $formatted;
    }

    protected function initialFormatEntry($entry){
        $entry['data']['id_number'] = str_replace('_', '', $entry['data']['id_number']);
        return $entry;
    }

    protected function formatCFData($entries, $search=''){

        if(empty($entries)){
            return array();
        }

        $formatted = array();
        $index = 0;

        if(!empty($search)){
            foreach ($entries as $entry){
                $entry = $this->initialFormatEntry($entry);
                if(!empty(array_search($search, $entry['data']))){
                    $formatted = $this->formatEntry($formatted, $index, $entry);
                }
                $index++;
            }
            return $formatted;
        }

        foreach ($entries as $entry){
            $entry = $this->initialFormatEntry($entry);
            $formatted = $this->formatEntry($formatted, $index, $entry);
            $index++;
        }

        return $formatted;
    }

    protected function usort_reorder( $a, $b ) {
        // If no sort, default to title
        $orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'id_number';
        // If no order, default to asc
        $order = ( ! empty($_GET['order'] ) ) ? $_GET['order'] : 'asc';
        // Determine sort order
        $result = strcmp( $a[$orderby], $b[$orderby] );
        // Send final sort direction to usort
        return ( $order === 'asc' ) ? $result : -$result;
    }

    protected function hideButton($action, $status){
        if(($action == 'approve_pass' && $status == 'approve') || ($action == 'revoke_pass' && $status == 'revoke') || ($action == 'revoke_pass' && $status == '')){
            return "style=\"display:none;\"";
        }
        return  $status;
    }

    protected function custom_row_actions( $actions, $id_number, $always_visible = false) {
        $data_table = new QRPDataTable();
        $user_status = $data_table->getUserData($id_number)['status'];
        $action_count = count( $actions );
        $i            = 0;

        if ( ! $action_count ) {
            return '';
        }

        $out = '<div class="' . ( $always_visible ? 'row-actions visible' : 'row-actions' ) . '">';
        foreach ( $actions as $action => $link ) {
            ++$i;
            ( $i == $action_count ) ? $sep = '' : $sep = ' | ';
            $out.= "<span ".$this->hideButton($action, $user_status)." class='$action'>$link$sep</span>";
        }
        $out .= '</div>';

        $out .= '<button type="button" class="toggle-row"><span class="screen-reader-text">' . __( 'Show more details' ) . '</span></button>';

        return $out;
    }

    protected function column_id_number($item){
        $actions = array(
            'view_profile'      => sprintf('<a href="javascript:void(0);" class="qrp-action-trigger" data-nonce="%s" data-action="qrp_view" data-id="%s" data-cf-id="%s">View</a>', wp_create_nonce('qrp_view_' . $item['id_number']), $item['id_number'], $this->form_id),
            'approve_pass'      => sprintf('<a href="javascript:void(0);" class="qrp-action-trigger" data-nonce="%s" data-action="qrp_approve" data-id="%s" data-cf-id="%s">Approve</a>', wp_create_nonce('qrp_approve_' . $item['id_number']), $item['id_number'], $this->form_id),
            'revoke_pass'      => sprintf('<a href="javascript:void(0);" class="qrp-action-trigger" data-nonce="%s" data-action="qrp_revoke" data-id="%s" data-cf-id="%s">Revoke</a>', wp_create_nonce('qrp_revoke_' . $item['id_number']), $item['id_number'], $this->form_id),
            'link_ref'      => sprintf('<a href="javascript:void(0);" class="qrp-action-trigger" data-nonce="%s" data-action="qrp_update_link" data-id="%s" data-cf-id="%s">Link</a>', wp_create_nonce('qrp_update_link_' . $item['id_number']), $item['id_number'], $this->form_id),
            'update_email'    => sprintf('<a href="javascript:void(0);" class="qrp-action-trigger" data-nonce="%s" data-action="qrp_update_email" data-id="%s" data-cf-id="%s">Update Email</a>', wp_create_nonce('qrp_update_email_' . $item['id_number']), $item['id_number'], $this->form_id),
            'send_email'    => sprintf('<a href="javascript:void(0);" class="qrp-action-trigger" data-nonce="%s" data-action="qrp_send_email" data-id="%s" data-cf-id="%s">Notify</a>', wp_create_nonce('qrp_send_email_' . $item['id_number']), $item['id_number'], $this->form_id),
        );

        return sprintf('%1$s %2$s', $item['id_number'], $this->custom_row_actions($actions, $item['id_number']) );
    }

    /**
     * Retrieve QRP data from the database
     *
     * @param int $per_page
     * @param int $page_number
     *
     * @param string $search
     * @return mixed
     */
    public static function get_list( $per_page, $page_number, $search='' ){

        if(!empty($search)){
            $data = QRPEntriesManager::get_cf_entries( self::$instance->form_id, 1, 99999999 );
            return self::$instance->formatCFData(empty($data['entries']) ? array() : $data['entries'], $search);
        }

        $data = QRPEntriesManager::get_cf_entries( self::$instance->form_id, $page_number, $per_page );

        return self::$instance->formatCFData(empty($data['entries']) ? array() : $data['entries']);
    }

    /**
     * Method for item deletion
     *
     * @param int $id QRP item ID
     *
     * @return array
     */
    public static function delete_item( $id ){
        $entries = new QRPEntriesManager(self::$instance->form_id);
        return $entries->delete( $id );
    }

    /**
     * Returns the total counts of records in the database
     *
     * @param void
     *
     * @return null|string
     */

    public static function get_record_count() {
        $data = QRPEntriesManager::get_cf_entries( self::$instance->form_id, 1, 9999999 );
        return count(empty($data['entries']) ? array() : $data['entries']);
    }

    /**
     * Render a message if no QRP postypes available
     *
     */
    public function no_items() {
        _e( 'No Entries added yet.', 'qr-pass' );
    }

    /**
     * Render a column when no column specific method exists.
     *
     * @param array $item
     * @param string $column_name
     *
     * @return mixed
     */
    public function column_default( $item, $column_name ) {
        if($column_name === 'status'){
            $data_table = new QRPDataTable();
            return $data_table->getUserData($item['id_number'])['status'];
        }
        return $item[$column_name];
    }


    /**
     * Render the bulk delete checkbox
     *
     * @param array $item
     *
     * @return string
     */
    public function column_cb( $item ) {
        return '<input type="checkbox" name="bulk-action[]" value="' . $item['id_number'] . '" />';
    }

    /**
     *  Associative array of columns
     *
     * @return array
     */
    function get_columns() {
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'id_number' => __( 'ID Number', 'qr-pass' ),
            'status' => __( 'Status', 'qr-pass' ),
            'date_entry' => __( 'Date', 'qr-pass' )
        );
        foreach ($this->getDataKeys() as $key){
            if($key !== 'id_number'){
                $columns[$key] =  __( $this->getDataLabel($key), 'qr-pass' );
            }
        }
        return $columns;
    }

    /**
     * Columns to make sortable.
     *
     * @return array
     */
    public function get_sortable_columns() {
        $columns = array('date_entry'     => array( 'date_entry', true ));

        foreach ($this->getDataKeys() as $key){
            $columns[$key] =  array( $key, true );
        }

        return $columns;
    }

    /**
     * Returns an associative array containing the bulk action
     *
     * @return array
     */
    public function get_bulk_actions() {
        $actions = [
            'bulk-approve' => 'Approve',
            'bulk-revoke' => 'Revoke',
            'bulk-send' => 'Notify',
            'bulk-delete' => 'Delete'
        ];

        return $actions;
    }

    /**
     * Handles data query and filter, sorting, and pagination.
     * @param string $search
     */
    public function prepare_items($search = '') {

        if($this->verifyIfEmpty()){
            echo "<div class=\"qrp-table-no-items\">";
            $this->no_items();
            echo "</div>";
            wp_die();
        }

        $this->_column_headers = $this->get_column_info();

        /** Process bulk action */
        $this->process_bulk_action();
        $per_page     = $this -> get_items_per_page( 'qrp_entries_per_page');
        $current_page = $this -> get_pagenum();
        $items = $this -> get_list( $per_page, $current_page , $search );

        if(!empty($_REQUEST['orderby'])){
            usort( $items, array( &$this, 'usort_reorder' ) );
        }

        if(!empty($search)){
            $total_items  = count($items);
        }else{
            $total_items  = $this->get_record_count();
        }

        $this->set_pagination_args( [
            'total_items' => $total_items, //WE have to calculate the total number of items
            'per_page'    => $per_page //WE have to determine how many items to show on a page
        ] );

        $this -> items = $items;
    }

    function generic_bulk_actions($action, $function, $message){
        // If the delete bulk action is triggered
        if ( ( isset( $_POST[ 'action' ] ) && $_POST[ 'action' ] == $action )
            || ( isset( $_POST[ 'action2' ] ) && $_POST[ 'action2' ] == $action )
        ) {

            $delete_ids = esc_sql( $_POST[ 'bulk-action' ] );
            $action_logs = array();
            $index = 0;
            // loop over the array of record IDs and delete them
            foreach ( $delete_ids as $id ) {
                $del = call_user_func($function, $id);
                $action_logs[$index]['status'] = $del['status'];
                $action_logs[$index]['id'] = $id;
                $index++;
            }

            $counts["success"] = 0;
            $counts["error"] = 0;
            foreach ($action_logs as $log){
                $counts["success"] = $log['status'] == 'success'? $counts["success"]+1 : $counts["success"];
                $counts["error"] = $log['status'] == 'error'? $counts["error"]+1 : $counts["error"];
            }

            if($counts["success"] !== 0){
                $this->qrp_action_success_notice($message, $counts["success"] );
            }

            if($counts["error"] !== 0){
                $this->qrp_action_error_notice('error in '. $message, $counts["error"] );
            }
        }
    }

    /**
     * Process Bulk Action
     */
    public function process_bulk_action() {

        $qrp_entries = new QRPEntriesManager($this->form_id);

        // Delete Items
        $this->generic_bulk_actions('bulk-delete', array($this, 'delete_item') , 'deleting');

        // Approve Items
        $this->generic_bulk_actions('bulk-approve', array($qrp_entries, 'approve'), 'approving');

        // Revoke Items
        $this->generic_bulk_actions('bulk-revoke', array($qrp_entries, 'revoke'), 'revoking');

        // Notify Items
        $this->generic_bulk_actions('bulk-send', array($qrp_entries, 'send'), 'notifying');

    }

    protected function qrp_action_error_notice($type, $counts) {
        ?>
        <div class="error notice">
            <p><?php _e( 'Encountered ' . $type . ' ' . $counts . ' entry(s).', 'qr-pass' ); ?></p>
        </div>
        <?php
    }

    protected function qrp_action_success_notice($type, $counts) {
        ?>
        <div class="updated notice">
            <p><?php _e( 'Success ' . $type . ' ' . $counts . ' entry(s).', 'qr-pass' ); ?></p>
        </div>
        <?php
    }


}
