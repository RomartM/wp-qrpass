<?php

if (! defined( 'ABSPATH' ) ){
    exit;
}

class QRPResultGenerator
{

    private $form_id;
    private $form_entry_id;
    private $form_instance;
    private $css_styles;

    /**
     * QRPActivityCollector constructor.
     *
     * @param $entry_id
     * @param $form
     * @param null $css_styles
     */
    public function __construct($entry_id, $form, $css_styles=null)
    {
        $this->form_id = $form['ID'];
        $this->form_entry_id = $entry_id;
        $this->form_instance = Caldera_Forms::get_form($this->form_id);;

        if(empty($css_styles)){
            $this->css_styles = array('#dff0d8', '#a3d48e', '#3c763d');
        }else {
            $this->css_styles = $css_styles;
        }
    }

    public function getIDNumber(){
        $entry = new Caldera_Forms_Entry( $this->form_instance, $this->form_entry_id );
        $field = Caldera_Forms_Field_Util::get_field_by_slug('id_number', $this->form_instance);

        $id_number_initial = $entry->get_field($field['ID']);

        if(!empty($id_number_initial)){
            $id_number = $id_number_initial->get_value();
        }else{
            $id_number = $entry->get_entry_id();
        }
        return $id_number;
    }

    public function resultHTML($header = null, $footer = null){
        $entry = new Caldera_Forms_Entry( $this->form_instance, $this->form_entry_id );
        $field = Caldera_Forms_Field_Util::get_field_by_slug('id_number', $this->form_instance);

        $parsed_param = '';
        $raw_data = get_option( WP_QRP_OPTION_PREFIX . "form_response_config_". $entry->get_form_id());
        parse_str($raw_data, $parsed_param);

        $id_number_initial = $entry->get_field($field['ID']);

        if(!empty($id_number_initial)){
            $id_number = $id_number_initial->get_value();
        }else{
            $id_number = $entry->get_entry_id();
        }

        $html = "";

        $html .= "<div class=\"qrpass-container\" style=\"font-family:-apple-system,BlinkMacSystemFont,'Helvetica Neue',Helvetica,sans-serif;\">
        <div class=\"alert alert-success\" style=\"background-color:". $this->css_styles[0] .";border-color:". $this->css_styles[1] .";color:". $this->css_styles[2] .";padding: 5% 10%;text-align:center;\" >";

        if(!empty($header)){
            $html .= sprintf("<div style=\"max-width:500px;margin:auto;padding:25px;font-size:14px;\">%s</div>", $header);
        }

        if(!empty($parsed_param)){
            $settings_data = $parsed_param['response_settings'];

            if($settings_data['is_display_response'] == 'on'){
                $response = "<ul>";

                forEach($settings_data['display_fields']['value'] as $id => $item){
                    $answer = "";
                    if(!empty($entry->get_field($id))){
                        $answer = $entry->get_field($id)->get_value();
                    }
                    if(is_array($answer)){
                        $final_answer = implode(", ", $answer);
                    }else{
                        $final_answer = $answer;
                    }
                    if(!empty($answer)){
                        $response .= sprintf("<li style=\"font-size:14px;line-height:20px;margin-bottom:12px; text-align: left;\"><span>%s</span>: <strong>%s</strong></li>", $item, $final_answer );
                    }
                }

                $response .= "</ul>";
                $html .= "<div class=\"response-container\" style=\"background-color:#fff;padding-top:15px;padding-bottom:15px;padding-right:15px;padding-left:15px;border-radius:10px;max-width:600px;margin:auto;\">
                    <div class=\"container-title\" style=\"margin-bottom:15px;font-size:20px;\">Your responses</div>". $response ."</div>";
            }

            if($settings_data['is_display_qr'] == 'on'){
                $qr_gen = new QRPGenerator($id_number, $this->form_id);
                $url = $qr_gen->getResourceURL();
                $image = file_get_contents($url);
                $html .= "<div class=\"qrpass-container\" style=\"font-family:-apple-system,BlinkMacSystemFont,'Helvetica Neue',Helvetica,sans-serif;background-color:#fff;background-image:none;background-repeat:repeat;background-position:top left;background-attachment:scroll;border-radius:15px;padding-top:15px;padding-bottom:15px;padding-right:15px;padding-left:15px;max-width:600px;margin:10px auto;\">
                <div class=\"container-title\" style=\"margin-bottom:15px;font-size:20px;\">Your QR Pass</div>
                <a title=\"Click to Download\" href=\"data:image/png;base64,".base64_encode($image)."\" download>
                <img src=\"".$url."\" style=\"display:block!important;margin-left:auto!important;margin-right:auto!important;max-width:200px;\" id=\"qrpass\"/></a>
                </div>";
            }

            if($settings_data['is_display_time'] == 'on' && empty($header)){
                $date_format = get_option('date_format');
                $time_format = get_option('time_format');
                $date = date("{$date_format} {$time_format}", strtotime($entry->get_entry()->{'datestamp'}));
                $html .= "<div class=\"time-container\" style=\"font-size:13px;padding-top:10px;padding-bottom:10px;padding-right:10px;padding-left:10px;\">Completed ". strtoupper($date) ."</div>";
            }

            if($settings_data['is_resend_response'] == 'on' && empty($header)){
                $html .= "<div class=\"email-container\" style=\"font-size:13px;padding-top:10px;padding-bottom:10px;padding-right:10px;padding-left:10px;\">Did not received the email response? <a href=\"javascript:void(0)\" onclick=\"window.qrp_resend_email(this)\" data-id=\"" . $id_number . "\" data-cfid=\"" . $this->form_id . "\" data-nonce=\"". wp_create_nonce("qrp_eid_" . $id_number) ."\" class=\"toggle_email_modal\">Click Here</a> </div>";
            }
        }

        if(!empty($footer)){
            $html .= sprintf("<div>%s</div>", $footer);
        }

        $html .= "</div></div>";

        return $html;
    }
}