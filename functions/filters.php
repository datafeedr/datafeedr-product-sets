<?php

/**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds new actions to the Bulk Actions select menu on WordPress Admin Area > Product Sets page.
 *
 * @since 1.2.33
 *
 * @param array $bulk_actions
 *
 * @return array
 */
function dfrps_bulk_actions( $bulk_actions ) {

	if ( current_user_can( 'manage_options' ) ) {
		$bulk_actions['dfrps_bulk_bump']          = __( 'Bump', 'datafeedr-product-sets' );
		$bulk_actions['dfrps_bulk_bump_priority'] = __( 'Bump (with priority)', 'datafeedr-product-sets' );
	}

	return $bulk_actions;
}

add_filter( 'bulk_actions-edit-datafeedr-productset', 'dfrps_bulk_actions' );

/**
 * Handles "Bumping" Product Sets so that their update time is set to Now.
 *
 * @since 1.2.33
 *
 * @param string $redirect_to
 * @param string $doaction
 * @param array $post_ids
 *
 * @return string
 */
function dfrps_handle_dfrps_bulk_bump( $redirect_to, $doaction, $post_ids ) {

	if ( ! current_user_can( 'manage_options' ) ) {
		return $redirect_to;
	}

	if ( $doaction !== 'dfrps_bulk_bump' ) {
		return $redirect_to;
	}

	foreach ( $post_ids as $post_id ) {
		update_post_meta( $post_id, '_dfrps_cpt_next_update_time', date_i18n( 'U' ) );
	}

	return add_query_arg( 'dfrps_bulk_bumped', count( $post_ids ), $redirect_to );
}

add_filter( 'handle_bulk_actions-edit-datafeedr-productset', 'dfrps_handle_dfrps_bulk_bump', 10, 3 );

/**
 * Handles priority "Bumping" Product Sets so that their update time is set to 1 year in the past
 *
 * @since 1.2.33
 *
 * @param string $redirect_to
 * @param string $doaction
 * @param array $post_ids
 *
 * @return string
 */
function dfrps_handle_dfrps_bulk_bump_with_priority( $redirect_to, $doaction, $post_ids ) {

	if ( ! current_user_can( 'manage_options' ) ) {
		return $redirect_to;
	}

	if ( $doaction !== 'dfrps_bulk_bump_priority' ) {
		return $redirect_to;
	}

	foreach ( $post_ids as $post_id ) {
		update_post_meta( $post_id, '_dfrps_cpt_next_update_time', date_i18n( 'U', strtotime( '-1 year' ) ) );
	}

	return add_query_arg( 'dfrps_bulk_bumped_with_priority', count( $post_ids ), $redirect_to );
}

add_filter( 'handle_bulk_actions-edit-datafeedr-productset', 'dfrps_handle_dfrps_bulk_bump_with_priority', 10, 3 );
