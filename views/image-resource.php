<?php

function qrp_image_resource(){
    if ( is_home() || is_front_page() ) {
        if(!empty($_GET['photo_id'])){
            $validator = new QRPHashValidator($_GET['photo_id']);
            $file_link = sprintf("%s/%s.JPG", IMAGE_STORAGE_PATH, strtoupper($validator->decryptHash()['content']));
            if(is_file($file_link)) {
                getImage($file_link);
            }else{
                getImage(FALLBACK_IMAGE);
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
        getImage(FALLBACK_IMAGE);
    }
}

add_filter ( 'wp_enqueue_scripts' , 'qrp_image_resource' );