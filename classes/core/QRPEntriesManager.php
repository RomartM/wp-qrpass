<?php

if (! defined( 'ABSPATH' ) ){
    exit;
}

/**
 * Class QRPEntriesManager
 */
class QRPEntriesManager extends QRPDataTable
{
    private $form_id;
    public $form_entries;
    public $form_instance;

    /**
     * QRPEntriesManager constructor.
     * @param $form_id
     */
    public function __construct($form_id)
    {
        parent::__construct();
        $this->form_id = $form_id;
        $this->form_entries = $this->get_cf_entries( $this->form_id, 1, 9999999 );
        $this->form_instance = Caldera_Forms_Forms::get_form( $this->form_id );
    }

    /**
     * Get caldera form entries modified version
     * @param $form
     * @param int $page
     * @param int $perpage
     * @param string $status
     * @return array|void
     */
    public static function get_cf_entries( $form, $page = 1, $perpage = 20, $status = 'active' ) {

        if ( is_string( $form ) ) {
            $form = Caldera_Forms_Forms::get_form( $form );
        }

        if ( isset( $form[ 'ID' ])) {
            $form_id = $form[ 'ID' ];
        }else{
            return;
        }

        $field_labels = array();
        $backup_labels = array();
        $selects = array();


        $fields = array();
        if ( ! empty( $form[ 'fields' ] ) ) {
            foreach ( $form[ 'fields' ] as $fid => $field ) {
                $fields[ $field[ 'slug' ] ] = $field;

                if ( ! empty( $field[ 'entry_list' ] ) ) {
                    $selects[] = "'" . $field[ 'slug' ] . "'";
                    $field_labels[ $field[ 'slug' ] ] = $field[ 'label' ];
                }
                $has_vars = array();
                if ( ! empty( $form[ 'variables' ][ 'types' ] ) ) {
                    $has_vars = $form[ 'variables' ][ 'types' ];
                }
                if ( ( count( $backup_labels ) < 4 && ! in_array( 'entryitem', $has_vars ) ) && in_array( $field[ 'type' ], array(
                        'text',
                        'email',
                        'date',
                        'name'
                    ) )
                ) {
                    // backup only first 4 fields
                    $backup_labels[ $field[ 'slug' ] ] = $field[ 'label' ];
                }
            }
            $field_labels[ 'id_number' ] = 'ID Number';
        }

        if ( empty( $field_labels ) ) {
            $field_labels = $backup_labels;
        }

        $entries = new Caldera_Forms_Entry_Entries( $form, $perpage );

        $data = array();

        $filter = null;

        $data[ 'trash' ]  = $entries->get_total( 'trash' );
        $data[ 'active' ] = $entries->get_total( 'active' );

        // set current total
        if ( ! empty( $status ) && isset( $data[ $status ] ) ) {
            $data[ 'total' ] = $entries->get_total( $status );
        } else {
            $data[ 'total' ] = $data[ 'active' ];
        }


        $data[ 'pages' ] = ceil( $data[ 'total' ] / $perpage );

        if ( ! empty( $page ) ) {
            $page = abs( $page );
            if ( $page > $data[ 'pages' ] ) {
                $page = $data[ 'pages' ];
            }
        }

        $data['current_page'] = $page;

        if($data['total'] > 0) {

            $data[ 'form' ] = $form_id;

            $data[ 'fields' ] = $field_labels;


            $the_entries = $entries->get_page( $page, $status );

            if ( ! empty( $the_entries ) ) {

                $ids = array();
                $data[ 'entries' ] = array();


                /** @var Caldera_Forms_Entry $an_entry */
                foreach ( $the_entries as $an_entry ) {
                    $ids[] = $an_entry->get_entry_id();
                }
                // init field types to initialize view rendering in entry lists
                Caldera_Forms_Fields::get_all();

                foreach ( $ids as $entry_id ) {
                    $rows = $entries->get_rows( $page, (int) $entry_id, $status );
                    foreach ( $rows as $row ) {
                        $e = 'E' . $row->entry_id;
                        if ( ! empty( $row->_user_id ) ) {
                            $user = get_userdata( $row->_user_id );
                            if ( ! empty( $user ) ) {
                                $data[ 'entries' ][ $e ][ 'user' ][ 'ID' ]     = $user->ID;
                                $data[ 'entries' ][ $e ][ 'user' ][ 'name' ]   = $user->data->display_name;
                                $data[ 'entries' ][ $e ][ 'user' ][ 'email' ]  = $user->data->user_email;
                                $data[ 'entries' ][ $e ][ 'user' ][ 'avatar' ] = get_avatar( $user->ID, 64 );
                            }
                        }

                        $data[ 'entries' ][ $e ][ '_entry_id' ] = $row->entry_id;

                        $submitted = $row->_datestamp;


                        $data[ 'entries' ][ $e ][ '_date' ] = Caldera_Forms::localize_time( $submitted );

                        // setup default data array
                        if ( ! isset( $data[ 'entries' ][ $e ][ 'data' ] ) ) {
                            if ( isset( $field_labels ) ) {
                                foreach ( $field_labels as $slug => $label ) {
                                    if($slug == 'id_number'){
                                        // setup labels ordering
                                        $data[ 'entries' ][ $e ][ 'data' ][ $slug ] = $row->entry_id;
                                    }else{
                                        // setup labels ordering
                                        $data[ 'entries' ][ $e ][ 'data' ][ $slug ] = null;
                                    }
                                }
                            }
                        }

                        if ( ! empty( $field_labels[ $row->slug ] ) ) {

                            // check view handler
                            $field = Caldera_Forms_Field_Util::get_field(  $row->slug, $form, true );

                            // maybe json?
                            $is_json = json_decode( $row->value, ARRAY_A );
                            if ( ! empty( $is_json ) ) {
                                $row->value = $is_json;
                            }

                            if( is_string( $row->value ) ) {
                                $row->value = esc_html( stripslashes_deep( $row->value ) );
                            }else{
                                $row->value = stripslashes_deep( Caldera_Forms_Sanitize::sanitize( $row->value ) );
                            }

                            $row->value = apply_filters( 'caldera_forms_view_field_' . $field[ 'type' ], $row->value, $field, $form );


                            if ( isset( $data[ 'entries' ][ $e ][ 'data' ][ $row->slug ] ) ) {
                                if( $row->slug !== 'id_number' ){
                                    // array based - add another entry
                                    if ( ! is_array( $data[ 'entries' ][ $e ][ 'data' ][ $row->slug ] ) ) {
                                        $tmp = $data[ 'entries' ][ $e ][ 'data' ][ $row->slug ];
                                        $data[ 'entries' ][ $e ][ 'data' ][ $row->slug ] = array( $tmp );
                                    }
                                    $data[ 'entries' ][ $e ][ 'data' ][ $row->slug ][] = $row->value;
                                } else {
                                    $data[ 'entries' ][ $e ][ 'data' ][ $row->slug ] = $row->value;
                                }
                            } else {
                                $data[ 'entries' ][ $e ][ 'data' ][ $row->slug ] = $row->value;
                            }
                        }

                        if ( ! empty( $form[ 'variables' ][ 'types' ] ) ) {
                            foreach ( $form[ 'variables' ][ 'types' ] as $var_key => $var_type ) {
                                if ( $var_type == 'entryitem' ) {
                                    $data[ 'fields' ][ $form[ 'variables' ][ 'keys' ][ $var_key ] ] = ucwords( str_replace( '_', ' ', $form[ 'variables' ][ 'keys' ][ $var_key ] ) );
                                    $data[ 'entries' ][ $e ][ 'data' ][ $form[ 'variables' ][ 'keys' ][ $var_key ] ] = Caldera_Forms::do_magic_tags( $form[ 'variables' ][ 'values' ][ $var_key ], $row->_entryid );
                                }
                            }
                        }


                    }
                }
            }
        }


        return $data;

    }

    /**
     * Verify action status
     * @param $id_number
     * @param $action
     * @param $status
     * @return |null
     */
    private function veriyActionStatus($id_number, $action, $status){

        $form = $this->form_instance;
        $data = $this->form_entries;
        $entry = new Caldera_Forms_Entry( $form, $this->getEntryIDbyIDNumber($data, $id_number) );
        $activity = new QRPActivityCollector($entry->get_entry(), $form);
        switch ($action){
            case 'approve':
                if($status['status'] == 'success'){
                    $activity->logValidated($id_number);
                }
                return $status;
            case 'revoke':
                if($status['status'] == 'success'){
                    $activity->logInvalidated($id_number);
                }
                return $status;
            default:
                return null;
        }
    }

    /**
     * Get Caldera Form Entry ID by ID Number
     *
     * @param $data
     * @param $id_number
     * @return mixed
     */
    public function getEntryIDbyIDNumber($data, $id_number){
        foreach ($data['entries'] as $entry){
            if(ucwords(str_replace('_', '', $entry['data']['id_number'])) == $id_number){
                return $entry['_entry_id'];
            }
        }
        return false;
    }

    /**
     * Check if value exists
     * @param $field_meta
     * @return string
     */
    public function getFieldValueIfExist($field_meta){
        if(!empty($field_meta)){
            return $field_meta->get_value();
        }
        return "";
    }

    public function syncUserData($id_number){
        $form = $this->form_instance;
        $data = $this->form_entries;


        //Get  form entry
        $entry = new Caldera_Forms_Entry( $form, $this->getEntryIDbyIDNumber($data, $id_number) );

        //Get field object of field to get
        $first_name_meta = $entry->get_field(Caldera_Forms_Field_Util::get_field_by_slug('first_name', $form)['ID']);
        $middle_name_meta = $entry->get_field(Caldera_Forms_Field_Util::get_field_by_slug('middle_name', $form)['ID']);
        $last_name_meta = $entry->get_field(Caldera_Forms_Field_Util::get_field_by_slug('last_name', $form)['ID']);

        $first_name = $this->getFieldValueIfExist($first_name_meta);;
        $middle_name = $this->getFieldValueIfExist($middle_name_meta);;
        $last_name = $this->getFieldValueIfExist($last_name_meta);;

        //Get user group
        $group = "";
        $link_forms_data = get_option( WP_QRP_OPTION_PREFIX . "link_forms");
        foreach ($link_forms_data as $form_id){
            if($form_id['cf_id']==$this->form_id){
                $group = str_replace(' ', '_', strtolower($form_id["group"]));
                break;
            }
        }

        return $this->insertUser($id_number , '', $this->form_id, $group, '', $first_name, $middle_name, $last_name);
    }

    /**
     * Approve QR Pass
     *
     * @param $id_number
     * @return array|string[]
     */
    public function approve($id_number){
        if($this->isUserDataExist($id_number)){
            return $this->veriyActionStatus($id_number, __FUNCTION__, $this->updateUserStatus($id_number, __FUNCTION__));
        } else {
            $result = $this->syncUserData($id_number);
            if($result['status'] == 'success'){
                return $this->veriyActionStatus($id_number, __FUNCTION__, $this->updateUserStatus($id_number, __FUNCTION__));
            }else{
                return array(
                    'method'   => 'DATA_INSERTION',
                    'status'   => 'error' );
            }
        }
    }

    /**
     * Revoke QR Pass
     * @param $id_number
     * @return array|string[]
     */
    public function revoke($id_number){
        return $this->veriyActionStatus($id_number, __FUNCTION__, $this->updateUserStatus($id_number, __FUNCTION__));
    }

    /**
     * Set reference id
     * @param $id_number
     * @param $ref_id
     * @return string[]|null
     */
    public function link($id_number, $ref_id){
        if($this->isUserDataExist($id_number)){
            return $this->veriyActionStatus($id_number, __FUNCTION__, $this->updateUserLink($id_number, $ref_id));
        } else {
            $result = $this->syncUserData($id_number);
            if($result['status'] == 'success'){
                return $this->veriyActionStatus($id_number, __FUNCTION__, $this->updateUserLink($id_number, $ref_id));
            }else{
                return array(
                    'method'   => 'DATA_INSERTION',
                    'status'   => 'error' );
            }
        }
    }

    /**
     * Delete entry and related fields
     * @param $id_number
     * @return array|string[]
     */
    public function delete($id_number){
        $data = $this->form_entries;
        $result = Caldera_Forms_Entry_Bulk::delete_entries(array($this->getEntryIDbyIDNumber($data, $id_number)));
        $result_entries = $this->deleteUser($id_number);
        if($result_entries['status'] == 'success' && $result !== false){
            return $result_entries;
        }
        return array(
            'method'   => __FUNCTION__,
            'message'  => array($result, $result_entries),
            'status'   => 'error' );
    }

    /**
     * Generic email sender
     * @param $id_number
     * @param string $email_address
     * @return string[]
     */
    public function send($id_number, $email_address=""){

        if(empty($email_address)){
            $email_address = $this->getEmailAddress($id_number)['content'];
        }

        $form = Caldera_Forms::get_form($this->form_id);
        $data = $this->form_entries;
        $parsed_param = "";
        $is_attach_qr=false; // Default

        $entry = new Caldera_Forms_Entry( $form, $this->getEntryIDbyIDNumber($data, $id_number) );

        $qrp_result = new QRPResultGenerator($entry->get_entry_id(), $form);
        $header = Caldera_Forms_Magic_Doer::do_field_magic(get_option( WP_QRP_OPTION_PREFIX . "email_message"), $entry->get_entry_id(), $form);
        $footer = Caldera_Forms_Magic_Doer::do_field_magic(get_option( WP_QRP_OPTION_PREFIX . "email_message_footer"), $entry->get_entry_id(), $form);

        $message = $qrp_result->resultHTML($header, $footer);;

        $raw_data = get_option( WP_QRP_OPTION_PREFIX . 'form_response_config_'.$this->form_id);

        parse_str($raw_data, $parsed_param);

        if(!empty($parsed_param['response_settings']['is_attach_qr'])){
            if($parsed_param['response_settings']['is_attach_qr']=='on'){
                $is_attach_qr=true;
            }else{
                $is_attach_qr=false;
            }
        }


        $results = $this->coreSend($id_number, $email_address, $message, $is_attach_qr);

        if($results){
            return array(
                'method'   => 'send_email',
                'status'   => 'success' );
        }else{
            return array(
                'method'   => 'send_email',
                'status'   => 'error' );
        }
    }

    /**
     * Admin email sender
     * @param $id_number
     * @param $email_address
     * @param $message
     * @param bool $is_attach_qr
     * @return string[]
     */
    public function adminSend($id_number, $email_address, $message, $is_attach_qr=false){
        $results = $this->coreSend($id_number, $email_address, $message, $is_attach_qr);
        if($results){
            return array(
                'method'   => 'send_email',
                'status'   => 'success' );
        }else{
            return array(
                'method'   => 'send_email',
                'status'   => 'error' );
        }
    }

    /**
     * Core email sender
     * @param $id_number
     * @param $email_address
     * @param $message
     * @param bool $is_attach_qr
     * @return bool
     */
    public function coreSend($id_number, $email_address, $message, $is_attach_qr=false){
        $attachments = array();
        $form = $this->form_instance;
        $data = $this->form_entries;
        $entry = new Caldera_Forms_Entry( $form, $this->getEntryIDbyIDNumber($data, $id_number) );
        $qrp_gen = new QRPGenerator($id_number, $this->form_id);
        $subject = get_option( WP_QRP_OPTION_PREFIX . "email_subject");
        $message = Caldera_Forms_Magic_Doer::do_field_magic($message, $entry->get_entry_id(), $form);

        if($is_attach_qr){
            $attachments[0] = $qrp_gen->getTempPath();
        }

        $headers = array('Content-Type: text/html; charset=UTF-8');
        return wp_mail($email_address, $subject , $message, $headers, $attachments);
    }

    /**
     * Get user entry fields
     * @param $id_number
     * @param bool $include_media
     * @return array
     */
    public function getUserEntryFields($id_number, $include_media=true){
        $filter_slugs = 'start_screening qrpass proceed next next2 submit';
        $formatted_fields = array();
        $index = 0;
        $form = $this->form_instance;
        $data = $this->form_entries;
        //Get  form entry
        $entry = new Caldera_Forms_Entry( $form, $this->getEntryIDbyIDNumber($data, $id_number) );

        foreach ($entry->get_fields() as $field){
            if(strpos($filter_slugs, $field->{'slug'}) === false){
                $formatted_fields[$index]['label'] = str_replace('_', ' ', $field->{'slug'});
                $formatted_fields[$index]['value'] = $field->{'value'};
                $index++;
            }
        }

        $qrp_generator  = new QRPGenerator($id_number, $this->form_id);

        if($include_media){
            // Get QR
            $index++;
            $formatted_fields[$index]['label'] = 'qrcode';
            $formatted_fields[$index]['value'] = $qrp_generator->getResourceURL();

            // Get Photo
            $index++;
            $formatted_fields[$index]['label'] = 'photo';
            $formatted_fields[$index]['value'] = sprintf("%s/?photo_id=%s", get_home_url(), $qrp_generator->getHash());
        }

        return $formatted_fields;
    }

    /**
     * Get email address
     * @param $id_number
     * @return array
     */
    public function getEmailAddress($id_number){
        $form = $this->form_instance;
        $data = $this->form_entries;

        //Get  form entry
        $entry = new Caldera_Forms_Entry( $form, $this->getEntryIDbyIDNumber($data, $id_number) );

        //Get field object of field to edit
        $email_address = $this->getFieldValueIfExist($entry->get_field(Caldera_Forms_Field_Util::get_field_by_slug('email_address', $form)['ID']));

        $this->legacyLogger(__FUNCTION__);

        return array(
            'content'  => $email_address,
            'method'   => __FUNCTION__,
            'status'   => 'success' );
    }


    public function getUserLink($id_number){

        $ref_id = $this->getUserData($id_number)['ref_id'];

        $this->legacyLogger(__FUNCTION__);

        return array(
            'content'  => $ref_id,
            'method'   => __FUNCTION__,
            'status'   => 'success' );
    }

    /**
     * Update Email Address on entry
     *
     * @param $id_number
     * @param $email
     * @return array|int|string
     */
    public function updateEmail($id_number, $email){
        $form = $this->form_instance;
        $data = $this->form_entries;

        //Get  form entry
        $entry = new Caldera_Forms_Entry( $form, $this->getEntryIDbyIDNumber($data, $id_number) );

        //get all fields
        $fields = $entry->get_fields();

        //Get field object of field to edit
        $field_to_edit = $entry->get_field(Caldera_Forms_Field_Util::get_field_by_slug('email_address', $form)['ID']);;

        //Change fields value
        $field_to_edit->value = $email;

        //Put modified field back in entry
        $entry->add_field( $field_to_edit );

        //Save entry
        $entry_id = $entry->save();

        return array(
            'content'       => $entry_id,
            'method'   => __FUNCTION__,
            'status'   => 'success' );
    }

    /**
     * Search Entry by ID Number
     *
     * @param $id_number
     * @return mixed
     */
    public function search($id_number){
        $data = $this->form_entries;

        foreach ($data['entries'] as $entry){
            if(str_replace('_', '', $entry['data']['id_number']) == $id_number){
                return $entry;
            }
        }
        return false;
    }

    /**
     * Search Entry by Full Name
     *
     * @param $name
     * @return mixed
     */
    public function searchByName($name){
        $data = $this->form_entries;
        $form = $this->form_instance;


        foreach ($data['entries'] as $entry){
            //Get  form entry
            $entry = new Caldera_Forms_Entry( $form, $entry['_entry_id'] );

            //Get field object of field to get
            $first_name_meta = $entry->get_field(Caldera_Forms_Field_Util::get_field_by_slug('first_name', $form)['ID']);
            $middle_name_meta = $entry->get_field(Caldera_Forms_Field_Util::get_field_by_slug('middle_name', $form)['ID']);
            $last_name_meta = $entry->get_field(Caldera_Forms_Field_Util::get_field_by_slug('last_name', $form)['ID']);

            $first_name = $this->getFieldValueIfExist($first_name_meta);
            $middle_name = $this->getFieldValueIfExist($middle_name_meta);
            $last_name = $this->getFieldValueIfExist($last_name_meta);

            if(sprintf("%s %s %s", $first_name, $middle_name, $last_name) == $name){
                return $entry;
            }
        }
        return false;
    }


}