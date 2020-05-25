<?php
if (! defined( 'ABSPATH' ) ){
    exit;
}

global $current_user;
wp_get_current_user();

if(!empty($_REQUEST['qrp_submit'])){
    if ( !wp_verify_nonce( $_REQUEST['qrp_storage_nonce'], 'qrp_storage_settings_'.$current_user->user_login )) {
        echo QRPUtility::instance()->admin_notice( array(
            'type' => 'error',
            'message' => 'Failed to save changes'
        ) );
    }else{
        if(!empty($_REQUEST['qrp_is_storage_remote'])){
            update_option( WP_QRP_OPTION_PREFIX . "is_storage_remote'", $_REQUEST['qrp_is_storage_remote']);
        }
        if(!empty($_REQUEST['qrp_storage_remote_url'])) {
            update_option(WP_QRP_OPTION_PREFIX . "storage_remote_url'", $_REQUEST['qrp_storage_remote_url']);
        }
        echo QRPUtility::instance()->admin_notice( array(
            'type' => 'update',
            'message' => 'Changes successfully saved'
        ) );
    }
}

$is_remote_storage = get_option( WP_QRP_OPTION_PREFIX . "is_storage_remote'");
?>
<form action="<?php echo esc_url( admin_url('admin.php?page=qrp-settings&tab=storage') ); ?>" method="post">
    <input type="hidden" value="<?php echo wp_create_nonce('qrp_storage_settings_'.$current_user->user_login )?>" name="qrp_storage_nonce"/>
    <label for="qrp_is_storage_remote">Enable Remote Photo Storage</label><br/>
    <input type="checkbox" name="qrp_is_storage_remote" id="qrp_is_storage_remote" <?php echo ($is_remote_storage == 'on')? 'checked':'' ?>/><br/>
    <?php if($is_remote_storage == 'on'): ?>
    <label for="qrp_storage_remote_url">Remote Storage URL</label><br/>
    <input type="url" name="qrp_storage_remote_url" id="qrp_storage_remote_url" value="<?php echo get_option( WP_QRP_OPTION_PREFIX . "storage_remote_url'") ?>"/><br/>
    <?php endif; ?>
    <?php
        echo sprintf("<div class='qrp-storage-info'>Current Storage path: [%s]</div>", QRPUtility::instance()->get_current_storage());
    ?>
    <input type="submit" class="button" name="qrp_submit" value="Save Settings"/>
</form>