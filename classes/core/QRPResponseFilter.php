<?php

if (! defined( 'ABSPATH' ) ){
    exit;
}

class QRPResponseFilter
{
    private $form;
    private $form_id;
    private $entry_id;

    public function __construct($form_id, $entry_id)
    {
        $this->form = Caldera_Forms_Forms::get_form( $form_id );
        $this->form_id = $form_id;
        $this->entry_id = $entry_id;
    }

    private function conditional_relation($relation, $initial_result, $score_value){
        $conditional_result = $initial_result >= $score_value;
        switch ($relation){
            case 'add':
                if($score_value == $conditional_result){
                    return $initial_result;
                }
                return 0;
            case 'and':
                if($score_value && $conditional_result){
                    return $initial_result;
                }
                return 0;
            case 'or':
                if($score_value || $conditional_result){
                    return $initial_result;
                }
                return 0;
            case 'not':
                if($score_value != $conditional_result){
                    return $initial_result;
                }
                return 0;
        }
    }

    private function condition_converter($operator, $value_config, $incremental_hit_value, $first_value, $second_value){

        $tmp = null;
        $hit_iterator = 0;

        if(is_array($first_value) && is_array($second_value)){ // Both values are array
            forEach($first_value as $f_value){
                forEach ($second_value as $s_value){
                    $tmp = $this->sub_condition_converter($operator, $f_value, $s_value, $value_config, $incremental_hit_value, $hit_iterator);
                    if(!empty($tmp)){
                        $hit_iterator = $tmp;
                    }
                }
            }
            return $hit_iterator;
        } elseif (is_array($first_value) && !is_array($second_value)){ // First Value is array only
            forEach($first_value as $f_value){
                $tmp = $this->sub_condition_converter($operator, $f_value, $second_value, $value_config, $incremental_hit_value, $hit_iterator);
                if(!empty($tmp)){
                    $hit_iterator = $tmp;
                }
            }
            return $hit_iterator;
        } elseif (!is_array($first_value) && is_array($second_value)){ // Second Value is array only
            forEach($second_value as $s_value){
                $tmp = $this->sub_condition_converter($operator, $first_value, $s_value, $value_config, $incremental_hit_value, $hit_iterator);
                if(!empty($tmp)){
                    $hit_iterator = $tmp;
                }
            }
            return $hit_iterator;
        }
    }

    private function sub_condition_converter($operator, $f_value, $s_value, $value_config, $incremental_hit_value, $hit_iterator){
        if($this->equality_operator($operator, $f_value, $s_value)){
            if($value_config == 'sum'){
                $hit_iterator += $incremental_hit_value;
            }elseif ($value_config == 'one'){
                $hit_iterator = $incremental_hit_value;
            }
            return $hit_iterator;
        }
        return null;
    }

    private function equality_operator($operator, $first_value = "", $second_value = ""){
        switch ($operator){
            case '==':
                return ($first_value == $second_value);
            case '>':
                return ($first_value > $second_value);
            case '<':
                return ($first_value < $second_value);
            case '>=':
                return ($first_value >= $second_value);
            case '<=':
                return ($first_value <= $second_value);
            default:
                return 0;
        }
    }

    public function process(){
        $parsed_data = '';
        $raw_data = get_option( WP_QRP_OPTION_PREFIX . "form_filters_config_" . $this->form_id );
        parse_str($raw_data, $parsed_data);

        $entry = new Caldera_Forms_Entry( $this->form, $this->entry_id );
        $entry_manager = new QRPEntriesManager($this->form_id);

        //print_r($form['fields']);
        forEach($parsed_data["qrp_group_condition"] as $item){
            $total = 0;
            forEach ($item['condition'] as $condition_items){
                if(!empty($entry->get_field($condition_items['field_id']))){
                    if( is_array($condition_items['value']) ){
                        $tmp = $this->condition_converter(
                            $condition_items['equality_operator'],
                            $condition_items['value_config'],
                            $condition_items['score_value'],
                            $entry->get_field($condition_items['field_id'])->{'value'},
                            $condition_items['value']
                        );
                        $total += $this->conditional_relation($condition_items['conditional_relation'], $tmp, $condition_items['score_value']);
                    }else {
                        $tmp = $this->condition_converter(
                            $condition_items['equality_operator'],
                            'sum',
                            $condition_items['score_value'],
                            $entry->get_field($condition_items['field_id'])->{'value'},
                            $condition_items['value']
                        );
                        $total += $this->conditional_relation($condition_items['conditional_relation'], $tmp, $condition_items['score_value']);
                    }
                } else {
                    $total = null;
                }
            }

            if($this->equality_operator($item['total_equality_operator'], $total, $item['total_score_value'])){ //bug for unknown entry id
                $m = Caldera_Forms_Field_Util::get_field_by_slug('id_number', $this->form)['ID'];
                $value = $entry->get_field($m);
                if(!empty($value)){
                    $id_number = $value->get_value();

                    $entry_manager->approve($id_number);


                    if($item['settings_config']['is_approve'] !== 'on'){
                        $entry_manager->revoke($id_number);
                    }

                    if($item['settings_config']['is_notify'] == 'on'){
                        $entry_manager->adminSend(
                            $id_number,
                            $item['settings_config']['email'],
                            $item['settings_config']['message'],
                            $is_attach_qr = $item['settings_config']['is_attach_qr'] == 'on');
                    }

                    $entry_manager->send($id_number); // Send Email
                }
            }
        }
    }

}