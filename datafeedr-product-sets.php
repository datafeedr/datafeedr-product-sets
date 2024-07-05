<?php
/*
Plugin Name: Datafeedr Product Sets
Plugin URI: https://www.datafeedr.com
Description: Build sets of products to import into your website. <strong>REQUIRES: </strong><a href="http://wordpress.org/plugins/datafeedr-api/">Datafeedr API</a> and <a href="https://datafeedr.me/dfrpswc">WooCommerce Importer</a> plugins.
Author: datafeedr.com
Author URI: https://www.datafeedr.com
Text Domain: datafeedr-product-sets
License: GPL v3
Requires PHP: 7.4
Requires at least: 3.8
Tested up to: 6.6-RC2
Version: 1.3.23

Datafeedr Product Sets Plugin
Copyright (C) 2024, Datafeedr - help@datafeedr.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define constants.
 */
define( 'DFRPS_VERSION', '1.3.23' );
define( 'DFRPS_DB_VERSION', '1.2.0' );
define( 'DFRPS_SET_VERSION', '1.2.0' );
define( 'DFRPS_URL', plugin_dir_url( __FILE__ ) );
define( 'DFRPS_PATH', plugin_dir_path( __FILE__ ) );
define( 'DFRPS_BASENAME', plugin_basename( __FILE__ ) );
define( 'DFRPS_DOMAIN', 'datafeedr-product-sets' );
define( 'DFRPS_CPT', 'datafeedr-productset' );
define( 'DFRPS_PREFIX', 'dfrps' );

/**
 * Loads required function files.
 */
require_once( DFRPS_PATH . 'functions/functions.php' );
require_once( DFRPS_PATH . 'classes/class-datafeedr-plugin-dependency.php' );

/**
 * Import the post's image.
 *
 * Instantiate the Dfrps_Image_Importer class and pass a $post object
 * for image processing. This loads on all page loads so that images
 * will be uploaded even when on the frontend of the site.
 *
 * @param object $post A $post object for the post we want to import an image for.
 *
 * @since 1.1.10
 *
 */
function dfrps_import_image( $post ) {

//	_deprecated_function(
//		__FUNCTION__,
//		'1.2.22 of the Datafeedr Product Sets plugin',
//		'dfrps_import_product_thumbnail()'
//	);

	/**
	 * @since 1.2.22
	 */
	if ( function_exists( 'datafeedr_import_image' ) ) {

		$result = dfrps_import_post_thumbnail( $post->ID );

		if ( ! is_wp_error( $result ) && $result->has_error() ) {

			$error = array(
				'function' => __FUNCTION__,
				'$url'     => $result->url(),
				'$args'    => $result->args(),
				'$post'    => $post,
				'WP_Error' => $result->wp_error(),
			);

			dfrps_error_log( 'Error importing image' . ': ' . print_r( $error, true ) );
		}

		return;
	}

	if ( ! class_exists( 'Dfrps_Image_Importer' ) ) {
		require_once( DFRPS_PATH . 'classes/class-dfrps-image-importer.php' );
	}

	$registered_cpts = get_option( 'dfrps_registered_cpts', array() );

	$post_types = array_keys( $registered_cpts );

	if ( ! in_array( $post->post_type, $post_types ) ) {
		return;
	}

	if ( has_post_thumbnail( $post ) ) {
		return;
	}

	new Dfrps_Image_Importer( $post );
}

//add_action( 'the_post', 'dfrps_import_image' );

/**
 * Notify user that an Importer plugin is missing and is required.
 */
function dfrps_missing_importer() {
	if ( ! dfrps_registered_cpt_exists() ) {
		echo '<div class="notice notice-error"><p>';
		echo __( 'The <strong>Datafeedr Product Sets</strong> plugin requires an importer plugin.', 'datafeedr-product-sets' );
		echo ' <a href="https://datafeedr.me/dfrpswc">';
		echo __( 'Download the WooCommerce Importer plugin', 'datafeedr-product-sets' );
		echo '</a>.</p></div>';
	}
}

add_action( 'admin_notices', 'dfrps_missing_importer' );

/**
 * Notify user if a default CPT hasn't been selected.
 */
function dfrps_default_cpt_not_selected() {
	if ( ! dfrps_default_cpt_is_selected() ) {
		echo '<div class="notice notice-error"><p>';
		echo __( 'The <strong>Datafeedr Product Sets</strong> plugin requires you to', 'datafeedr-product-sets' );
		echo ' <a href="' . admin_url( 'admin.php?page=dfrps_configuration' ) . '">';
		echo __( 'select a Default Custom Post Type', 'datafeedr-product-sets' );
		echo '</a>.</p></div>';
	}
}

add_action( 'admin_notices', 'dfrps_default_cpt_not_selected' );

/**
 * Notify user that updates are disabled.
 */
function dfrps_updates_disabled() {
	$options = get_option( 'dfrps_configuration', array() );
	if ( isset( $options['updates_enabled'] ) && $options['updates_enabled'] == 'disabled' ) {
		echo '<div class="notice notice-error"><p>';
		echo __( 'The <strong>Datafeedr Product Sets</strong> plugin has disabled Product Set updates. Enable Product Set updates ', 'datafeedr-product-sets' );
		echo ' <a href="' . admin_url( 'admin.php?page=dfrps_configuration' ) . '">';
		echo __( 'here', 'datafeedr-product-sets' );
		echo '</a>.</p></div>';
	}
}

add_action( 'admin_notices', 'dfrps_updates_disabled' );

/**
 * Display admin notices for each required plugin that needs to be
 * installed, activated and/or updated.
 *
 * @since 1.2.24
 */
function dfrps_admin_notice_plugin_dependencies() {

	/**
	 * @var Dfrps_Plugin_Dependency[] $dependencies
	 */
	$dependencies = array(
		new Dfrps_Plugin_Dependency( 'Datafeedr API', 'datafeedr-api/datafeedr-api.php', '1.2.0' ),
		new Dfrps_Plugin_Dependency( 'Datafeedr WooCommerce Importer', 'datafeedr-woocommerce-importer/datafeedr-woocommerce-importer.php', '1.1.17', false ),
	);

	foreach ( $dependencies as $dependency ) {

		$action = $dependency->action_required();

		if ( ! $action ) {
			continue;
		}

		echo '<div class="notice notice-error"><p>';
		echo $dependency->msg( 'Datafeedr Product Sets' );
		echo $dependency->link();
		echo '</p></div>';
	}
}

add_action( 'admin_notices', 'dfrps_admin_notice_plugin_dependencies' );

/**
 * Notify user that "DISABLE_WP_CRON" is set to true.
 *
 * This will display a notice in the admin area of a user's WordPress site if the
 * DISABLE_WP_CRON constant is set to anything other than false.
 *
 * @since 1.2.14
 */
function dfrps_wp_cron_disabled() {

	if ( ! defined( 'DISABLE_WP_CRON' ) ) {
		return;
	}

	if ( false === DISABLE_WP_CRON ) {
		return;
	}

	$msg = 'Datafeedr Product Set updates cannot run because the WordPress Cron is disabled. Open your <code>wp-config.php</code> file and remove this line: <code>define(\'DISABLE_WP_CRON\', true );</code>.';

	echo '<div class="error dfrps_wp_cron_disabled_notice"><p>';
	echo '<strong>' . __( 'Warning:', 'datafeedr-product-sets' ) . '</strong> ';
	echo __( $msg, 'datafeedr-product-sets' );
	echo '</p></div>';
}

add_action( 'admin_notices', 'dfrps_wp_cron_disabled' );

/**
 * Upon plugin activation.
 */
register_activation_hook( __FILE__, 'dfrps_activate' );
function dfrps_activate( bool $network_wide ) {

	// Check that minimum WordPress requirement has been met.
	$version = get_bloginfo( 'version' );
	if ( version_compare( $version, '3.8', '<' ) ) {
		deactivate_plugins( DFRPS_BASENAME );
		wp_die( __(
			'The Datafeedr Product Sets Plugin could not be activated because it requires WordPress version 3.8 or greater. Please upgrade your installation of WordPress.',
			'datafeedr-product-sets'
		) );
	}

	// Check that plugin is not being activated at the Network level on Multisite sites.
	if ( $network_wide && is_multisite() ) {
		deactivate_plugins( DFRPS_BASENAME );
		wp_die( __(
			'The Datafeedr Product Sets plugin cannot be activated at the Network-level. Please activate the Datafeedr Product Sets plugin at the Site-level instead.',
			'datafeedr-product-sets'
		) );
	}

	dfrps_add_capabilities();

	// Add default options if they do not already exist. @since 1.2.1
	$dfrps_configuration = get_option( 'dfrps_configuration', false );
	if ( ! $dfrps_configuration ) {
		require_once( DFRPS_PATH . 'classes/class-dfrps-configuration.php' );
		$default_options = Dfrps_Configuration::default_options();
		add_option( 'dfrps_configuration', $default_options );
	}
}

/**
 * Add new capabilities to "administrator" role.
 */
function dfrps_add_capabilities() {
	$role = get_role( 'administrator' );
	$role->add_cap( 'edit_product_set' );
	$role->add_cap( 'read_product_set' );
	$role->add_cap( 'delete_product_set' );
	$role->add_cap( 'edit_product_sets' );
	$role->add_cap( 'edit_others_product_sets' );
	$role->add_cap( 'publish_product_sets' );
	$role->add_cap( 'read_private_product_sets' );
	$role->add_cap( 'delete_product_sets' );
	$role->add_cap( 'delete_private_product_sets' );
	$role->add_cap( 'delete_published_product_sets' );
	$role->add_cap( 'delete_others_product_sets' );
	$role->add_cap( 'edit_private_product_sets' );
	$role->add_cap( 'edit_published_product_sets' );
	$role->add_cap( 'edit_product_sets' );
}

/**
 * Build CPT
 */
add_action( 'init', 'dfrps_create_post_type' );
function dfrps_create_post_type() {

	$labels = array(
		'name'               => _x( 'Product Sets', 'datafeedr-product-sets' ),
		'singular_name'      => _x( 'Product Set', 'datafeedr-product-sets' ),
		'add_new'            => _x( 'Add New Product Set', 'datafeedr-product-sets' ),
		'all_items'          => _x( 'All Product Sets', 'datafeedr-product-sets' ),
		'add_new_item'       => _x( 'Add New Product Set', 'datafeedr-product-sets' ),
		'edit_item'          => _x( 'Edit Product Set', 'datafeedr-product-sets' ),
		'new_item'           => _x( 'New Product Set', 'datafeedr-product-sets' ),
		'view_item'          => _x( 'View Product Set', 'datafeedr-product-sets' ),
		'search_items'       => _x( 'Search Product Sets', 'datafeedr-product-sets' ),
		'not_found'          => _x( 'No Product Sets found', 'datafeedr-product-sets' ),
		'not_found_in_trash' => _x( 'No Product Sets found in trash', 'datafeedr-product-sets' ),
		'parent_item_colon'  => _x( 'Parent Product Set:', 'datafeedr-product-sets' ),
		'menu_name'          => _x( 'Product Sets', 'datafeedr-product-sets' )
	);

	$args = array(
		'labels'              => $labels,
		'description'         => "These store saved searches and individual products as product sets.",
		'public'              => true,
		'exclude_from_search' => true,
		'publicly_queryable'  => false,
		'show_ui'             => true,
		'show_in_nav_menus'   => false,
		'show_in_menu'        => 'dfrps',
		'show_in_admin_bar'   => true,
		'menu_position'       => 20,
		'menu_icon'           => null,
		'capability_type'     => 'product_set',
		'map_meta_cap'        => true,
		'hierarchical'        => true,
		'supports'            => array( 'title' ),
		'has_archive'         => false,
		'rewrite'             => false,
		'query_var'           => false,
		'can_export'          => true
	);

	register_post_type( DFRPS_CPT, $args );
}

/**
 * Load files only if we're in the admin section of the site.
 */
if ( is_admin() ) {
	if ( defined( 'DFRAPI_BASENAME' ) ) {
		require_once( DFRPS_PATH . 'classes/class-dfrps-initialize.php' );
	}
}

/**
 * Returns true if plugin is installed, else returns false.
 *
 * @param string $plugin_file Plugin file name formatted like: woocommerce/woocommerce.php
 *
 * @return bool
 */
function dfrps_plugin_is_installed( $plugin_file ) {
	$file_name = plugin_dir_path( __DIR__ ) . $plugin_file;

	return ( file_exists( $file_name ) );
}
