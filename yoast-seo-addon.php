<?php

/*
Plugin Name: WP All Import Yoast SEO Add-On
Description: A complete example add-on for importing data to certain Yoast SEO fields.
Version: 1.0
Author: WP All Import
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

final class Yoast_SEO_Add_On {

    protected static $instance;
    protected $add_on;
    protected $addon_name = 'Yoast SEO Add-On'; // Define the add-on name
    protected $addon_slug = 'wpai_yoast_seo_add_on'; // Define an unique slug for the add-on

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    protected function __construct() {
        add_action('init', array($this, 'init'));
    }

    public function init() {
        // Check for required classes and display an admin notice if not found
        if (!class_exists('PMXI_Plugin') || !class_exists('PMXI_RapidAddon')) {
            add_action('admin_notices', function() {
                echo '<div class="error notice"><p>The Yoast SEO Add-On requires WP All Import Pro to be installed and active.</p></div>';
            });
            return;
        }

        // Check if the Yoast SEO plugin is active
        if (!is_plugin_active('wordpress-seo/wp-seo.php') && !is_plugin_active('wordpress-seo-premium/wp-seo-premium.php')) {
            add_action('admin_notices', function() {
                echo '<div class="error notice"><p>The Yoast SEO Add-On requires the Yoast WordPress SEO plugin to be installed and active.</p></div>';
            });
            return;
        }

        // Initialize the add-on with its name and slug
        $this->add_on = new PMXI_RapidAddon($this->addon_name, $this->addon_slug);
        $this->wpai_setup_fields(); // Add fields to the add-on
        $this->add_on->set_import_function([$this, 'import']); // Set the import function
        $this->add_on->run(); // Run the add-on
    }

    // Define the fields for the import template
    public function wpai_setup_fields() {
        $this->add_on->add_field('yoast_wpseo_title', 'SEO Title', 'text');
        $this->add_on->add_field('yoast_wpseo_metadesc', 'Meta Description', 'text');
        $this->add_on->add_field('yoast_wpseo_meta-robots-noindex', 'Meta Robots Index', 'radio', ['' => 'default', '1' => 'noindex', '2' => 'index']);
        $this->add_on->add_field('yoast_wpseo_opengraph-image', 'Facebook Image', 'image');
    }

    // Import function to handle the actual data import
    public function import($post_id, $data, $import_options) {
        if ($this->add_on->can_update_meta('_yoast_wpseo_title', $import_options)) {
            update_post_meta($post_id, '_yoast_wpseo_title', $data['yoast_wpseo_title']);
        }

        if ($this->add_on->can_update_meta('_yoast_wpseo_metadesc', $import_options)) {
            update_post_meta($post_id, '_yoast_wpseo_metadesc', $data['yoast_wpseo_metadesc']);
        }

        if ($this->add_on->can_update_meta('_yoast_wpseo_meta-robots-noindex', $import_options)) {
            update_post_meta($post_id, '_yoast_wpseo_meta-robots-noindex', $data['yoast_wpseo_meta-robots-noindex']);
        }

        if ($this->add_on->can_update_image($import_options)) {
            $image_url = wp_get_attachment_url($data['yoast_wpseo_opengraph-image']['attachment_id']);
            update_post_meta($post_id, '_yoast_wpseo_opengraph-image', $image_url);
        }
    }
}

Yoast_SEO_Add_On::get_instance();
