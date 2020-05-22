<?php

/**
 * QRPGenerator
 *
 * Generate encrypted QR code with Google Chart API
 *
 * @since 2.0.0
 */
class QRPGenerator extends QRPCrypto
{
    private $data, $prefix, $image_size;

    /**
     * GenerateQRCode constructor.
     *
     * @since 2.0.0
     * @var string $data A data to be encrypted and converted to QR Code
     */
    function __construct($data) {
        parent::__construct();
        $this->data = $data;
        $this->prefix = 'qr-pass';
        $this->image_size = 350;
    }

    /**
     * Get QR Code URL
     *
     * @since 2.0.0
     * @var string $data Encrypted base64 data
     * @return string QR Code URL
     */
    private function getURL($data){
        return 'https://chart.googleapis.com/chart?chs='. $this->image_size .'x'. $this->image_size .'&cht=qr&chl=' . $data;
    }

    /**
     * Get Data encrypted base64 data
     *
     * @since 2.0.0
     * @return string Encrypted base64 data
     */
    function getHash(){
        $data_payload["user-id"] = $this->data;
        return base64_encode(json_encode($this->encrypt(json_encode($data_payload))['content']));
    }

    /**
     * Get QR Code URL with supplied data
     *
     * @since 2.0.0
     * @return string QR Code URL
     */
    function getResourceURL(){
        return $this->getURL($this->getHash());
    }

    /**
     * Get QR image temporary path
     *
     * @since 2.0.0
     * @return string QR Code Path
     */
    function getTempPath(){
        $qr_img = sys_get_temp_dir(). '/' . $this->prefix . '-' . $this->data . '.png';
        file_put_contents( $qr_img, file_get_contents( $this->getResourceURL() ) );

        return $qr_img;
    }

    /**
     * Set QR file image prefix
     *
     * @param $file_prefix
     * @since 2.0.0
     */
    function setPrefix($file_prefix){
        $this->prefix = $file_prefix;
    }

    /**
     * Set QR image dimension size
     *
     * @param $size
     * @since 2.0.0
     */
    function setQRDimensionSize($size){
        $this->image_size = $size;
    }

}