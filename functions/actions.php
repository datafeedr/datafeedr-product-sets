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