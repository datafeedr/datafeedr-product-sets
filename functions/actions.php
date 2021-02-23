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

	$url = dfrps_featured_image_url( $post_id );

	$product_id = $post_id;

	$product = wc_get_product( $product_id );

	if ( ! $product ) {
		return new WP_Error( 'invalid_product_id', 'The WooCommerce Product ID "' . $product_id . '" is invalid.' );
	}

	$image_data = new Dfrapi_Image_Data( $url );

	$image_data->set_title( $product->get_name() );
	$image_data->set_filename( $product->get_name() );
	$image_data->set_description( $product->get_name() );
	$image_data->set_caption( $product->get_name() );
	$image_data->set_alternative_text( $product->get_name() );
	$image_data->set_author_id( get_post_field( 'post_author', $product->get_id() ) );
	$image_data->set_post_parent_id( $product->get_id() );

	$image_data = apply_filters( 'dfr_import_featured_image_post_data', $image_data, $product );

	$uploader = new Dfrapi_Image_Uploader( $image_data );

	$attachment_id = $uploader->upload();

	if ( is_wp_error( $attachment_id ) ) {
		return $attachment_id;
	}

	// Update '_source_plugin' meta field.
	update_post_meta( $attachment_id, '_owner_datafeedr', 'dfrps' );

	echo '<pre>$attachment_id: ';
	print_r( $attachment_id );
	echo '</pre>';

	return $attachment_id;


	$result = dfrps_import_post_thumbnail( $post_id );

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

add_action( 'dfrapi_as_dfrps_import_product_image', 'dfrps_import_product_image_action', 200 );


//add_action( 'admin_notices', 'test_new_image_import_code' );
function test_new_image_import_code() {

	$urls = [
		'https://images.asos-media.com/products/new-look-snake-print-v-shaped-bikini-bottoms-in-bright-yellow/11880433-1-brightyellow?$XXLrmbnrbtm$',
		'https://www.rei.com/media/3c8c2c5f-5c2c-4319-b536-1a9caefb8514',
		'https://www.patagonia.com/dw/image/v2/BDJB_PRD/on/demandware.static/-/Sites-patagonia-master/default/dwa72917ec/images/hi-res/11193_950.jpg?sw=1000&sh=1000&sfrm=png&q=95&bgcolor=f6f6f6',
	];

	$url = $urls[ mt_rand( 0, ( count( $urls ) - 1 ) ) ];


	$product_id = 18907;

	$product = wc_get_product( $product_id );

	if ( ! $product ) {

		echo '<pre>$product: ';
		print_r( $product );
		echo '</pre>';

		return new WP_Error( 'invalid_product_id', 'The WooCommerce Product ID "' . $product_id . '" is invalid.' );
	}

	$image_data = new Dfrapi_Image_Data( $url );

	// @todo test empty data.
	$image_data->set_title( $product->get_name() );
	$image_data->set_filename( $product->get_name() );
	$image_data->set_description( $product->get_name() );
	$image_data->set_caption( $product->get_name() );
	$image_data->set_alternative_text( $product->get_name() );
	$image_data->set_author_id( get_post_field( 'post_author', $product->get_id() ) );
	$image_data->set_post_parent_id( $product->get_id() );

	$image_data = apply_filters( 'dfr_import_featured_image_post_data', $image_data, $product );

	$uploader = new Dfrapi_Image_Uploader( $image_data );

	$attachment_id = $uploader->upload();

	if ( is_wp_error( $attachment_id ) ) {

		echo '<pre>$attachment_id: ';
		print_r( $attachment_id );
		echo '</pre>';

		return $attachment_id;
	}

	// Update '_source_plugin' meta field.
	update_post_meta( $attachment_id, '_owner_datafeedr', 'dfrps' );

	echo '<pre>$attachment_id: ';
	print_r( $attachment_id );
	echo '</pre>';
}



