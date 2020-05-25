<?php
if (! defined( 'ABSPATH' ) ){
    exit;
}

$link_forms_data = get_option( WP_QRP_OPTION_PREFIX . "link_forms"); ?>
<label for="form-list">Select Form</label>
<select class="form-list" id="form-list"><?php
    foreach ($link_forms_data as $item){
        ?>
        <option value="<?php echo $item['cf_id'] ?>"><?php echo $item['group'] ?></option>
        <?php
    }?>
</select>
<div class="qrp_form_view">
    <div class="qrp-center loading hidden">
        <h2>Loading...</h2>
        <div class="qrp-spinner"></div>
    </div>
    <div class="success hidden">
        <form class="qrp-form-data">
            <label for="qrp_attach_qr">Attach QR Code to email</label>
            <input type="checkbox" name="response_settings[is_attach_qr]" id="qrp_attach_qr"/><br/>
            <label for="qrp_display_qr">Display QR Code on Result Page</label>
            <input type="checkbox" name="response_settings[is_display_qr]" id="qrp_display_qr"/><br/>
            <label for="qrp_display_time">Display Time of Completion</label>
            <input type="checkbox" name="response_settings[is_display_time]" id="qrp_display_time"/><br/>
            <label for="qrp_display_response">Display Response</label>
            <input type="checkbox" name="response_settings[is_display_response]" id="qrp_display_response"/><br/>
            <label for="qrp_resend_response">Allow Resend Email</label>
            <input type="checkbox" name="response_settings[is_resend_response]" id="qrp_resend_response"/><br/>
            <label for="qrp_duplicate_response">Allow Duplicate Entry</label>
            <input type="checkbox" name="response_settings[is_duplicate_response]" id="qrp_duplicate_response"/><br/>
            <label for="qrp_duplicate_response">Duplicate Response Message:</label>
            <textarea name="response_settings[duplicate_response_message]" id="qrp_duplicate_response_message" ></textarea><br/>
            <div class="response_fields hidden">
                <label for="qrp_display_fields">Select Fields to display</label>
                <div id="qrp_display_fields"></div>
            </div>
        </form>
        <button class="qrp-form-data-save button">Save</button>
    </div>
</div>
<div id="qrp-dialog" class="hidden" style="max-width:200px">
    <div class="qrp_process_view qrp-center hidden">
        <div class="loading hidden">
            <h2>Loading...</h2>
            <div class="qrp-spinner"></div>
        </div>
        <div class="success hidden">
            <h2>Loading...</h2>
        </div>
    </div>
</div>
</div>
