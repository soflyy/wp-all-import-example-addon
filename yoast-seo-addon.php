<?php
/*
Plugin Name: WP All Import Yoast SEO Add-On
Description: A complete example add-on for importing data to certain Yoast SEO fields.
Version: 1.0
Author: WP All Import
*/

include "rapid-addon.php";


final class Yoast_SEO_Add_On {

    protected static $instance;

    protected $add_on;

    static public function get_instance() {
        if ( self::$instance == NULL ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    protected function __construct() {
        
        // Define the add-on
        $this->add_on = new RapidAddon( 'Yoast SEO Add-On', 'wpai_yoast_seo_add_on' );
        
        // Add UI elements to the import template
        $this->add_on->add_field( 'yoast_wpseo_title', 'SEO Title', 'text' );
        $this->add_on->add_field( 'yoast_wpseo_metadesc', 'Meta Description', 'text' );
        $this->add_on->add_field( 'yoast_wpseo_meta-robots-noindex', 'Meta Robots Index', 'radio', ['' => 'default', '1' => 'noindex', '2' => 'index'] );
        $this->add_on->add_field( 'yoast_wpseo_opengraph-image', 'Facebook Image', 'image' );

        $this->add_on->set_import_function( [ $this, 'import' ] );
        add_action( 'admin_init', [ $this, 'admin_init' ] );
    }

    // Check if Yoast SEO is installed and activate
    public function admin_init() {
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

    // Check if the user has allowed these fields to be updated, and then import data to them
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
}

Yoast_SEO_Add_On::get_instance();
