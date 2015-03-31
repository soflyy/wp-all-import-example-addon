<?php

/*
Plugin Name: Property Master Add-On
Description: A complete example add-on for the fictional "Property Master" theme. Runs when importing to "property_listing" Custom Post Type.
Version: 1.0
Author: WP All Import
*/


include "rapid-addon.php";

$property_master_addon = new RapidAddon('Property Master Add-On', 'property_master_addon');

$property_master_addon->add_field('property_address', 'Street Address', 'text');
$property_master_addon->add_field('property_city_state_zip', 'City State Zip', 'text');

$property_master_addon->add_field('property_type', 'Property Type', 'radio', array('rent' => 'For Rent', 'buy' => 'For Sale'));

$property_master_addon->add_field('property_image', 'Property Image', 'image');

$property_master_addon->set_import_function('property_master_addon_import');

$property_master_addon->run(
	array(
		"post_types" => array("property_listing")
	)
);


function property_master_addon_import($post_id, $data, $import_options) {

	global $property_master_addon;

	if ($property_master_addon->can_update_meta('_property_address', $import_options)) {
		update_post_meta($post_id, '_property_address', $data['property_address']);
	}

	if ($property_master_addon->can_update_meta('_property_city_state_zip', $import_options)) {
		update_post_meta($post_id, '_property_city_state_zip', $data['property_city_state_zip']);
	}

	if ($property_master_addon->can_update_meta('_property_type', $import_options)) {
		update_post_meta($post_id, '_property_type', $data['property_type']);
	}

	if ($property_master_addon->can_update_image($import_options)) {
		set_post_thumbnail($post_id, $data['property_image']['attachment_id']);
	}

	// get the latitude/longitude coordinates from Google Maps, based on the specified address
	if ($property_master_addon->can_update_meta('_address_lat_long', $import_options)) {

		$combined_address = $data['property_address'].' '.$data['property_city_state_zip'];

		$geocode = file_get_contents("geocode url");

		update_post_meta($post_id, '_address_lat_long', $geocode);

	}

}