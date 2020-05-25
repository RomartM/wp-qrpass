<?php
/**
 * QRPHashValidator
 *
 * Validate QR Hash Content
 *
 * @since 2.0.0
 */

if (! defined( 'ABSPATH' ) ){
    exit;
}

class QRPHashValidator extends QRPCrypto
{
    private $data_hash;
    private $photo_cloud_storage_url;
    private $data_table;

    /**
     * ValidateHash constructor.
     *
     * @since 2.0.0
     * @var string $hash A hash from QR Code
     */
    function __construct($hash){
        parent::__construct();
        $this->data_table = new QRPDataTable();
        if($this->checkIfRefID($hash)){
            $id_number = $this->data_table->getUserDataByRefID($hash)['id_number'];
            $form_id = $this->data_table->getUserDataByRefID($hash)['form_id'];
            if(!empty($id_number) && !empty($form_id)){
                $v_gen = new QRPGenerator($id_number, $form_id);
                $this->data_hash = $v_gen->getHash();
            }else{
                $this->data_hash = $hash;
            }
        }else{
            $this->data_hash = $hash;
        }
    }

    private function checkIfRefID($hash){
        preg_match('/R-\d+$/', $hash, $matches, PREG_OFFSET_CAPTURE);
        return !empty($matches);
    }

    /**
     * Decrypt Hash.
     *
     * @since 2.0.0
     * @var string $hash A hash from QR Code
     * @return array
     */
    public function decryptHash(){
        $data_result = Array();
        try {
            list($data, $iv) = json_decode(base64_decode($this->data_hash));
            $data_content = $this->decrypt($data, $iv);
            if(empty(json_decode($data_content['content'])->{'user-id'}) ||
               empty(json_decode($data_content['content'])->{'form-id'})){
                throw new Exception('Can\' retrieve user ID.');
            }
            $data_id_no = json_decode($data_content['content'])->{'user-id'};
            $data_id_fo = json_decode($data_content['content'])->{'form-id'};
            $data_result['status'] = $data_content['status'];
            $data_result['content'] = array(
                strlen($data_id_no) < 5 ? 'Empty' : $data_id_no,
                strlen($data_id_fo) < 5 ? 'Empty' : $data_id_fo
            );
        } catch (Exception $e) {
            $data_result['status'] = 'error';
            $data_result['content'] = $e->getMessage();
        }
        return $data_result;
    }

    /**
     * Get hash result as JSON response.
     *
     * @param bool $is_resource
     * @param string $resource_callback
     * @return string
     * @since 2.0.0
     */
    function getResponse($is_resource=false, $resource_callback=''){
        $hash = $this->decryptHash();
        $status = $hash['status'];
        $id_number = $hash['content'][0];
        $form_id = $hash['content'][1];

        $is_remote = get_option( WP_QRP_OPTION_PREFIX . "is_storage_remote'");
        if(!empty($is_remote)){
            $this->photo_cloud_storage_url = get_option( WP_QRP_OPTION_PREFIX . "storage_remote_url'") . '/';
        }else{
            $this->photo_cloud_storage_url = WP_QRP_STORAGE_PATH . sanitize_title(WP_QRP_OPTION_PREFIX . $form_id ) . '/';
        }

        if($status == 'success'){
            if(empty($id_number) || empty($form_id)){
                return $this->getResponseJSON(null, 401, "Pass not Valid");
            }else{
                // Get User Information
                $user_data = $this->data_table->getUserData($id_number);

                $payload['user-id'] = strtoupper($id_number);
                $payload['user-photo'] = get_site_url(null, '?photo_id=' .  base64_encode(json_encode($this->data_hash)) );
                $payload['user-name'] = (strlen($this->getName($user_data)) <= 4) ? 'No Data for Name' : $this->getName($user_data);

                if($is_resource){
                    $payload['user-meta'] = $resource_callback($form_id, $id_number);
                }

                return $this->getResponseJSON($payload, $this->getStatusCode($id_number), $this->getStatus($id_number));
            }
        } else {
            return $this->getResponseJSON(null, 403, "Pass not Valid");
        }
    }


    /**
     * Convert Data into JSON Response.
     *
     * @param $data
     * @param $code
     * @param $message
     * @return string
     * @since 2.0.0
     *
     */
    protected function getResponseJSON($data, $code, $message){
        $response = array();
        $response['data'] = $data;
        $response['code'] = $code;
        $response['message'] = $message;

        return json_encode($response);
    }

    /**
     * Get UserData
     *
     * @param $id_number
     * @return void
     * @since 2.0.0
     */
    protected function getUserData($id_number){
        return $this->data_table->getUserData($id_number);
    }

    /**
     * Get User Status
     *
     * @since 2.0.0
     * @var string $id_number User ID
     * @return string
     */
    public function getStatus($id_number){
        return $this->data_table->getUserData($id_number)['status'];
    }

    /**
     * Get User Status Code
     *
     * @since 2.0.0
     * @var string $id_number User ID
     * @return integer
     */
    public function getStatusCode($id_number){
        if($this->data_table->getUserData($id_number)['status'] == 'approve'){
            return 200;
        }else{
            return 403;
        }
    }

    /**
     * Format Name
     *
     * @since 2.0.0
     * @var string $str
     * @return string
     */
    protected function formatName($str){
        return str_replace("Ñ", "ñ", mb_convert_encoding(ucwords(strtolower($str)), 'cp1252', 'utf-8'));
    }

    /**
     * Get User Full Name
     *
     * @since 2.0.0
     * @var array $data UserData Array
     * @return string
     */
    protected function getName($data){
        return ($this->formatName($data['first_name'])." ". $this->formatName($data['middle_name']) . " " . $this->formatName($data['last_name']) . " " . $this->formatName($data["name_ext"]));
    }

    /**
     * Set Photo Resource URL
     *
     * @since 2.0.0
     * @var string $url Photo Resource URL
     */
    public function setPhotoResourceURL($url){
        $this->photo_cloud_storage_url = $url;
    }
}