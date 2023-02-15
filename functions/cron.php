<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * The interval (in seconds) in which the system
 * should check for product sets which need to be updated.
 *
 * By default: 1 minute (60 seconds)
 */
function dfrps_default_cron_interval() {
	$configuration = get_option( 'dfrps_configuration', array() );
	if ( ! empty( $configuration ) ) {
		return $configuration['cron_interval'];
	} else {
		return 60;
	}
}

/**
 * Add a new interval for DFRPS to the cron schedules.
 */
add_filter( 'cron_schedules', 'dfrps_cron_schedules' );
function dfrps_cron_schedules( $array ) {
    $array['dfrps_schedule'] = array(
		'interval' 	=> dfrps_default_cron_interval(),
		'display' 	=> __( 'DFRPS Cron Schedule', 'datafeedr-product-sets' ),
    );
    return $array;
}

/**
 * Schedule DFRPS cron event, but just once.
 */
if ( ! wp_next_scheduled( 'dfrps_cron' ) ) {
	wp_schedule_event( time(), 'dfrps_schedule', 'dfrps_cron' );
}

/**
 * Query product sets and run update if needed.
 */
add_action( 'dfrps_cron', 'dfrps_get_product_set_to_update' );
function dfrps_get_product_set_to_update() {

	// Check if an update is already running.
	$use_cache    = wp_using_ext_object_cache( false );
	$doing_update = get_transient( 'dfrps_doing_update' );
	$doing_update = apply_filters( 'dfrps_doing_update', $doing_update );
	wp_using_ext_object_cache( $use_cache );
	if ( $doing_update !== false ) {
		return true;
	}

	// Check that updates are enabled.
	$options = get_option( 'dfrps_configuration', array() );
	if ( $options['updates_enabled'] == 'disabled' ) {
		return true;
	}

	// Check that at least 1 network is selected.
	$networks = (array) get_option( 'dfrapi_networks' );
	if ( empty( $networks['ids'] ) ) {
		return true;
	}

	// Check that at least 1 merchant is selected.
	$merchants = (array) get_option( 'dfrapi_merchants' );
	if ( empty( $merchants['ids'] ) ) {
		return true;
	}

	// Return if no CPTs exist to import into.
	$registered_cpts = get_option( 'dfrps_registered_cpts', array() );
	if ( empty( $registered_cpts ) ) {
		return true;
	} else {
		$cpts     = array_keys( $registered_cpts );
		$sql_cpts = implode( "','", $cpts );
	}

	// Check that DFRPSWC importer is compatible.
	if ( defined( 'DFRPSWC_VERSION' ) ) {
		if ( version_compare( DFRPSWC_VERSION, '1.2.0', '<' ) ) {
			return true;
		}
	}

	global $wpdb;

	$post = $wpdb->get_row(
		"

		SELECT 
			p.*,
			update_phase.meta_value AS update_phase,
			next_update.meta_value AS next_update,
			cpt_type.meta_value AS cpt_type

		FROM $wpdb->posts p

		LEFT JOIN $wpdb->postmeta AS update_phase 
			ON p.ID = update_phase.post_id
			AND update_phase.meta_key = '_dfrps_cpt_update_phase'

		LEFT JOIN $wpdb->postmeta AS next_update 
			ON p.ID = next_update.post_id
			AND next_update.meta_key = '_dfrps_cpt_next_update_time'

		LEFT JOIN $wpdb->postmeta AS cpt_type 
			ON p.ID = cpt_type.post_id
			AND cpt_type.meta_key = '_dfrps_cpt_type'

		WHERE p.post_type = '" . DFRPS_CPT . "'
		AND (
			p.post_status = 'publish'
			OR 
			p.post_status = 'trash'
		)
		AND cpt_type.meta_value IN ('" . $sql_cpts . "')

		ORDER BY 
			CAST(update_phase.meta_value AS UNSIGNED) DESC,
			CAST(next_update.meta_value AS UNSIGNED) ASC

		LIMIT 1

	",
		ARRAY_A
	);

	// Return if no $post exists.
	if ( is_null( $post ) ) {
		return true;
	}

	// Make sure this product set is version 1.2.0 or greater.
	dfrps_upgrade_product_set_to_120( $post );

	$post = apply_filters( 'dfrps_cron_before_delete_or_update', $post );

	/**
	 * First check if post_status is 'trash'. Trashed sets
	 * get priority as we need to remove those products
	 * from the store ASAP.
	 */
	if ( $post['post_status'] == 'trash' && $post['next_update'] <= date_i18n( 'U' ) ) {

		require_once DFRPS_PATH . 'classes/class-dfrps-delete.php';
		new Dfrps_Delete( $post );
		return true;
	}

	/*
	 * If a Product Set is currently in an update phase
	 * or, if a Product Set's next update time is now
	 * or, if a Product Set's next update time is 0
	 * then, run the update.
	 */
	if (
		$post['post_status'] == 'publish' &&
		(
			$post['update_phase'] > 0 ||
			$post['next_update'] <= date_i18n( 'U' ) ||
			$post['next_update'] == 0
		) ) {

		if ( isset( $post['ID'] ) && ( $post['ID'] > 0 ) ) {

			$post['registered_cpts'] = $registered_cpts;

			// Update Product Set.
			require_once DFRPS_PATH . 'classes/class-dfrps-update.php';
			new Dfrps_Update( $post );
			return true;
		}
	}
	return true;
}

/**
 * Remove cron schedule if plugin is deactivated.
 */
register_deactivation_hook( __FILE__, 'dfrps_deactivate_cron' );
function dfrps_deactivate_cron() {
	wp_clear_scheduled_hook( 'dfrps_cron' );
}
