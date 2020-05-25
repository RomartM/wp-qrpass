<?php

$link_forms_data = get_option( WP_QRP_OPTION_PREFIX . "link_forms");
if(empty($link_forms_data)){
    echo "<div>Please link at least one form to be able to use this dashboard.</div></div>";
    return;
}

$entries_tab = new QRPTabs( $link_forms_data, array("slug"=>"group", "ref_id"=>"cf_id"), "qrp-entries-manager" );
$entries_tab->build(function($group, $form_id){
    ?>
    <form method="post" >
        <?php
        global $qrpEntriesTable;

        if( isset($_POST['s']) ){
            $qrpEntriesTable->prepare_items($_POST['s']);
        } else {
            $qrpEntriesTable->prepare_items();
        }
        $qrpEntriesTable -> search_box( 'search', 'search_id' );
        $qrpEntriesTable -> display();
        ?>
    </form>
    <?php
});
?>
<!-- The modal / dialog box, hidden somewhere near the footer -->
<div id="qrp-dialog" class="hidden" style="max-width:800px">
    <h3 class="dialog-title">Dialog content</h3>
    <div class="qrp_view qrp-center hidden">
        <div class="loading hidden">
            <h2>Loading...</h2>
            <div class="qrp-spinner"></div>
        </div>
        <div class="success hidden"></div>
    </div>
    <div class="qrp_approve qrp-center hidden">
        <div class="loading hidden">
            <h2>Approving...</h2>
            <div class="qrp-spinner"></div>
        </div>
        <div class="success hidden">
            <h2>QR Pass Approved</h2>
        </div>
    </div>
    <div class="qrp_revoke qrp-center hidden">
        <div class="loading hidden">
            <h2>Revoking...</h2>
            <div class="qrp-spinner"></div>
        </div>
        <div class="success hidden">
            <h2>QR Pass Revoked</h2>
        </div>
    </div>
    <div class="qrp_update_link qrp-center hidden">
        <div class="default hidden">
            <h2>Update Reference ID</h2>
            <div class="link-wrapper">Reference ID: <input type="text" placeholder="R-XXXXXXXXXX" id="qrp_reference_id"/></div>
            <button class="update_link_action button action">Update Reference ID</button>
        </div>
        <div class="loading hidden">
            <h2>Updating Reference ID...</h2>
            <div class="qrp-spinner"></div>
        </div>
        <div class="success hidden">
            <h2>Reference ID Updated</h2>
        </div>
    </div>
    <div class="qrp_update_email qrp-center hidden">
        <div class="default hidden">
            <h2>Update Email</h2>
            <div class="email-wrapper">Email Address: <input type="email" id="qrp_email_address"/></div>
            <button class="update_email_action button action">Update Email</button>
        </div>
        <div class="loading hidden">
            <h2>Updating Email...</h2>
            <div class="qrp-spinner"></div>
        </div>
        <div class="success hidden">
            <h2>Email Updated</h2>
        </div>
    </div>
    <div class="qrp_send_email qrp-center hidden">
        <div class="default hidden">
            <h2>Notify User</h2>
            <div class="email-wrapper">Current Email: <span class="email_address"></span></div>
            <button class="send_email_action button action">Send Email</button>
        </div>
        <div class="loading hidden">
            <h2>Sending Email...</h2>
            <div class="qrp-spinner"></div>
        </div>
        <div class="success hidden">
            <h2>Email Sent</h2>
        </div>
    </div>
</div>
