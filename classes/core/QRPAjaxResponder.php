<?php


class QRPAjaxResponder
{
    public function __construct(){
        add_action("wp_ajax_qrp_view", array( $this, "qrp_view" ));
        add_action("wp_ajax_qrp_approve", array( $this, "qrp_approve" ));
        add_action("wp_ajax_qrp_revoke", array( $this, "qrp_revoke" ));
        add_action("wp_ajax_qrp_update_link", array( $this, "qrp_update_link" ));
        add_action("wp_ajax_qrp_update_email", array( $this, "qrp_update_email" ));
        add_action("wp_ajax_qrp_send_email", array( $this, "qrp_send_email" ));
        add_action("wp_ajax_qrp_form_filters", array( $this, "qrp_form_filters" ));
        add_action("wp_ajax_qrp_form_response", array( $this, "qrp_form_response" ));
        add_action("wp_ajax_resend_email_public" , array( $this, "resend_email_public" ));
        add_action("wp_ajax_nopriv_resend_email_public" , array( $this, "resend_email_public" ));
    }

    /**
     * Generic AJAX Responder
     *
     * @param $action
     * @param $callback
     */
    public function generic_responder($action, $callback){
        $response = array();
        if ( !wp_verify_nonce( $_REQUEST['nonce'], $action . "_" . $_REQUEST['id_number']) && !empty($_REQUEST['id_number'])) {
            $response['status'] = "NONCE_ERROR";
        }else{
            if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                $response = $callback();
                $response['status'] = "SUCCESS";
            }else {
                $response['status'] = "HTTP_REFERER_ERROR";
            }
            $response['nonce'] = wp_create_nonce($action . "_" . $_REQUEST['id_number']);
        }
        header("Content-Type: application/json; charset=UTF-8");
        echo json_encode($response);
        die();
    }

    public function qrp_view() {
        $this->generic_responder(__FUNCTION__, function (){
            $qrp_entries = new QRPEntriesManager($_REQUEST['cf_id']);
            $response['fields'] = $qrp_entries->getUserEntryFields($_REQUEST['id_number']);
            return $response;
        });
    }

    public function qrp_approve() {
        $this->generic_responder(__FUNCTION__, function (){
            $qrp_entries = new QRPEntriesManager($_REQUEST['cf_id']);
            $response['message'] = $qrp_entries->approve($_REQUEST['id_number']);
            return $response;
        });
    }

    public function qrp_revoke() {
        $this->generic_responder(__FUNCTION__, function (){
            $qrp_entries = new QRPEntriesManager($_REQUEST['cf_id']);
            $response['message'] = $qrp_entries->revoke($_REQUEST['id_number']);
            return $response;
        });
    }

    public function qrp_update_link() {
        $this->generic_responder(__FUNCTION__, function (){
            $qrp_entries = new QRPEntriesManager($_REQUEST['cf_id']);
            if(empty($_REQUEST['param'])){
                $response['message'] = $qrp_entries->getUserLink($_REQUEST['id_number']);
            }else{
                $response['message'] = $qrp_entries->updateUserLink($_REQUEST['id_number'], $_REQUEST['param']);
            }
            return $response;
        });
    }

    public function qrp_update_email() {
        $this->generic_responder(__FUNCTION__, function (){
            $qrp_entries = new QRPEntriesManager($_REQUEST['cf_id']);
            if(empty($_REQUEST['param'])){
                $response['message'] = $qrp_entries->getEmailAddress($_REQUEST['id_number']);
            }else{
                $response['message'] = $qrp_entries->updateEmail($_REQUEST['id_number'], $_REQUEST['param']);
            }
            return $response;
        });
    }

    public function qrp_send_email() {
        $this->generic_responder(__FUNCTION__, function (){
            $qrp_entries = new QRPEntriesManager($_REQUEST['cf_id']);

            if(empty($_REQUEST['param'])){
                $response['message'] = $qrp_entries->getEmailAddress($_REQUEST['id_number']);
            }else{
                $response['message'] = $qrp_entries->send($_REQUEST['id_number']);
            }
            $response['status'] = "SUCCESS";
            return $response;
        });
    }

    public function qrp_form_filters() {
        $this->generic_responder(__FUNCTION__, function (){
            if(empty($_REQUEST['param'])){
                $form = Caldera_Forms_Forms::get_form( $_REQUEST['cf_id'] );

                $parsed_data = '';
                $raw_data = get_option( WP_QRP_OPTION_PREFIX . "form_filters_config_" . $_REQUEST['cf_id'] );
                parse_str($raw_data, $parsed_data);

                $response['fields'] = json_encode($form['fields']);
                $response['data'] = json_encode($parsed_data);
                $response['status'] = "SUCCESS";
            }else{
                $param_values = $_REQUEST['param'];
                parse_str($param_values, $parsed_param);

                if(!empty($parsed_param['qrp_group_condition'])){
                    $result = update_option(WP_QRP_OPTION_PREFIX . "form_filters_config_" . $_REQUEST['cf_id'], $_REQUEST['param']);
                    if($result){
                        $response['status'] = "SUCCESS";
                    }else{
                        $response['status'] = "ERROR";
                    }
                } else {
                    $response['status'] = "ERROR";
                }
            }

            return $response;
        });
    }

    public function qrp_form_response(){
        $this->generic_responder(__FUNCTION__, function (){
            if(!empty($_REQUEST['param'])){
                switch ($_REQUEST['param']){
                    case 'get_values':
                        $parsed_param = '';
                        $form = Caldera_Forms_Forms::get_form( $_REQUEST['cf_id'] );
                        $raw_data = get_option( WP_QRP_OPTION_PREFIX . "form_response_config_" . $_REQUEST['cf_id']);

                        parse_str($raw_data, $parsed_param);

                        if(!empty($parsed_param['response_settings'])){
                            $response['data'] = json_encode($parsed_param);
                        }else{
                            $response['data'] = "empty";
                        }
                        $response['fields'] = json_encode($form['fields']);
                        $response['status'] = "SUCCESS";
                        break;
                    case 'set_values':
                        $param_values = $_REQUEST['data'];
                        parse_str($param_values, $parsed_param);

                        if(!empty($parsed_param['response_settings'])){
                            $result = update_option(WP_QRP_OPTION_PREFIX . "form_response_config_" . $_REQUEST['cf_id'], $_REQUEST['data']);
                            if($result){
                                $response['status'] = "SUCCESS";
                            }else{
                                $response['status'] = "ERROR";
                            }
                        }else{
                            $response['status'] = "ERROR";
                        }
                        break;
                    default:
                        $response['status'] = "ERROR";
                }
            }else{
                $response['status'] = "ERROR";
            }

            return $response;
        });
    }

    public function resend_email_public(){
        if ( !wp_verify_nonce( $_REQUEST['nonce'], "qrp_eid_" . $_REQUEST['id_number']) && !empty($_REQUEST['id_number'])) {
            $response['status'] = "NONCE_ERROR";
        }else{
            $qrp_entries = new QRPEntriesManager($_REQUEST['cf_id']);
            $response['message'] = $qrp_entries->send($_REQUEST['id_number'], $_REQUEST['email']);
            $response['status'] = "SUCCESS";
        }
        header("Content-Type: application/json; charset=UTF-8");
        echo json_encode($response);
        wp_die();
    }
}