<?php
if (! defined( 'ABSPATH' ) ){
    exit;
}

function qrp_image_resource(){
    if ( is_home() || is_front_page() ) {
        if(!empty($_GET['photo_id'])){
            $validator = new QRPHashValidator($_GET['photo_id']);
            $hash = $validator->decryptHash();
            $id_number = $hash['content'][0];
            $form_id = $hash['content'][1];

            $is_remote = get_option( WP_QRP_OPTION_PREFIX . "is_storage_remote'");
            if(!empty($is_remote)){
                $storage_path = get_option( WP_QRP_OPTION_PREFIX . "storage_remote_url'") . '/';
            }else{
                $storage_path = WP_QRP_STORAGE_PATH . sanitize_title(WP_QRP_OPTION_PREFIX . $form_id ) . '/';
            }

            $file_link = sprintf("%s/%s.JPG", $storage_path, strtoupper($id_number));
            if(is_file($file_link)) {
                getImage($file_link);
            }else{
                getImage(WP_QRP_ROOT . '/assets/image/user.png');
            }
        }
    }
}

function getImage($file_link){
    if(false !== ($data = file_get_contents($file_link))){
        ob_clean();
        header('Content-type: '. wp_get_image_mime($file_link));
        readfile($file_link);
    }else{
        getImage(WP_QRP_ROOT . '/assets/image/user.png');
    }
}

add_filter ( 'wp_enqueue_scripts' , 'qrp_image_resource' );