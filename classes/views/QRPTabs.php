<?php

if (! defined( 'ABSPATH' ) ){
    exit;
}

/**
 * Class QRPTabs
 */
class QRPTabs
{
    private $tab;
    private $tab_name;
    private $tab_index;
    private $page_slug;
    private $default_tab;
    private $content_meta;
    private $content;

    /**
     * QRPTabs constructor.
     *
     * @param array $content Usage: array( array("content_label"=>"First Tab", "content_id"=>"sub_page_path") )
     * @param array $content_meta Usage: array("slug"=>"{content_label}", "ref_id"=>"{content_id}")
     * @param string $page_slug Page slug e.g wp-admin/?page={page_slug}
     * @param string $tab_name Tab name e.g wp-admin/?page=page_slug&{tab_name}=slug
     * @param string $default_tab Slug name
     */
    public function __construct($content, $content_meta, $page_slug, $tab_name='tab', $default_tab=''){
        $this->tab_name = $tab_name;
        $this->page_slug = $page_slug;
        $this->default_tab = empty($default_tab) ? $content[0][$content_meta['slug']] : $default_tab;
        $this->content = $content;
        $this->content_meta = $content_meta;

    }

    /**
     * Tab dynamic navs
     */
    protected function nav(){
        $index = 0;
        echo '<nav class="nav-tab-wrapper">';
        foreach ($this->content as $data){
            $is_active = "";
            if( QRPUtility::instance()->format_group_name($this->tab) === QRPUtility::instance()->format_group_name($data[$this->content_meta['slug']])){
                $this->tab_index = $index;
                $is_active = "nav-tab-active";
            }
            echo "<a href=\"?page=" . $this->page_slug . "&" . $this->tab_name . "=". QRPUtility::instance()->format_group_name($data[$this->content_meta['slug']])."\" class=\"nav-tab " . $is_active . "\">". $data[$this->content_meta['slug']] ."</a>";
            $index++;
        }
        echo '</nav>';
    }


    /**
     * Build Tab
     *
     * @param mixed $content_callback  For custom dynamic content if empty will use ref_id as sub page path to include
     */
    public function build($content_callback){
        $this->tab = isset($_GET[$this->tab_name]) ? $_GET[$this->tab_name] : $this->default_tab;
        $this->nav();
        $index = $this->tab_index;

        if (empty($index)){
            $this->tab = $this->default_tab;
            $index = 0;
        }

        $ref_id = $this->content[$index][$this->content_meta['ref_id']];

        echo '<div class="tab-content">';
        if(empty($content_callback)){
            $dir_path = WP_QRP_ROOT . '/' . $ref_id;
            if(file_exists($dir_path)){
                include( $dir_path );
            }else{
                echo "Sub page does not exist";
            }
        }else{
            $content_callback($this->tab, $ref_id);
        }
        echo '<div>';
    }

}