<?php

function validateRequest($function_callback){
    if(!empty($_REQUEST['user-id'])){
        ob_get_clean();
        $function_callback($_REQUEST['user-id']);
    }else{
        wp_die('Invalid Action');
    }
}

function QRP_assets() {
    if(is_home() || is_front_page()){
        switch ($_REQUEST['action']){
            case 'validate':
                validateRequest(function ($hash_data){
                    $validation_instance = new QRPHashValidator($hash_data);
                    $data = $validation_instance->getResponse();
                    echo json_encode($data);
                    die();
                });
                break;
            case 'get-resource':
                validateRequest(function ($hash_data){
                    echo "Resource Here";
                    die();
                });
                break;
            default:
                wp_die('Invalid Action');
        }
    }
//    if(!empty($_GET['user_id'])){
//        	$hash_data =  json_decode(base64_decode($_GET['user_id']));
//        	ob_get_clean();
//        	header('Content-Type: application/json');
//        	$data = aes_decrypt_data($hash_data);
//            $data["data"]["user-name"] = str_replace("Ñ", "ñ",mb_convert_encoding(aes_decrypt_data($hash_data)["data"]["user-name"], 'cp1252', 'utf-8'));
//        	echo json_encode($data);
//        	die();
//        }
//    global $post;
//
//    if ( is_a( $post, 'WP_Post' ) &&  has_shortcode( $post->post_content, 'qrpass-ui')) {
//
//    	if(!empty($_GET['user_id'])){
//        	$hash_data =  json_decode(base64_decode($_GET['user_id']));
//        	ob_get_clean();
//        	header('Content-Type: application/json');
//        	$data = aes_decrypt_data($hash_data);
//            $data["data"]["user-name"] = str_replace("Ñ", "ñ",mb_convert_encoding(aes_decrypt_data($hash_data)["data"]["user-name"], 'cp1252', 'utf-8'));
//        	echo json_encode($data);
//        	die();
//        }
//
//    	// if(!empty($_GET['user_id_debug'])){
//    	// $hash_data =  json_decode(base64_decode($_GET['user_id_debug']));
//    	// ob_get_clean();
//    	// header('Content-Type: application/json');
//    	// $data = aes_decrypt_data($hash_data);
//    	// $data["data"]["user-name"] = str_replace("Ñ", "ñ",mb_convert_encoding(aes_decrypt_data($hash_data)["data"]["user-name"], 'cp1252', 'utf-8'));
//    	// print_r($data);
//    	// echo mb_convert_encoding(json_encode($data), 'cp1252', 'utf-8');
//    	// die();
//    	// }
//        // all styles
//       // wp_enqueue_style( 'bootstrap', QRP_PLUGIN_PATH . 'assets/css/lib/bootstrap.min.css', array(), 1 );
//        wp_enqueue_style( 'QRP-style', QRP_PLUGIN_PATH . 'assets/css/QRP-style.css', array(), 1 );
//
//        // all scripts
//        //wp_enqueue_script( 'bootstrap', QRP_PLUGIN_PATH . 'assets/js/lib/bootstrap.min.js', array('jquery'), '1', true );
//        wp_enqueue_script( 'html5qrcode', QRP_PLUGIN_PATH . 'assets/js/lib/html5qrcode.min.js', array('jquery'), '1', true );
//        wp_enqueue_script( 'QRP-script', QRP_PLUGIN_PATH . 'assets/js/QRP-script.js', array('jquery'), '1', true );
//		// Localize the enqueued JS script
//  		wp_localize_script( 'QRP-script', 'ajax_object',
//    		array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'place' => '' ) );
//    }
}

add_action('wp_enqueue_scripts', 'QRP_assets');