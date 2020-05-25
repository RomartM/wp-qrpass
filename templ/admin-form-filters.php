<?php
if (! defined( 'ABSPATH' ) ){
    exit;
}

$link_forms_data = get_option( WP_QRP_OPTION_PREFIX. "link_forms"); ?>
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
        <input type="text" class="qrp_group_name" placeholder="Group Name" required/>
        <button class="qrp_trigger_action button" data-action="add-group">Add Group Condition</button>
        <form class="qrp-form-data">
            <div class="qrp_group_wrapper"></div>
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
