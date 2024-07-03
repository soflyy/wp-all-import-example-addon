<?php
/*
Plugin Name: WP All Import Yoast SEO Add-On
Description: A complete example add-on for importing data to certain Yoast SEO fields.
Version: 1.0
Author: WP All Import
Text Domain: import-yoast-seo
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

final class Yoast_SEO_Add_On {

    protected static $instance;
    protected $add_on;
    protected $addon_name = 'Yoast SEO Add-On'; // Define the add-on name
    protected $addon_slug = 'wpai_yoast_seo_add_on'; // Define a unique slug for the add-on

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
        // Load the text domain for localization
        load_plugin_textdomain('import-yoast-seo', false, dirname(plugin_basename(__FILE__)) . '/languages');

        // Check for required classes and display an admin notice if not found
        if (!class_exists('PMXI_Plugin') || !class_exists('PMXI_RapidAddon')) {
            add_action('admin_notices', function() {
                echo '<div class="error notice"><p>' . esc_html__('The Yoast SEO Add-On requires WP All Import Pro to be installed and active.', 'import-yoast-seo') . '</p></div>';
            });
            return;
        }

        // Include the 'is_plugin_active' function if it doesn't exist
        if (!function_exists('is_plugin_active')) {
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }

        // Check if the Yoast SEO plugin is active
        if (!is_plugin_active('wordpress-seo/wp-seo.php') && !is_plugin_active('wordpress-seo-premium/wp-seo-premium.php')) {
            add_action('admin_notices', function() {
                echo '<div class="error notice"><p>' . esc_html__('The Yoast SEO Add-On requires the Yoast WordPress SEO plugin to be installed and active.', 'import-yoast-seo') . '</p></div>';
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
        $this->add_on->add_field('yoast_wpseo_title', esc_html__('SEO Title', 'import-yoast-seo'), 'text');
        $this->add_on->add_field('yoast_wpseo_metadesc', esc_html__('Meta Description', 'import-yoast-seo'), 'text');
        $this->add_on->add_field('yoast_wpseo_meta-robots-noindex', esc_html__('Meta Robots Index', 'import-yoast-seo'), 'radio', ['' => esc_html__('default', 'import-yoast-seo'), '1' => esc_html__('noindex', 'import-yoast-seo'), '2' => esc_html__('index', 'import-yoast-seo')]);
        $this->add_on->add_field('yoast_wpseo_opengraph-image', esc_html__('Facebook Image', 'import-yoast-seo'), 'image');
    }

    // Import function to handle the actual data import
    public function import($post_id, $data, $import_options, $article) {
        if (empty($article['ID']) || $this->add_on->can_update_meta('_yoast_wpseo_title', $import_options)) {
            update_post_meta($post_id, '_yoast_wpseo_title', sanitize_text_field($data['yoast_wpseo_title']));
        }

        if (empty($article['ID']) || $this->add_on->can_update_meta('_yoast_wpseo_metadesc', $import_options)) {
            update_post_meta($post_id, '_yoast_wpseo_metadesc', sanitize_text_field($data['yoast_wpseo_metadesc']));
        }

        if (empty($article['ID']) || $this->add_on->can_update_meta('_yoast_wpseo_meta-robots-noindex', $import_options)) {
            update_post_meta($post_id, '_yoast_wpseo_meta-robots-noindex', sanitize_text_field($data['yoast_wpseo_meta-robots-noindex']));
        }

        if ($this->add_on->can_update_image($import_options)) {
            $image_url = wp_get_attachment_url(sanitize_text_field($data['yoast_wpseo_opengraph-image']['attachment_id']));
            update_post_meta($post_id, '_yoast_wpseo_opengraph-image', esc_url_raw($image_url));
        }
    }
}

// Initialize the add-on
Yoast_SEO_Add_On::get_instance();
