<?php



/*
 * Main WP QRP class
 * @class WP_QRP_Init
 * @since 1.0
 */

class QRPInit {

    private $ajax_responder;

    /**
     *  Set things up.
     *  @since 1.0
     */
    public function __construct() {

        // include plugin classes
        $this->include_classes();

        //run on activation of plugin
        register_activation_hook( __FILE__, array( $this, 'wp_qrp_activate' ) );

        //run on deactivation of plugin
        register_deactivation_hook( __FILE__, array( $this, 'wp_qrp_deactivate' ) );

        //run on uninstall
        register_uninstall_hook( __FILE__, array( 'WP_QRP_Init', 'wp_qrp_uninstall' ) );

        // validate is caldera forms plugin exist
        add_action( 'admin_init', array( $this, 'wp_qrp_validate_parent_plugin' ) );

        // register admin menu
        add_action( 'admin_menu', array( $this, 'register_wp_qrp_menu_pages' ) );

        // load admin and wp asset files
        add_action( 'admin_enqueue_scripts', array( $this, 'register_qrp_admin_plugin_scripts' ));
        add_action( 'admin_enqueue_scripts', array( $this, 'load_qrp_admin_plugin_scripts' ));
        add_action( 'wp_enqueue_scripts', array( $this, 'register_qrp_plugin_script' ));
        add_action( 'wp_enqueue_scripts', array( $this, 'load_qrp_plugin_script' ));

        // Add form entries screen options
        add_filter('set-screen-option', array( $this, 'wp_qrp_set_options' ), 10, 3);

        // Add custom link for our plugin
        add_filter( 'plugin_action_links_' . WP_QRP_BASE_NAME , array( $this, 'wp_qrp_plugin_action_links' ) );

        // load ajax responder
        $this->ajax_responder = new QRPAjaxResponder();
    }

    public function include_classes(){
        QRPUtility::instance()->load_classes(array(
            'classes/core/QRPDataTable.php',
            'classes/core/QRPCrypto.php',
            'classes/core/QRPHashValidator.php',
            'classes/core/QRPGenerator.php',
            'classes/core/QRPResponseFilter.php',
            'classes/core/QRPResultGenerator.php',
            'classes/core/QRPActivityCollector.php',
            'classes/core/QRPEntriesManager.php',
            'classes/core/QRPAjaxResponder.php',
            'classes/views/QRPTabs.php',
            'classes/views/QRPEntriesTable.php',
            'overrides/caldera-hooks.php',
            'views/server.php',
            'views/image-resource.php'
        ));
    }

    /**
     * Do things on plugin activation
     * @since 1.0
     */
    public function wp_qrp_activate() {
        $db = new QRPDataTable();
        $db->install();
    }

    /**
     *  Runs on plugin uninstall.
     *  a static class method or function can be used in an uninstall hook
     *
     *  @since 1.0
     */

    public static function wp_qrp_uninstall() {
        // Drop Database excluding caldera form entries
        $db = new QRPDataTable();
        $db->uninstall();
        $db->deleteOptions();
    }

    /**
     * Validate parent Plugin Caldera Forms exist and activated
     * @since 1.0
     */
    public function wp_qrp_validate_parent_plugin() {
        if ( ( ! defined( CFCORE_VER  ) ) && ( ! defined( 'WP_QRP_VERSION' ) ) ) {
            add_action( 'admin_notices', array( $this, 'wp_qrp_caldera_forms_missing_notice' ) );
            deactivate_plugins( WP_QRP_BASE_NAME );
            if ( isset( $_GET[ 'activate' ] ) ) {
                unset( $_GET[ 'activate' ] );
            }
        }
    }

    /**
     * If Caldera Forms plugin is not installed or activated then throw the error
     *
     * @return mixed error_message, an array containing the error message
     *
     * @since 1.0 initial version
     */
    public function wp_qrp_caldera_forms_missing_notice() {
        $plugin_error = QRPUtility::instance()->admin_notice( array(
            'type' => 'error',
            'message' => 'WP QR Pass requires Caldera Forms plugin to be installed and activated.'
        ) );
        echo $plugin_error;
    }

    /**
     * Create/Register menu items for the plugin.
     * @since 1.0
     */
    public function register_wp_qrp_menu_pages() {
        $admin_page = new QRPAdminPages();

        $hook = add_menu_page(
            __( 'Entries Manager', 'wp-qrp' ),
            __( 'Entries Manager', 'wp-qrp' ),
            'manage_options',
            'qrp-entries-manager',
            array( $admin_page, 'qrp_entries_manager_contents' ),
            'dashicons-schedule',
            3
        );

        add_submenu_page(
            'qrp-entries-manager',
            __( 'Settings', 'wp-qrp' ),
            __( 'Settings', 'wp-qrp' ),
            'manage_options',
            'qrp-settings',
            array( $admin_page, 'qrp_settings' ));


        add_action( "load-$hook", array( $this, 'wp_qrp_add_options') );
    }

    /**
     * Form Entries table options
     */
    public function wp_qrp_add_options() {
        global $qrpEntriesTable;

        $option = 'per_page';
        $args = array(
            'label' => 'Form Entries',
            'default' => 10,
            'option' => 'qrp_entries_per_page'
        );
        add_screen_option( $option, $args );

        $link_forms_data = get_option( WP_QRP_OPTION_PREFIX . "link_forms");
        if(empty($link_forms_data)){
            return;
        }

        $default_tab = QRPUtility::instance()->format_group_name(array_values($link_forms_data)[0]['group']);
        $tab = isset($_GET['tab']) ? $_GET['tab'] : $default_tab;

        $form_id = "";
        $link_forms_data = get_option( WP_QRP_OPTION_PREFIX . "link_forms");
        foreach ($link_forms_data as $entry){
            if($tab=== QRPUtility::instance()->format_group_name($entry["group"])){
                $form_id = $entry["cf_id"];

            }
        }

        $form = Caldera_Forms_Forms::get_form(  $form_id );
        $qrpEntriesTable = new QRPEntriesTable($form);
    }

    /**
     * Set form entry table options
     *
     * @param $status
     * @param $option
     * @param $value
     * @return mixed
     */
    public function wp_qrp_set_options($status, $option, $value) {

        if ( 'qrp_entries_per_page' == $option ) return $value;

        return $status;

    }

    /**
     * Register admin pages assets
     */
    public function register_qrp_admin_plugin_scripts() {
        global $current_user;
        wp_get_current_user();


        // Entries Manager Assets
        wp_register_style( 'qrp-admin', WP_QRP_URL . 'assets/admin/css/qrp-style.css', array( 'wp-jquery-ui-dialog' ) );
        wp_register_script( 'qrp-admin', WP_QRP_URL . 'assets/admin/js/qrp-script.js', array('jquery', 'jquery-ui-dialog') );
        wp_localize_script( 'qrp-admin', 'qrpAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));

        // Link Form Settings Assets
        wp_register_style( 'qrp-admin-link-forms', WP_QRP_URL . 'assets/admin/css/qrp-link-forms-style.css' );
        wp_register_script( 'qrp-admin-link-forms', WP_QRP_URL . 'assets/admin/js/qrp-link-forms-script.js', array('jquery') );

        // Form Filter Settings Assets
        wp_register_style( 'qrp-admin-form-filters', WP_QRP_URL . 'assets/admin/css/qrp-form-filters-style.css', array( 'wp-jquery-ui-dialog' ) );
        wp_register_script( 'qrp-admin-form-filters', WP_QRP_URL . 'assets/admin/js/qrp-form-filters-script.js', array('jquery',  'jquery-ui-dialog') );
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
        wp_register_style( 'qrp-admin-form-response', WP_QRP_URL . 'assets/admin/css/qrp-form-response-style.css', array( 'wp-jquery-ui-dialog' ) );
        wp_register_script( 'qrp-admin-form-response', WP_QRP_URL . 'assets/admin/js/qrp-form-response-script.js', array('jquery',  'jquery-ui-dialog') );
        wp_localize_script( 'qrp-admin-form-response', 'qrpAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'action' => 'qrp_form_response', 'nonce' => wp_create_nonce('qrp_form_filters_'.$current_user->user_login )));
    }

    /**
     * Load admin pages assets only to the specific page
     *
     * @param $hook
     */
    public function load_qrp_admin_plugin_scripts( $hook ) {

        // Load Settings Tab Assets
        if($hook == 'entries-manager_page_qrp-settings' && isset($_REQUEST['tab'])){
            switch ($_REQUEST['tab']){
                case 'response':
                    wp_enqueue_style( 'qrp-admin-form-response' );
                    wp_enqueue_script( 'qrp-admin-form-response' );
                    break;
                case 'link_forms':
                    wp_enqueue_style( 'qrp-admin-link-forms' );
                    wp_enqueue_script( 'qrp-admin-link-forms' );
                    break;
                case 'conditions':
                    wp_enqueue_style( 'qrp-admin-form-filters' );
                    wp_enqueue_script( 'qrp-admin-form-filters' );
                    break;
                default:
            }
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

    /**
     * Register wp frontend assets
     */
    public function register_qrp_plugin_script(){
        wp_register_style( 'qrp-wp-generic', WP_QRP_URL . 'assets/css/qrp-generic-style.css', array() );
        wp_register_script( 'qrp-wp-generic', WP_QRP_URL . 'assets/js/qrp-generic-script.js', array('jquery') );
        wp_localize_script( 'qrp-wp-generic', 'qrpPublicAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ),  'action' => 'resend_email_public'));
    }

    /**
     * Load wp frontend assets
     */
    public function load_qrp_plugin_script(){
        wp_enqueue_style( 'qrp-wp-generic' );
        wp_enqueue_script( 'qrp-wp-generic' );
    }

    /**
     * Add custom link for the plugin beside activate/deactivate links
     * @param array $links Array of links to display below our plugin listing.
     * @return array Amended array of links.    *
     * @since 1.0
     */
    public function wp_qrp_plugin_action_links( $links ) {
        return array_merge( array(
            '<a href="' . admin_url( 'admin.php?page=qrp-entries-manager' ) . '">' . __( 'Entries Manager', 'wp-qrp' ) . '</a>'
        ), $links );
    }
}
