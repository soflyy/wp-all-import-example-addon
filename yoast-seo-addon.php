<?php
/*
Plugin Name: WP All Import Yoast SEO Add-On
Description: A complete example add-on for importing data to certain Yoast SEO fields.
Version: 1.0
Author: WP All Import
*/

include "rapid-addon.php";

/**
 * Class Yoast_Seo_Add_On.
 */
final class Yoast_Seo_Add_On {

    /**
     * Singletone instance.
     * @var Yoast_Seo_Add_On
     */
    protected static $instance;

    /**
     * Add On instance.
     * @var RapidAddon
     */
    protected $add_on;

    /**
     * Return singletone instance.
     * @return Yoast_Seo_Add_On
     */
    static public function get_instance() {
        if ( self::$instance == NULL ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Yoast_Seo_Add_On constructor.
     */
    protected function __construct() {
        $this->add_on = new RapidAddon( 'Yoast SEO Add-On', 'yoast_seo_addon' );
        $this->add_on->add_field( 'yoast_wpseo_title', 'SEO Title', 'text' );
        $this->add_on->add_field( 'yoast_wpseo_metadesc', 'Meta Description', 'text' );
        $this->add_on->add_field( 'yoast_wpseo_meta-robots-noindex', 'Meta Robots Index', 'radio', ['' => 'default', '1' => 'noindex', '2' => 'index'] );
        $this->add_on->add_field( 'yoast_wpseo_opengraph-image', 'Facebook Image', 'image' );
        $this->add_on->import_images( 'yoast_wpseo_photo_gallery', 'Photo Gallery', 'images', [ $this, 'photo_gallery' ]);
        $this->add_on->set_import_function( [ $this, 'import' ] );
        add_action( 'init', [ $this, 'init' ] );
    }

    /**
     *  Check add-on conditions.
     */
    public function init() {
        if ( function_exists('is_plugin_active') ) {
            // Display this notice if neither the free or pro version of the Yoast plugin is active.
            if ( ! is_plugin_active( 'wordpress-seo/wp-seo.php' ) && ! is_plugin_active( 'wordpress-seo-premium/wp-seo-premium.php' ) ) {
                // Specify a custom admin notice.
                $this->add_on->admin_notice(
                    'The Yoast WordPress SEO Add-On requires WP All Import <a href="http://wordpress.org/plugins/wp-all-import" target="_blank">Free</a> and the <a href="https://yoast.com/wordpress/plugins/seo/">Yoast WordPress SEO</a> plugin.'
                );
            }
            // Only run this add-on if the free or pro version of the Yoast plugin is active.
            if ( is_plugin_active( 'wordpress-seo/wp-seo.php' ) || is_plugin_active( 'wordpress-seo-premium/wp-seo-premium.php' ) ) {
                $this->add_on->run();
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
    public function import( $post_id, $data, $import_options ) {

        if ( $this->add_on->can_update_meta( '_yoast_wpseo_title', $import_options ) ) {
            update_post_meta( $post_id, '_yoast_wpseo_title', $data['yoast_wpseo_title'] );
        }

        if ( $this->add_on->can_update_meta( '_yoast_wpseo_metadesc', $import_options ) ) {
            update_post_meta( $post_id, '_yoast_wpseo_metadesc', $data['yoast_wpseo_metadesc'] );
        }

        if ( $this->add_on->can_update_meta( '_yoast_wpseo_meta-robots-noindex', $import_options ) ) {
            update_post_meta( $post_id, '_yoast_wpseo_meta-robots-noindex', $data['yoast_wpseo_meta-robots-noindex'] );
        }

        if ( $this->add_on->can_update_image( $import_options ) ) {
            $image_url = wp_get_attachment_url( $data['yoast_wpseo_opengraph-image']['attachment_id'] );
            update_post_meta( $post_id, '_yoast_wpseo_opengraph-image', $image_url );
        }
    }

    /**
     * Import gallery handler.
     *
     * @param $post_id
     * @param $attachment_id
     * @param $image_filepath
     * @param $import_options
     */
    public function photo_gallery( $post_id, $attachment_id, $image_filepath, $import_options ) {

    }
}

Yoast_Seo_Add_On::get_instance();
