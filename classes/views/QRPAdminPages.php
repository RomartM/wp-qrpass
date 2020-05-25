<?php

if (! defined( 'ABSPATH' ) ){
    exit;
}

/**
 * Class QRPAdminPages
 */
class QRPAdminPages
{

    protected $wp_option_prefix;

    /**
     * QRPAdminPages constructor.
     * @param string $option_prefix
     */
    public function __construct($option_prefix="wp_qrp_opt") {
        $this->wp_option_prefix = $option_prefix;
    }

    /**
     * Generate page heading
     * @param string $heading
     * @return string
     */
    protected function page_header($heading="h1"){
        $title = esc_html( get_admin_page_title() );
        return '<'. $heading .' class="qrp-page-header">'. $title .'</'. $heading .'>';
    }

    /**
     * Generate page content
     * @param string $content
     * @param string $class_name
     */
    protected function page_body($content="", $class_name="wrap"){
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        echo '<div class="qrp-page-body ' . $class_name . '">';
        echo $this->page_header();
        include (WP_QRP_ROOT . $content);
        echo $this->page_footer();
        echo '</div>';
    }

    /**
     * Generate page footer
     * @param string $content
     * @return string
     */
    protected function page_footer($content=""){
        return '<div class="qrp-page-footer">' . $content . '</div>';
    }

    /**
     * Create settings view
     */
    public function qrp_settings(){
        $this->page_body(
            '/templ/admin-settings.php'
        );
    }

    /**
     * Create entries manager view
     */
    public function qrp_entries_manager_contents() {
        $this->page_body(
            '/templ/admin-entries-manager.php'
        );
    }

}