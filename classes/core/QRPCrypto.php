<?php

if (! defined( 'ABSPATH' ) ){
    exit;
}

/**
 * QRPCrypto - PHP AES-256 Crypto.
 *
 * Encrypt and Decrypt string with secret key.
 *
 * @since 2.0.0
 */
class QRPCrypto
{

    const DEFAULT_ENCODED_KEY = 'OHVrX1VOMVZpciRpdFlfVEAka0YwUmMzQXczJDBNMyE=';
    const END_OF_DATA_MARK = '<EOD>';
    protected $encryption_encoded_key;
    protected $iteration;

    /**
     * Crypto constructor.
     *
     * @param int $iteration
     * @param string $encoded_key OpenSSL AES-256-CBC Base64 string (To generate:echo secret | openssl aes-256-cbc -e -base64).
     * @since 2.0.0
     */
    public function __construct($encoded_key = '', $iteration=1000){
        if(empty($encoded_key)){
            if(!empty(get_option( WP_QRP_OPTION_PREFIX . "qrp_security_password" ))){
                $this->encryption_encoded_key = get_option( WP_QRP_OPTION_PREFIX . "qrp_security_password" );
            } else {
                $this->encryption_encoded_key = self::DEFAULT_ENCODED_KEY;
            }
        }else{
            $this->encryption_encoded_key = $encoded_key;
        }
        $this->iteration = $iteration;
    }

    /**
     * Encrypt String.
     *
     * @since 2.0.0
     * @var string $data String based data.
     * @return array(status, content).
     */
    protected function encrypt($data) {
        $status = Array();
        try {
            $data .= self::END_OF_DATA_MARK;

            $encryption_key = base64_decode($this->encryption_encoded_key);
            $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cfb'));
            $encrypted = openssl_encrypt($data, 'aes-256-cfb', $encryption_key, 1, $iv);

            $status['status'] = 'success';
            $status['content'] = Array(base64_encode($encrypted), base64_encode($iv));
        } catch (Exception $e) {
            $status['status'] = 'error';
            $status['content'] = $e->getMessage();
        }
        return $status;
    }

    /**
     * Decrypt Hash.
     *
     * @since 2.0.0
     * @var string $data String based hash data.
     * @var string $iv A non-NULL initialization vector .
     * @return array(status, content).
     */
    protected function decrypt($data, $iv) {
        $status = Array();
        try {
            $encryption_key = base64_decode($this->encryption_encoded_key);
            $encrypted_data = base64_decode($data);
            $iv = base64_decode($iv);

            $status['status'] = 'success';
            $status['content'] = explode(self::END_OF_DATA_MARK, openssl_decrypt($encrypted_data, 'aes-256-cfb', $encryption_key, 1, $iv), 2)[0];
        } catch (Exception $e) {
            $status['status'] = 'error';
            $status['content'] = $e->getMessage();
        }
        return $status;
    }

    /**
     * Set Encoded Key.
     *
     * @return bool
     * @var string $encoded_key OpenSSL AES-256-CBC Base64 string (To generate:echo secret | openssl aes-256-cbc -e -base64).
     * @since 2.0.0
     */
    public function setEncodedKey($pass_key){
        global $current_user;
        wp_get_current_user();
        $salt = openssl_random_pseudo_bytes(32);
        $encoded_key = json_encode(hash_pbkdf2("sha256", $pass_key, $salt, $this->iteration, 32));
        update_option( WP_QRP_OPTION_PREFIX . "security_update_time", array('user'=>$current_user->user_login, 'date'=>QRPUtility::instance()->get_date()));
        return update_option( WP_QRP_OPTION_PREFIX . "security_password", $encoded_key);
    }

}