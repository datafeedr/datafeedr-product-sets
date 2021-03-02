<?php

/**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Displays admin notice after Product Sets have been bumped.
 *
 * @since 1.2.33
 */
function dfrps_bulk_bump_admin_notice() {

	$query_param = 'dfrps_bulk_bumped';

	if ( ! isset( $_REQUEST[ $query_param ] ) || empty( $_REQUEST[ $query_param ] ) ) {
		return;
	}

	$count = absint( $_REQUEST[ $query_param ] );

	$format  = '<div id="message"  class="updated notice notice-success is-dismissible"><p>%s</p></div>';
	$message = __( 'Successfully bumped ', 'datafeedr-product-sets' );
	$message .= sprintf( _n( '%s Product Set', '%s Product Sets', $count, 'datafeedr-product-sets' ), $count ) . '.';

	printf( $format, $message );
}

add_action( 'admin_notices', 'dfrps_bulk_bump_admin_notice' );

/**
 * Displays admin notice after Product Sets have been bumped with priority.
 *
 * @since 1.2.33
 */
function dfrps_bulk_bump_priority_admin_notice() {

	$query_param = 'dfrps_bulk_bumped_with_priority';

	if ( ! isset( $_REQUEST[ $query_param ] ) || empty( $_REQUEST[ $query_param ] ) ) {
		return;
	}

	$count = absint( $_REQUEST[ $query_param ] );

	$format  = '<div id="message"  class="updated notice notice-success is-dismissible"><p>%s</p></div>';
	$message = __( 'Successfully bumped with priority ', 'datafeedr-product-sets' );
	$message .= sprintf( _n( '%s Product Set', '%s Product Sets', $count, 'datafeedr-product-sets' ), $count ) . '.';

	printf( $format, $message );
}

add_action( 'admin_notices', 'dfrps_bulk_bump_priority_admin_notice' );

/**
 * Schedule an image to be imported via the ActionScheduler plugin.
 *
 * @param array $post Array containing WordPress Post information.
 * @param array $product Array containing Datafeedr Product information.
 * @param array $set Array containing Product Set information.
 * @param string $action Either "update" or "insert" depending on what the Product Set is doing.
 */
function dfrps_schedule_image_import( $post, $product, $set, $action ) {

	if ( ! function_exists( 'dfrapi_schedule_async_action' ) ) {
		dfrps_error_log( 'The dfrapi_schedule_async_action() function does not exist. Please upgrade your Datafeedr API plugin to the latest version.' );
	}

	$post_id = absint( $post['ID'] );

	$do_import_thumbnail = dfrps_do_import_product_thumbnail( $post_id );

	if ( is_wp_error( $do_import_thumbnail ) ) {
		dfrps_error_log( 'Skipping image import' . ': ' . print_r( $do_import_thumbnail, true ) );

		return;
	}

	dfrapi_schedule_async_action( 'dfrps_import_product_image', compact( 'post_id' ), 'dfrps' );
}

add_action( 'dfrpswc_do_product', 'dfrps_schedule_image_import', 10, 4 );

/**
 * The action the ActionScheduler will call in order to import the image for $post_id.
 *
 * @param int $post_id
 *
 * @throws Exception
 */
function dfrps_import_product_image_action( $post_id ) {

	$result = dfrps_import_post_thumbnail( $post_id );

	if ( function_exists( 'dfrapi_use_legacy_image_importer' ) && dfrapi_use_legacy_image_importer() === false ) {

		if ( is_wp_error( $result ) ) {
			dfrps_error_log( 'Unable to import image' . ': ' . print_r( $result, true ) );

			$message = sprintf(
				__( 'Image import failed. PRODUCT: "%s" ID: %d ERROR: %s', 'datafeedr-product-sets' ),
				esc_html( get_the_title( $post_id ) ),
				absint( $post_id ),
				esc_html( $result->get_error_message() )
			);

			throw new Exception( $message );
		}

		return;
	}

	if ( is_wp_error( $result ) ) {
		dfrps_error_log( 'Unable to import image' . ': ' . print_r( $result, true ) );
	}

	if ( ! is_wp_error( $result ) && $result->has_error() ) {

		$error = array(
			'function' => __FUNCTION__,
			'$url'     => $result->url(),
			'$args'    => $result->args(),
			'$post_id' => $post_id,
			'WP_Error' => $result->wp_error(),
		);

		dfrps_error_log( 'Error importing image' . ': ' . print_r( $error, true ) );

		$message = sprintf(
			__( 'Image import failed. PRODUCT: "%s" ID: %d URL: %s ERROR: %s', 'datafeedr-product-sets' ),
			get_the_title( $post_id ),
			$post_id,
			$result->url(),
			$result->wp_error()->get_error_message()
		);

		throw new Exception( $message );
	}
}

add_action( 'dfrapi_as_dfrps_import_product_image', 'dfrps_import_product_image_action' );
