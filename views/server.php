<?php
if (! defined( 'ABSPATH' ) ){
    exit;
}

function validateRequest($function_callback){
    if(!empty($_REQUEST['user-id'])){
        $function_callback($_REQUEST['user-id']);
    }else{
        wp_die('Invalid Action');
    }
}

function do_client_action($action){
    ob_get_clean();
    header('Content-Type: application/json');

    $is_validation = get_option( WP_QRP_OPTION_PREFIX . "server_is_validation");
    $is_resource = get_option( WP_QRP_OPTION_PREFIX . "server_is_resource");

    $is_validation = empty($is_validation) ? '' : $is_validation;
    $is_resource = empty($is_resource) ? '' : $is_resource;

    switch ($action) {
        case 'validate':
            if($is_validation == 'on'){
                validateRequest(function ($hash_data) {
                    $validation_instance = new QRPHashValidator($hash_data);
                    $data = $validation_instance->getResponse();
                    echo json_encode($data);
                    die();
                });
            }
            break;
        case 'resource':
            if($is_resource == 'on'){
                validateRequest(function ($hash_data) {
                    $validation_instance = new QRPHashValidator($hash_data);
                    $data = $validation_instance->getResponse(true, function($form_id, $id_number){
                        $entries = new QRPEntriesManager($form_id);
                        return $entries->getUserEntryFields($id_number, false);
                    });
                    echo json_encode($data);
                    die();
                });
            }
            break;
        default:
            wp_die('Invalid Action');
    }
}

function qrp_default_receiver()
{
    global $post;

    if (is_home() || is_front_page() && isset($_REQUEST['action'])) {
        do_client_action($_REQUEST['action']);
    }


    if(is_a( $post, 'WP_Post' ) &&  has_shortcode( $post->post_content, 'qrp-receiver')){
        if (isset($_REQUEST['user-id'])) {
            $action = 'validate';

            if(!empty(attr_has_shortcode( $post->post_content, 'qrp-receiver')['action'])){
                $action = attr_has_shortcode( $post->post_content, 'qrp-receiver')['action'];
            }
            do_client_action($action);
        }
    }
}

function attr_has_shortcode( $content, $tag ) {
    if ( false === strpos( $content, '[' ) ) {
        return false;
    }

    $result = array();

    if ( shortcode_exists( $tag ) ) {
        preg_match_all( '/' . get_shortcode_regex() . '/', $content, $matches, PREG_SET_ORDER );
        if ( empty( $matches ) ) {
            return false;
        }

        foreach ( $matches as $shortcode ) {
            if ( $tag === $shortcode[2] ) {
                $data = explode("'", $shortcode[3]);
                $result[str_replace(" ", "", str_replace("=", "", $data[0]))] = $data[1];
            } elseif ( ! empty( $shortcode[5] ) && has_shortcode( $shortcode[5], $tag ) ) {
                $data = explode("'", $shortcode[3]);
                $result[str_replace(" ", "", str_replace("=", "", $data[0]))] = $data[1];
            }
        }
    }
    return $result;
}

function qrp_receiver( $overrides_attr ){

    shortcode_atts(
        array(
            'action' => 'validate',
        ),
        $overrides_attr
    );
}

add_action('wp_enqueue_scripts', 'qrp_default_receiver');

add_shortcode( 'qrp-receiver', 'qrp_receiver' );