<?php
global $current_user;
wp_get_current_user();

if(!empty($_REQUEST['qrp_submit'])){
    if ( !wp_verify_nonce( $_REQUEST['qrp_server_nonce'], 'qrp_server_'.$current_user->user_login )) {
        echo QRPUtility::instance()->admin_notice( array(
            'type' => 'error',
            'message' => 'Failed to save changes'
        ) );
    }else{
        update_option( WP_QRP_OPTION_PREFIX . "server_is_validation", empty($_REQUEST['qrp_server_is_validation'])? '' : $_REQUEST['qrp_server_is_validation']);
        update_option( WP_QRP_OPTION_PREFIX . "server_is_resource", empty($_REQUEST['qrp_server_is_resource'])? '' : $_REQUEST['qrp_server_is_resource']);
        echo QRPUtility::instance()->admin_notice( array(
            'type' => 'update',
            'message' => 'Changes successfully saved'
        ) );
    }
}

$is_validation = get_option( WP_QRP_OPTION_PREFIX . "server_is_validation");
$is_resource = get_option( WP_QRP_OPTION_PREFIX . "server_is_resource");

?>
<form action="<?php echo esc_url( admin_url('admin.php?page=qrp-settings&tab=servers') ); ?>" method="post">
    <input type="hidden" value="<?php echo wp_create_nonce('qrp_server_'.$current_user->user_login )?>" name="qrp_server_nonce"/>
    <label for="qrp_server_is_validation">Enable Validation:</label>
    <input type="checkbox" name="qrp_server_is_validation" id="qrp_server_is_validation" <?php echo ($is_validation == 'on')? 'checked':'' ?>/><br/>
    <?php if($is_validation == 'on'):?>
        <label for="qrp_server_is_validation_url">Validation URL:</label>
        <input type="text" name="qrp_server_is_validation_url" id="qrp_server_is_validation_url" value="<?php echo get_site_url('', '?action=validation'); ?>" readonly/><br/>
        <label for="qrp_server_is_validation_shortcode">WP Shortcode (Embed to any page to turn into a validation page):</label>
        <input type="text" name="qrp_server_is_validation_shortcode" id="qrp_server_is_validation_shortcode" value="[qrp-receiver action='validate']" readonly/><br/>
    <?php endif; ?>
    <label for="qrp_server_is_resource">Enable Get Resource</label>
    <input type="checkbox" name="qrp_server_is_resource" id="qrp_server_is_resource" <?php echo ($is_resource == 'on')? 'checked':'' ?>/><br/>
    <?php if($is_resource == 'on'): ?>
    <label for="qrp_server_is_resource_url">Resource URL:</label>
    <input type="text" name="qrp_server_is_resource_url" id="qrp_server_is_resource_url" value="<?php echo get_site_url('', '?action=resource'); ?>" readonly/><br/>
        <label for="qrp_server_is_resource_shortcode">WP Shortcode (Embed to any page to turn into a resource page):</label>
        <input type="text" name="qrp_server_is_resource_shortcode" id="qrp_server_is_resource_shortcode" value="[qrp-receiver action='resource']" readonly/><br/>
    <?php endif; ?>
    <input type="submit" class="button" name="qrp_submit" value="Save Settings"/>
</form>
