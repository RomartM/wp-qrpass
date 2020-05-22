<?php

/**
 * Class QRPActivityCollector
 */

if (! defined( 'ABSPATH' ) ){
    exit;
}

class QRPActivityCollector extends QRPDataTable
{

    private $form_id;
    private $form_entry_id;

    /**
     * QRPActivityCollector constructor.
     *
     * @param $entry_id
     * @param $form
     */
    public function __construct($entry_id, $form)
    {
        parent::__construct();
        $this->form_id = $form['ID'];
        $this->form_entry_id = $entry_id;
    }

    /**
     * logDuplicate
     *
     * @param $user_id
     */
    public function logDuplicate($user_id){
        $details['form_id'] = $this->form_id;
        $details['form_entry_id'] = $this->form_entry_id;
        $this->insertLog($user_id, __METHOD__, json_encode($details));
    }

    /**
     * logValidated
     *
     * @param $user_id
     */
    public function logValidated($user_id){
        $details['form_id'] = $this->form_id;
        $details['form_entry_id'] = $this->form_entry_id;
        $this->insertLog($user_id, __METHOD__, json_encode($details));
    }

    /**
     * logInvalidated
     *
     * @param $user_id
     */
    public function logInvalidated($user_id){
        $details['form_id'] = $this->form_id;
        $details['form_entry_id'] = $this->form_entry_id;
        $this->insertLog($user_id, __METHOD__, json_encode($details));
    }

    /**
     * getDuplicateLogs
     *
     * @return mixed
     */
    public function getDuplicateLogs(){
        return $this->getLog('logDuplicate');
    }

    /**
     * getValidatedLogs
     *
     * @return mixed
     */
    public function getValidatedLogs(){
        return $this->getLog('logValidated');
    }

    /**
     * getInvalidatedLogs
     *
     * @return mixed
     */
    public function getInvalidatedLogs(){
        return $this->getLog('logInvalidated');
    }

}