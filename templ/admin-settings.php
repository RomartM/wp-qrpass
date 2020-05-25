<?php
if (! defined( 'ABSPATH' ) ){
    exit;
}

$grouped_settings = array(
    array("slug"=>"Link Forms", "dir_path"=>"/templ/admin-form-links.php"),
    array("slug"=>"Email", "dir_path"=>"/templ/admin-form-email-settings.php"),
    array("slug"=>"Response", "dir_path"=>"/templ/admin-form-response.php"),
    array("slug"=>"Conditions", "dir_path"=>"/templ/admin-form-filters.php"),
    array("slug"=>"Storage", "dir_path"=>"/templ/admin-storage.php"),
    array("slug"=>"Servers", "dir_path"=>"/templ/admin-server-validation.php"),
    array("slug"=>"Security", "dir_path"=>"/templ/admin-security.php"),
);

$entries_tab = new QRPTabs(
    $grouped_settings,
    array("slug"=>"slug", "ref_id"=>"dir_path"),
    "qrp-settings"
);
$entries_tab->build(null);
