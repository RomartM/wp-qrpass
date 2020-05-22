<?php

add_filter('caldera_forms_ajax_return', function($out, $form){
    $form = Caldera_Forms_Forms::get_form( $form['ID'] );

    $qrp_result = new QRPResultGenerator($out['data']['cf_id'], $form);

    $out["html"] .= $qrp_result->resultHTML();

    // Verify User Filters
    $form_filters_config = get_option('qrp_form_filters_config_'. $form['ID'] );
    if(!empty($form_filters_config)){
        $response_filter = new QRPResponseFilter($form['ID'], $out['data']['cf_id']);
        $response_filter->process();
    }

	return $out;
}, 10, 3);

add_filter( 'caldera_forms_summary_magic_fields', function( $fields, $form ) {

    $parsed_param = '';
    $raw_data = get_option('qrp_form_response_config_'. $form['ID']);
    parse_str($raw_data, $parsed_param);

    if(!empty($parsed_param)){
        $settings_data = $parsed_param['response_settings'];
        $new_fields = [];
        if($settings_data['is_display_response'] == 'on'){
            forEach($settings_data['display_fields']['value'] as $id => $item){
                array_push($new_fields, $fields[$id]);
            }
        }
        $fields = $new_fields;
    }

    return $fields;

}, 10, 2 );

add_filter( 'caldera_forms_mailer', function( $mail, $data, $form ) {

    $entry_manager = new QRPEntriesManager($form['ID']);
    $id_number_id = Caldera_Forms_Field_Util::get_field_by_slug('id_number', $form);
    $email_address_id = Caldera_Forms_Field_Util::get_field_by_slug('email_address', $form);

    if(empty($data[$id_number_id['ID']])){
        $id_number = $data['_entry_id'];
    } else {
        $id_number = $data[$id_number_id['ID']];
    }

    $email_address = $data[$email_address_id['ID']];
    $entry_manager->send($id_number, $email_address);

}, 10, 3 );

add_action( 'caldera_forms_submit_start', function( array $form, $process_id ) {
		$styles = "<style>.caldera_forms_form {display: none;} .alert.alert-error li {margin: 0 !important;}.alert.alert-error ul {list-style: none !important;margin: 0 !important;}</style>";


		// TODO: Duplicate Functionality Implement Here
		$privileges = "";

		if(!empty(wp_get_current_user()->roles)){
        	$privileges = wp_get_current_user()->roles[0];
        }

		$message = "";

        //echo "<div class=\"alert alert-error\" style=\"padding: 25px 5px;font-size: 15px;margin-bottom: 100px;\">Your response was not recorded due to duplicate response. <br\/>" . $message . "</div>" . $styles;

		return;
	}, 10, 2 );

