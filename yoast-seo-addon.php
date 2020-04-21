<?php

/*
Plugin Name: WP All Import Yoast SEO Add-On
Description: A complete example add-on for importing data to certain Yoast SEO fields.
Version: 1.0
Author: WP All Import
*/


include "rapid-addon.php";

/**
 * Class YoastSeoAddOn
 */
final class YoastSeoAddOn {

    /**
     * Singletone instance.
     * @var YoastSeoAddOn
     */
    protected static $instance;

    /**
     * AddOn instance.
     * @var RapidAddon
     */
    protected $addOn;

    /**
     * Return singletone instance.
     * @return YoastSeoAddOn
     */
    static public function getInstance() {
        if (self::$instance == NULL) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * YoastSeoAddOn constructor.
     */
    protected function __construct() {
        $this->addOn = new RapidAddon('Yoast SEO Add-On', 'yoast_seo_addon');
        $this->addOn->add_field('yoast_wpseo_title', 'SEO Title', 'text');
        $this->addOn->add_field('yoast_wpseo_metadesc', 'Meta Description', 'text');
        $this->addOn->add_field('yoast_wpseo_meta-robots-noindex', 'Meta Robots Index', 'radio', array('' => 'default', '1' => 'noindex', '2' => 'index'));
        $this->addOn->add_field('yoast_wpseo_opengraph-image', 'Facebook Image', 'image');
        $this->addOn->set_import_function([$this, 'import']);
        add_action('admin_init', [$this, 'admin_init']);
    }

    /**
     *  Check add-on conditions.
     */
    public function admin_init() {
        if (function_exists('is_plugin_active')) {
            // display this notice if neither the free or pro version of the Yoast plugin is active.
            if ( !is_plugin_active( "wordpress-seo/wp-seo.php" ) && !is_plugin_active( "wordpress-seo-premium/wp-seo-premium.php" ) ) {
                // Specify a custom admin notice.
                $this->addOn->admin_notice(
                    'The Yoast WordPress SEO Add-On requires WP All Import <a href="http://wordpress.org/plugins/wp-all-import" target="_blank">Free</a> and the <a href="https://yoast.com/wordpress/plugins/seo/">Yoast WordPress SEO</a> plugin.'
                );
            }
            // only run this add-on if the free or pro version of the Yoast plugin is active.
            if ( is_plugin_active( "wordpress-seo/wp-seo.php" ) || is_plugin_active( "wordpress-seo-premium/wp-seo-premium.php" ) ) {
                $this->addOn->run();
            }
        }
    }

    /**
     * Import function.
     *
     * @param $post_id
     * @param $data
     * @param $import_options
     */
    public function import($post_id, $data, $import_options) {

        if ($this->addOn->can_update_meta('_yoast_wpseo_title', $import_options)) {
            update_post_meta($post_id, '_yoast_wpseo_title', $data['yoast_wpseo_title']);
        }

        if ($this->addOn->can_update_meta('_yoast_wpseo_metadesc', $import_options)) {
            update_post_meta($post_id, '_yoast_wpseo_metadesc', $data['yoast_wpseo_metadesc']);
        }

        if ($this->addOn->can_update_meta('_yoast_wpseo_meta-robots-noindex', $import_options)) {
            update_post_meta($post_id, '_yoast_wpseo_meta-robots-noindex', $data['yoast_wpseo_meta-robots-noindex']);
        }

        if ($this->addOn->can_update_image($import_options)) {
            $image_url = wp_get_attachment_url($data['yoast_wpseo_opengraph-image']['attachment_id']);
            update_post_meta($post_id, '_yoast_wpseo_opengraph-image', $image_url);
        }
    }
}

YoastSeoAddOn::getInstance();
