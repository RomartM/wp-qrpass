<?php

if (! defined( 'ABSPATH' ) ){
    exit;
}

global $current_user;
wp_get_current_user();

if(!empty($_REQUEST['qrp_submit'])){
    if ( !wp_verify_nonce( $_REQUEST['qrp_email_nonce'], 'qrp_email_settings_'.$current_user->user_login )) {
        echo QRPUtility::instance()->admin_notice( array(
            'type' => 'error',
            'message' => 'Failed to save changes'
        ) );
    }else{
        update_option( WP_QRP_OPTION_PREFIX . "email_subject", $_REQUEST['qrp_email_subject']);
        update_option( WP_QRP_OPTION_PREFIX . "email_message", $_REQUEST['custom_email_message']);
        update_option( WP_QRP_OPTION_PREFIX . "email_message_footer", $_REQUEST['custom_email_message_footer']);
        echo QRPUtility::instance()->admin_notice( array(
            'type' => 'update',
            'message' => 'Changes successfully saved'
        ) );
    }
}

$subject = get_option( WP_QRP_OPTION_PREFIX . "email_subject");
$message = get_option( WP_QRP_OPTION_PREFIX . "email_message");
$footer = get_option( WP_QRP_OPTION_PREFIX . "email_message_footer");
?>
<form action="<?php echo esc_url( admin_url('admin.php?page=qrp-settings&tab=email') ); ?>" method="post">
    <label for="qrp_email_subject">Subject</label>
    <input type="hidden" value="<?php echo wp_create_nonce('qrp_email_settings_'.$current_user->user_login )?>" name="qrp_email_nonce"/>
    <input type="text" name="qrp_email_subject" id="qrp_email_subject" value="<?php echo $subject ?>"/>

    <label for="custom_email_message">Message</label>
    <?php
    wp_editor($message, 'custom_email_message', '');
    ?>
    <label for="custom_email_message">Footer Message</label>
    <?php
    wp_editor($footer, 'custom_email_message_footer', '');
    ?>
    <input type="submit" class="button" name="qrp_submit" value="Save Settings"/>
</form>
