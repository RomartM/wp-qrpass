<?php
if (! defined( 'ABSPATH' ) ){
    exit;
}

global $current_user;
wp_get_current_user();

if(!empty($_REQUEST['qrp_submit'])){
    if ( !wp_verify_nonce( $_REQUEST['qrp_security_nonce'], 'qrp_security_settings_'.$current_user->user_login )) {
        echo QRPUtility::instance()->admin_notice( array(
            'type' => 'error',
            'message' => 'Failed to save changes'
        ) );
    }else{
        if(!empty($_REQUEST['qrp_security_password'])){
            $qrp_crypto = new QRPCrypto();
            $qrp_crypto->setEncodedKey($_REQUEST['qrp_security_password']);
            echo QRPUtility::instance()->admin_notice( array(
                'type' => 'update',
                'message' => 'Changes successfully saved'
            ) );
        } else {
            echo QRPUtility::instance()->admin_notice( array(
                'type' => 'error',
                'message' => 'Password Empty'
            ) );
        }

    }
}


$security_password = "***********";
?>
<form action="<?php echo esc_url( admin_url('admin.php?page=qrp-settings&tab=security') ); ?>" method="post">
    <label for="qrp_security_password">QR Pass Password (Used to encrypt QR code data)</label><br/>
    <input type="hidden" value="<?php echo wp_create_nonce('qrp_security_settings_'.$current_user->user_login )?>" name="qrp_security_nonce"/>
    <input type="password" name="qrp_security_password" id="qrp_security_password" value="<?php echo $security_password ?>" placeholder="Hidden"/><br/>
    <?php
    if(!empty(get_option( WP_QRP_OPTION_PREFIX . "security_update_time" ))){
        $security_info = get_option( WP_QRP_OPTION_PREFIX . "security_update_time" );
        echo sprintf("<div class='qrp-security-info'>A password has been set. Last updated on %s by (%s).</div>", $security_info['date'], $security_info['user']);
    }
    ?>
    <input type="submit" class="button" name="qrp_submit" value="Save Settings"/>
</form>
