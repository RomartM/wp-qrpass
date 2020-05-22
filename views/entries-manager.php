<?php

function qrp_add_options() {
    global $qrpEntriesTable;
    $option = 'per_page';
    $args = array(
        'label' => 'User Entries',
        'default' => 10,
        'option' => 'qrp_entries_per_page'
    );
    add_screen_option( $option, $args );

    $link_forms_data = get_option("qrp_link_forms");
    if(empty($link_forms_data)){
        return;
    }

    $default_tab = qrp_format_group_name(array_values($link_forms_data)[0]['group']);
    $tab = isset($_GET['tab']) ? $_GET['tab'] : $default_tab;

    $form_id = "";
    $link_forms_data = get_option("qrp_link_forms");
    foreach ($link_forms_data as $entry){
        if($tab===qrp_format_group_name($entry["group"])){
            $form_id = $entry["cf_id"];
        }
    }

    $form = Caldera_Forms_Forms::get_form(  $form_id ); //'CF5ea3787b870e3'
    $qrpEntriesTable = new QRPEntriesTable($form);
}

add_filter('set-screen-option', 'qrp_set_options', 10, 3);

function qrp_set_options($status, $option, $value) {

    if ( 'qrp_entries_per_page' == $option ) return $value;

    return $status;

}

function my_admin_menu() {

    $hook = add_menu_page(

        __( 'Entries Manager', 'qr-pass' ),

        __( 'Entries Manager', 'qr-pass' ),

        'manage_options',

        'qrp-entries-manager',

        'qrp_entries_manager_contents',

        'dashicons-schedule',

        3

    );

    add_submenu_page(
        'qrp-entries-manager',
        'Response Settings',
        'Response Settings',
        'manage_options',
        'qrp-form-response-settings',
        'qrp_form_response_contents');

    add_submenu_page(
            'qrp-entries-manager',
            'Email Settings',
            'Email Settings',
            'manage_options',
            'qrp-email-settings',
            'qrp_email_settings_contents' );

    add_submenu_page(
            'qrp-entries-manager',
            'Link Forms',
            'Link Forms',
            'manage_options',
            'qrp-link-forms',
            'qrp_link_forms_contents');

    add_submenu_page(
        'qrp-entries-manager',
        'Form Filters',
        'Form Filters',
        'manage_options',
        'qrp-form-filters',
        'qrp_form_filters_contents');

    add_action( "load-$hook", 'qrp_add_options' );
}

add_action( 'admin_menu', 'my_admin_menu' );

function qrp_save_error_notice() {
    ?>
    <div class="error notice">
        <p><?php _e( 'Failed to save changes', 'qr-pass' ); ?></p>
    </div>
    <?php
}

function qrp_save_update_notice() {
    ?>
    <div class="updated notice">
        <p><?php _e( 'Changes successfully saved', 'qr-pass' ); ?></p>
    </div>
    <?php
}

function qrp_form_response_contents(){
    ?>
    <div class="wrap">
        <!-- Print the page title -->
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <?php
        $link_forms_data = get_option("qrp_link_forms");

        ?>  <label for="form-list">Select Form</label>
        <select class="form-list" id="form-list"><?php
            foreach ($link_forms_data as $item){
                ?>
                <option value="<?php echo $item['cf_id'] ?>"><?php echo $item['group'] ?></option>
                <?php
            }?></select><?php
        ?>
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
    <?php
}

function qrp_form_filters_contents(){
    ?>
    <div class="wrap">
    <!-- Print the page title -->
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    <?php
    $link_forms_data = get_option("qrp_link_forms");

    ?>  <label for="form-list">Select Form</label>
        <select class="form-list" id="form-list"><?php
    foreach ($link_forms_data as $item){
        ?>
        <option value="<?php echo $item['cf_id'] ?>"><?php echo $item['group'] ?></option>
        <?php
    }?></select><?php
    ?>
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
    <?php
}

function qrp_link_forms_contents(){
     global $current_user;
     wp_get_current_user();
     if(!empty($_REQUEST['qrp_form_link_submit'])){
        if ( !wp_verify_nonce( $_REQUEST['qrp_email_nonce'], 'qrp_email_settings_'.$current_user->user_login )) {
            qrp_save_error_notice();
        }else{
            if(!empty($_REQUEST['qrp_form_item'])){
                update_option("qrp_link_forms", $_REQUEST['qrp_form_item'] );
                qrp_save_update_notice();
            }
        }
    }

    $link_forms_data = get_option("qrp_link_forms");
    ?>
    <div class="wrap">
    <!-- Print the page title -->
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    <form action="<?php echo esc_url( admin_url('admin.php?page=qrp-link-forms') ); ?>" method="post">
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
    </div>
<?php
}

function qrp_email_settings_contents(){
    ?>
    <div class="wrap">
    <!-- Print the page title -->
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    <?php
    global $current_user;
    wp_get_current_user();

    if(!empty($_REQUEST['qrp_submit'])){
        if ( !wp_verify_nonce( $_REQUEST['qrp_email_nonce'], 'qrp_email_settings_'.$current_user->user_login )) {
            qrp_save_error_notice();
        }else{
            update_option("qrp_email_subject", $_REQUEST['qrp_email_subject']);
            update_option("qrp_email_message", $_REQUEST['custom_email_message']);
            update_option("qrp_email_message_footer", $_REQUEST['custom_email_message_footer']);
            qrp_save_update_notice();
        }
    }

    $subject = get_option("qrp_email_subject");
    $message = get_option("qrp_email_message");
    $footer = get_option("qrp_email_message_footer");
    ?>
    <form action="<?php echo esc_url( admin_url('admin.php?page=qrp-email-settings') ); ?>" method="post">
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
    </div>
    <?php
}

function qrp_format_group_name($group_name){
    return str_replace(' ', '_', strtolower($group_name));
}

function qrp_entries_manager_contents() {
 // check user capabilities
  if ( ! current_user_can( 'manage_options' ) ) {
    return;
  }

  ?>
  <!-- Our admin page content should all be inside .wrap -->
  <div class="wrap">
    <!-- Print the page title -->
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

    <?php
        $link_forms_data = get_option("qrp_link_forms");
          if(empty($link_forms_data)){
            echo "<div>Please link at least one form to be able to use this dashboard.</div></div>";
            return;
          }
        $default_tab = qrp_format_group_name(array_values($link_forms_data)[0]['group']);
        $tab = isset($_GET['tab']) ? $_GET['tab'] : $default_tab;
    ?>
    <!-- Here are our tabs -->
    <nav class="nav-tab-wrapper">
      <?php
      $form_id = "";
      $is_active = "";
      $link_forms_data = get_option("qrp_link_forms");
        foreach ($link_forms_data as $entry){
            $is_active = "";
            if($tab===qrp_format_group_name($entry["group"])){
                $form_id = $entry["cf_id"];
                $is_active = "nav-tab-active";
            }
            echo "<a href=\"?page=qrp-entries-manager&tab=".qrp_format_group_name($entry["group"])."\" class=\"nav-tab " . $is_active . "\">". $entry["group"] ."</a>";
        }
      ?>
    </nav>

    <div class="tab-content">
    <?php
        qrp_entry_tab(array($tab, $form_id));
    ?>
    </div>
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
  </div>
  <?php
}

function qrp_entry_tab($identifier){

    list($group, $form_id) = $identifier;
    ?>
    <form method="post" >
    <?php
    $form = Caldera_Forms_Forms::get_form(  $form_id ); //'CF5ea3787b870e3'

    global $qrpEntriesTable;

    $qrpEntriesTable -> setup($form, $group ); // 'personnel'
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
}

function register_qrp_admin_plugin_scripts() {
    global $current_user;
    wp_get_current_user();


    // Entries Manager Assets
    wp_register_style( 'qrp-admin', QRP_PLUGIN_PATH . 'assets/admin/css/qrp-style.css', array( 'wp-jquery-ui-dialog' ) );
    wp_register_script( 'qrp-admin', QRP_PLUGIN_PATH . 'assets/admin/js/qrp-script.js', array('jquery', 'jquery-ui-dialog') );
    wp_localize_script( 'qrp-admin', 'qrpAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));

    // Link Form Settings Assets
    wp_register_style( 'qrp-admin-link-forms', QRP_PLUGIN_PATH . 'assets/admin/css/qrp-link-forms-style.css' );
    wp_register_script( 'qrp-admin-link-forms', QRP_PLUGIN_PATH . 'assets/admin/js/qrp-link-forms-script.js', array('jquery') );

    // Form Filter Settings Assets
    wp_register_style( 'qrp-admin-form-filters', QRP_PLUGIN_PATH . 'assets/admin/css/qrp-form-filters-style.css', array( 'wp-jquery-ui-dialog' ) );
    wp_register_script( 'qrp-admin-form-filters', QRP_PLUGIN_PATH . 'assets/admin/js/qrp-form-filters-script.js', array('jquery',  'jquery-ui-dialog') );
    wp_localize_script( 'qrp-admin-form-filters', 'qrpAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ),
        'action' => 'qrp_form_filters', 'nonce' => wp_create_nonce('qrp_form_filters_'.$current_user->user_login ),
        'equality_operators' => array(
            array('label' => 'Equal (=)', 'value' => '=='),
            array('label' => 'Greater Than (>)', 'value' => '>'),
            array('label' => 'Less Than (<)', 'value' => '<'),
            array('label' => 'Greater Than or Equal (>=)', 'value' => '>='),
            array('label' => 'Less Than or Equal (<=)', 'value' => '<=')
        )));

    // Form Response Settings Assets
    wp_register_style( 'qrp-admin-form-response', QRP_PLUGIN_PATH . 'assets/admin/css/qrp-form-response-style.css', array( 'wp-jquery-ui-dialog' ) );
    wp_register_script( 'qrp-admin-form-response', QRP_PLUGIN_PATH . 'assets/admin/js/qrp-form-response-script.js', array('jquery',  'jquery-ui-dialog') );
    wp_localize_script( 'qrp-admin-form-response', 'qrpAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ),
        'action' => 'qrp_form_response', 'nonce' => wp_create_nonce('qrp_form_filters_'.$current_user->user_login )));
}

add_action( 'admin_enqueue_scripts', 'register_qrp_admin_plugin_scripts' );


function load_qrp_admin_plugin_scripts( $hook ) {

    // Load Form Response Assets
    if($hook == 'entries-manager_page_qrp-form-response-settings'){
        wp_enqueue_style( 'qrp-admin-form-response' );
        wp_enqueue_script( 'qrp-admin-form-response' );

        return;
    }

    // Load Form Links Assets
    if($hook == 'entries-manager_page_qrp-link-forms'){
        wp_enqueue_style( 'qrp-admin-link-forms' );
        wp_enqueue_script( 'qrp-admin-link-forms' );

        return;
    }

    // Load Form Filters Assets
    if($hook == 'entries-manager_page_qrp-form-filters'){
        wp_enqueue_style( 'qrp-admin-form-filters' );
        wp_enqueue_script( 'qrp-admin-form-filters' );

        return;
    }

    // Load Default form entry manager
    if( $hook != 'toplevel_page_qrp-entries-manager' && $hook != 'entries-manager_page_qrp-email-settings' ) {

        return;

    }

// Load style & scripts.

    wp_enqueue_style( 'qrp-admin' );
    wp_enqueue_script( 'qrp-admin' );

}

add_action( 'admin_enqueue_scripts', 'load_qrp_admin_plugin_scripts' );

function register_qrp_plugin_script(){
    wp_register_style( 'qrp-wp-generic', QRP_PLUGIN_PATH . 'assets/css/qrp-generic-style.css', array() );
    wp_register_script( 'qrp-wp-generic', QRP_PLUGIN_PATH . 'assets/js/qrp-generic-script.js', array('jquery') );
    wp_localize_script( 'qrp-wp-generic', 'qrpPublicAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ),  'action' => 'resend_email_public'));
}

add_action( 'wp_enqueue_scripts', 'register_qrp_plugin_script' );

function load_qrp_plugin_script(){
    wp_enqueue_style( 'qrp-wp-generic' );
    wp_enqueue_script( 'qrp-wp-generic' );
}

add_action( 'wp_enqueue_scripts', 'load_qrp_plugin_script' );

/**
 * Generic AJAX Responder
 *
 * @param $action
 * @param $callback
 */
function generic_responder($action, $callback){
    $response = array();
    if ( !wp_verify_nonce( $_REQUEST['nonce'], $action . "_" . $_REQUEST['id_number']) && !empty($_REQUEST['id_number'])) {
        $response['status'] = "NONCE_ERROR";
    }else{
        if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            $response = $callback();
            $response['status'] = "SUCCESS";
        }else {
            $response['status'] = "HTTP_REFERER_ERROR";
        }
        $response['nonce'] = wp_create_nonce($action . "_" . $_REQUEST['id_number']);
    }
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode($response);
    die();
}


add_action("wp_ajax_qrp_view", "qrp_view");

function qrp_view() {
    generic_responder(__FUNCTION__, function (){
        $qrp_entries = new QRPEntriesManager($_REQUEST['cf_id']);
        $response['fields'] = $qrp_entries->getUserEntryFields($_REQUEST['id_number']);
        return $response;
    });
}

add_action("wp_ajax_qrp_approve", "qrp_approve");

function qrp_approve() {
    generic_responder(__FUNCTION__, function (){
        $qrp_entries = new QRPEntriesManager($_REQUEST['cf_id']);
        $response['message'] = $qrp_entries->approve($_REQUEST['id_number']);
        return $response;
    });
}

add_action("wp_ajax_qrp_revoke", "qrp_revoke");

function qrp_revoke() {
    generic_responder(__FUNCTION__, function (){
        $qrp_entries = new QRPEntriesManager($_REQUEST['cf_id']);
        $response['message'] = $qrp_entries->revoke($_REQUEST['id_number']);
        return $response;
    });
}

add_action("wp_ajax_qrp_update_email", "qrp_update_email");

function qrp_update_email() {
    generic_responder(__FUNCTION__, function (){
        $qrp_entries = new QRPEntriesManager($_REQUEST['cf_id']);
        if(empty($_REQUEST['param'])){
            $response['message'] = $qrp_entries->getEmailAddress($_REQUEST['id_number']);
        }else{
            $response['message'] = $qrp_entries->updateEmail($_REQUEST['id_number'], $_REQUEST['param']);
        }
        return $response;
    });
}

add_action("wp_ajax_qrp_send_email", "qrp_send_email");

function qrp_send_email() {
    generic_responder(__FUNCTION__, function (){
        $qrp_entries = new QRPEntriesManager($_REQUEST['cf_id']);

        if(empty($_REQUEST['param'])){
            $response['message'] = $qrp_entries->getEmailAddress($_REQUEST['id_number']);
        }else{
            $response['message'] = $qrp_entries->send($_REQUEST['id_number']);
        }
        $response['status'] = "SUCCESS";
        return $response;
    });
}

add_action("wp_ajax_qrp_form_filters", "qrp_form_filters");

function qrp_form_filters() {
    generic_responder(__FUNCTION__, function (){
        if(empty($_REQUEST['param'])){
            $form = Caldera_Forms_Forms::get_form( $_REQUEST['cf_id'] );

            $parsed_data = '';
            $raw_data = get_option('qrp_form_filters_config_'.$_REQUEST['cf_id']);
            parse_str($raw_data, $parsed_data);

            $response['fields'] = json_encode($form['fields']);
            $response['data'] = json_encode($parsed_data);
            $response['status'] = "SUCCESS";
        }else{
            $param_values = $_REQUEST['param'];
            parse_str($param_values, $parsed_param);

            if(!empty($parsed_param['qrp_group_condition'])){
                $result = update_option('qrp_form_filters_config_'.$_REQUEST['cf_id'], $_REQUEST['param']);
                if($result){
                    $response['status'] = "SUCCESS";
                }else{
                    $response['status'] = "ERROR";
                }
            } else {
                $response['status'] = "ERROR";
            }
        }

        return $response;
    });
}


add_action("wp_ajax_qrp_form_response", "qrp_form_response");

function qrp_form_response(){
    generic_responder(__FUNCTION__, function (){
        if(!empty($_REQUEST['param'])){
            switch ($_REQUEST['param']){
                case 'get_values':
                    $parsed_param = '';
                    $form = Caldera_Forms_Forms::get_form( $_REQUEST['cf_id'] );
                    $raw_data = get_option('qrp_form_response_config_'.$_REQUEST['cf_id']);

                    parse_str($raw_data, $parsed_param);

                    if(!empty($parsed_param['response_settings'])){
                        $response['data'] = json_encode($parsed_param);
                    }else{
                        $response['data'] = "empty";
                    }
                    $response['fields'] = json_encode($form['fields']);
                    $response['status'] = "SUCCESS";
                    break;
                case 'set_values':
                    $param_values = $_REQUEST['data'];
                    parse_str($param_values, $parsed_param);

                    if(!empty($parsed_param['response_settings'])){
                        $result = update_option('qrp_form_response_config_'.$_REQUEST['cf_id'], $_REQUEST['data']);
                        if($result){
                            $response['status'] = "SUCCESS";
                        }else{
                            $response['status'] = "ERROR";
                        }
                    }else{
                        $response['status'] = "ERROR";
                    }
                    break;
                default:
                    $response['status'] = "ERROR";
            }
        }else{
            $response['status'] = "ERROR";
        }

        return $response;
    });
}

add_action("wp_ajax_resend_email_public" , "resend_email_public");
add_action("wp_ajax_nopriv_resend_email_public" , "resend_email_public");

function resend_email_public(){
    if ( !wp_verify_nonce( $_REQUEST['nonce'], "qrp_eid_" . $_REQUEST['id_number']) && !empty($_REQUEST['id_number'])) {
        $response['status'] = "NONCE_ERROR";
    }else{
        $qrp_entries = new QRPEntriesManager($_REQUEST['cf_id']);
        $response['message'] = $qrp_entries->send($_REQUEST['id_number'], $_REQUEST['email']);
        $response['status'] = "SUCCESS";
    }
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode($response);
    wp_die();
}
