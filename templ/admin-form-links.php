<?php
if (! defined( 'ABSPATH' ) ){
    exit;
}

global $current_user;
wp_get_current_user();
if( $_SERVER['REQUEST_METHOD'] === 'POST' ){
    if ( !wp_verify_nonce( $_REQUEST['qrp_email_nonce'], 'qrp_email_settings_'.$current_user->user_login )) {
        echo QRPUtility::instance()->admin_notice( array(
            'type' => 'error',
            'message' => 'Failed to save changes'
        ) );
    }else{

        if(empty($_REQUEST['qrp_form_item'])){
            $form_item = "";
        }else{
            $form_item = $_REQUEST['qrp_form_item'];
        }

        update_option(WP_QRP_OPTION_PREFIX . "link_forms", $form_item );
        echo QRPUtility::instance()->admin_notice( array(
            'type' => 'update',
            'message' => 'Changes successfully saved'
        ) );
    }
}

$link_forms_data = get_option( WP_QRP_OPTION_PREFIX . "link_forms");
?>
<form action="<?php echo esc_url( admin_url('admin.php?page=qrp-settings&tab=link_forms') ); ?>" method="post">
    <input type="hidden" value="<?php echo wp_create_nonce('qrp_email_settings_'.$current_user->user_login )?>" name="qrp_email_nonce"/>
    <div>
        <a href="javascript:void(0);" class="add_button button" title="Add field">Link New Form</a>
        <input type="submit" class="button" name="qrp_form_link_submit" value="Save"/>
    </div>
    <div class="field_wrapper">
        <?php
        if(empty($link_forms_data)){
            ?>
            <div>No Links Found.</div>
            <?php
        }else{
            $index = 0;
            foreach($link_forms_data as $item){
                ?>
                <div>
                    <input type="text" name="qrp_form_item[<?php echo $index; ?>][cf_id]" value="<?php echo $item["cf_id"]; ?>" placeholder="Caldera Form ID" required/>
                    <input type="text" name="qrp_form_item[<?php echo $index; ?>][group]" value="<?php echo $item["group"]; ?>" placeholder="Form Group" required/><a href="javascript:void(0);" class="remove_button button">Unlink Form</a>
                </div>
                <?php
                $index++;
            }
        }
        ?>
    </div>
</form>