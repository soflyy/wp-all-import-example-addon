<?php

/*
Plugin Name: WP All Import Yoast SEO Add-On
Description: A complete example add-on for importing data to certain Yoast SEO fields.
Version: 1.0
Author: WP All Import
*/


include "rapid-addon.php";

$yoast_seo_addon = new RapidAddon('Yoast SEO Add-On', 'yoast_seo_addon');

$yoast_seo_addon->add_field('yoast_wpseo_title', 'SEO Title', 'text');
$yoast_seo_addon->add_field('yoast_wpseo_metadesc', 'Meta Description', 'text');

$yoast_seo_addon->add_field('yoast_wpseo_meta-robots-noindex', 'Meta Robots Index', 'radio', array('' => 'default', '1' => 'noindex', '2' => 'index'));

$yoast_seo_addon->add_field('yoast_wpseo_opengraph-image', 'Facebook Image', 'image');

$yoast_seo_addon->set_import_function('yoast_seo_addon_import');

// admin notice if WPAI and/or Yoast isn't installed

if (function_exists('is_plugin_active')) {

	// display this notice if neither the free or pro version of the Yoast plugin is active.
	if ( !is_plugin_active( "wordpress-seo/wp-seo.php" ) && !is_plugin_active( "wordpress-seo-premium/wp-seo-premium.php" ) ) {

		// Specify a custom admin notice.
		$yoast_addon->admin_notice(
			'The Yoast WordPress SEO Add-On requires WP All Import <a href="http://wordpress.org/plugins/wp-all-import" target="_blank">Free</a> and the <a href="https://yoast.com/wordpress/plugins/seo/">Yoast WordPress SEO</a> plugin.'
		);
	}

	// only run this add-on if the free or pro version of the Yoast plugin is active.
	if ( is_plugin_active( "wordpress-seo/wp-seo.php" ) || is_plugin_active( "wordpress-seo-premium/wp-seo-premium.php" ) ) {
		
		$yoast_addon->run();
		
	}
}

// the add-on will run for all themes/post types if no arguments are passed to run()
$yoast_seo_addon->run(); 


function yoast_seo_addon_import($post_id, $data, $import_options) {

	global $yoast_seo_addon;

	if ($yoast_seo_addon->can_update_meta('_yoast_wpseo_title', $import_options)) {
		update_post_meta($post_id, '_yoast_wpseo_title', $data['yoast_wpseo_title']);
	}

	if ($yoast_seo_addon->can_update_meta('_yoast_wpseo_metadesc', $import_options)) {
		update_post_meta($post_id, '_yoast_wpseo_metadesc', $data['yoast_wpseo_metadesc']);
	}

	if ($yoast_seo_addon->can_update_meta('_yoast_wpseo_meta-robots-noindex', $import_options)) {
		update_post_meta($post_id, '_yoast_wpseo_meta-robots-noindex', $data['yoast_wpseo_meta-robots-noindex']);
	}


	if ($yoast_seo_addon->can_update_image($import_options)) {
		$image_url = wp_get_attachment_url($data['yoast_wpseo_opengraph-image']['attachment_id']);
		update_post_meta($post_id, '_yoast_wpseo_opengraph-image', $image_url);
	}

}