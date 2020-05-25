<?php

add_filter('caldera_forms_ajax_return', function($out, $form){
    $form = Caldera_Forms_Forms::get_form( $form['ID'] );

    $qrp_result = new QRPResultGenerator($out['data']['cf_id'], $form);

    $out["html"] .= $qrp_result->resultHTML();

    // Verify User Filters
    $form_filters_config = get_option( WP_QRP_OPTION_PREFIX . "form_filters_config_". $form['ID'] );
    if(!empty($form_filters_config)){
        $response_filter = new QRPResponseFilter($form['ID'], $out['data']['cf_id']);
        $response_filter->process();
    }

	return $out;
}, 10, 3);

add_filter( 'caldera_forms_summary_magic_fields', function( $fields, $form ) {

    $parsed_param = '';
    $raw_data = get_option( WP_QRP_OPTION_PREFIX . "form_response_config_". $form['ID']);
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

add_filter( 'caldera_forms_upload_directory', function( $dir, $field_id, $form_id ){

    $link_forms_data = get_option( WP_QRP_OPTION_PREFIX . "link_forms");
    if(!empty($link_forms_data)){
        foreach ($link_forms_data as $item){
            if( $form_id == $item["cf_id"] ){
                $dir = sanitize_title(WP_QRP_OPTION_PREFIX . $form_id );
            }
        }
    }

    return $dir;

}, 10, 3  );

add_action( 'caldera_forms_submit_start', function( array $form, $process_id ) {

        $error_message = "Your response was not recorded due to duplicate response.";
        $allow_duplicate = false;
        $raw_data = get_option( WP_QRP_OPTION_PREFIX . "form_response_config_" . $form['ID']);

        parse_str($raw_data, $parsed_param);

        if(!empty($parsed_param['response_settings'])){
            $settings = $parsed_param['response_settings'];
            if(!empty($settings['is_duplicate_response'])){
                $allow_duplicate = ($settings['is_duplicate_response'] == 'on');
            }
            if(!empty($settings['duplicate_response_message'])){
                $error_message = $settings['duplicate_response_message'];
            }
        }

        if($allow_duplicate){
            return;
        }

		$styles = "<style>.caldera_forms_form {display: none;} .alert.alert-error li {margin: 0 !important;}.alert.alert-error ul {list-style: none !important;margin: 0 !important;}</style>";
        $is_name = false;
		$id_number = Caldera_Forms_Field_Util::get_field_by_slug('id_number', $form);

		if(!empty($id_number['ID'])){
            $id_number = Caldera_Forms::get_field_data( $id_number['ID'], $form );
        }else{
		    $is_name = true;
            $first_name_meta = Caldera_Forms_Field_Util::get_field_by_slug('first_name', $form);
            $middle_name_meta = Caldera_Forms_Field_Util::get_field_by_slug('middle_name', $form);
            $last_name_meta = Caldera_Forms_Field_Util::get_field_by_slug('last_name', $form);

            $first_name = Caldera_Forms::get_field_data($first_name_meta['ID'], $form);;
            $middle_name = Caldera_Forms::get_field_data($middle_name_meta['ID'], $form);;
            $last_name = Caldera_Forms::get_field_data($last_name_meta['ID'], $form);;

		    $id_number  = sprintf("%s %s %s", $first_name, $middle_name, $last_name );
        }

        if(checkUniqueEntry($form['ID'], $id_number, $is_name)){
            echo "<div class=\"alert alert-error\" style=\"padding: 25px 5px;font-size: 15px;margin-bottom: 100px;\">" . $error_message . "<br\/></div>" . $styles;
            die();
        }
        die("Passed");
		return;
	}, 10, 2 );



function checkUniqueEntry($form_id, $user_id, $is_name){
	$entry_manager = new QRPEntriesManager($form_id);

	if(!$is_name){
	    return ($entry_manager->search($user_id) !== false);
    }
	return ($entry_manager->searchByName($user_id) !== false);
}
