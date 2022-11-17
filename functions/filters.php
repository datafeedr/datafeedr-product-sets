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
 * @param array $bulk_actions
 *
 * @return array
 * @since 1.2.33
 *
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
 * @param string $redirect_to
 * @param string $doaction
 * @param array $post_ids
 *
 * @return string
 * @since 1.2.33
 *
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
 * @param string $redirect_to
 * @param string $doaction
 * @param array $post_ids
 *
 * @return string
 * @since 1.2.33
 *
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

/**
 * If this Product Set has a custom update schedule, use that schedule instead of the default set on Configuration page.
 *
 * @param int $next_update_time
 * @param array $product_set
 *
 * @return int
 */
function dfrps_update_next_update_time_if_custom_schedule_exists_for_this_product_set( int $next_update_time, array $product_set ): int {

	$custom_schedule = (array) get_post_meta( $product_set['ID'], 'dfrps_custom_update_schedule', true );

	// If no custom schedule has been activated, return the default $next_update_time.
	if ( $custom_schedule['active'] !== true ) {
		return $next_update_time;
	}

	// If the interval type is not selected, we don't have enough data to know what to do with. Return $next_update_time.
	if ( ! isset( $custom_schedule['interval_type'] ) ) {
		return $next_update_time;
	}

	// If the interval type does not match the possible values, return $next_update_time.
	if ( ! in_array( $custom_schedule['interval_type'], [ 'day_of_week', 'day_of_month' ], true ) ) {
		return $next_update_time;
	}

	$custom_update_time = $custom_schedule['time_of_day'] ?? '00:00:00';

	// Get current day's data.
	$now_timestamp    = (int) date_i18n( 'U' );
	$day_of_week_now  = (int) date_i18n( 'w' );
	$day_of_month_now = (int) date_i18n( 'j' );

	$custom_schedule['day_of_week'] = [
		'd0' => false, // Sunday
		'd1' => true, // Monday
		'd2' => false, // Tuesday
		'd3' => true, // Wednesday
		'd4' => false, // Thursday
		'd5' => true, // Friday
		'd6' => false, // Saturday
	];

	if ( $custom_schedule['interval_type'] === 'day_of_week' ) {

		$days_of_week_to_update = [];

		foreach ( $custom_schedule['day_of_week'] as $day => $active ) {
			if ( $active ) {
				$days_of_week_to_update[] = absint( str_replace( 'd', '', $day ) );
			}
		}

		if ( empty( $days_of_week_to_update ) ) {
			return $next_update_time;
		}

		sort( $days_of_week_to_update );

		$potential_next_update_times = [];

		for ( $i = 0; $i <= 1; $i ++ ) {
			foreach ( $days_of_week_to_update as $day_of_week ) {
				$potential_next_update_times = strtotime( 'this week on wednesday at 11:00' );

			}
		}

		// Get the next day of the week to update.


	}


	return $next_update_time;
}

add_filter( 'dfrps_cpt_next_update_time', 'dfrps_update_next_update_time_if_custom_schedule_exists_for_this_product_set', 10, 2 );

/**
 * Add Datafeedr Product Set Plugin's settings and configuration to the WordPress
 * Site Health Info section (WordPress Admin Area > Tools > Site Health).
 *
 * @return array
 */
add_filter( 'debug_information', function ( $info ) {

	global $wpdb;

	$options = get_option( 'dfrps_configuration', Dfrps_Configuration::default_options() );

	$update_intervals = [
		'-1' => __( 'Continuous', 'datafeedr-product-sets' ),
		'1'  => __( 'Every Day', 'datafeedr-product-sets' ),
		'2'  => __( 'Every 2 Days', 'datafeedr-product-sets' ),
		'3'  => __( 'Every 3 Days', 'datafeedr-product-sets' ),
		'4'  => __( 'Every 4 Days', 'datafeedr-product-sets' ),
		'5'  => __( 'Every 5 Days', 'datafeedr-product-sets' ),
		'6'  => __( 'Every 6 Days', 'datafeedr-product-sets' ),
		'7'  => __( 'Every 7 Days', 'datafeedr-product-sets' ),
		'10' => __( 'Every 10 Days', 'datafeedr-product-sets' ),
		'14' => __( 'Every 14 Days', 'datafeedr-product-sets' ),
		'21' => __( 'Every 21 Days', 'datafeedr-product-sets' ),
		'30' => __( 'Every 30 Days', 'datafeedr-product-sets' ),
		'45' => __( 'Every 45 Days', 'datafeedr-product-sets' ),
		'60' => __( 'Every 60 Days', 'datafeedr-product-sets' ),
	];

	$update_interval = isset( $options['update_interval'] ) && ! empty( $options['update_interval'] )
		? $update_intervals[ $options['update_interval'] ]
		: $update_intervals['7'];

	$cron_intervals = [
		'10'   => __( 'Every 10 seconds', 'datafeedr-product-sets' ),
		'30'   => __( 'Every 30 seconds', 'datafeedr-product-sets' ),
		'60'   => __( 'Every minute', 'datafeedr-product-sets' ),
		'120'  => __( 'Every 2 minutes', 'datafeedr-product-sets' ),
		'300'  => __( 'Every 5 minutes', 'datafeedr-product-sets' ),
		'600'  => __( 'Every 10 minutes', 'datafeedr-product-sets' ),
		'900'  => __( 'Every 15 minutes', 'datafeedr-product-sets' ),
		'1800' => __( 'Every 30 minutes', 'datafeedr-product-sets' ),
		'3600' => __( 'Every hour', 'datafeedr-product-sets' ),
	];

	$cron_interval = isset( $options['cron_interval'] ) && ! empty( $options['cron_interval'] )
		? $cron_intervals[ $options['cron_interval'] ]
		: $cron_intervals['60'];

	$total_product_sets = wp_count_posts( DFRPS_CPT );
	$total_products     = wp_count_posts( 'product' );

	/**
	 * SELECT p.post_status, count(*)
	 * FROM wp_postmeta as pm
	 * INNER JOIN wp_posts as p
	 * ON p.ID = pm.post_id
	 * WHERE pm.meta_key = '_dfrps_is_dfrps_product'
	 * GROUP BY p.post_status
	 */
	$dfrps_products = $wpdb->get_results( "SELECT p.post_status as status, count(*) as total FROM $wpdb->postmeta as pm INNER JOIN $wpdb->posts as p ON p.ID = pm.post_id WHERE pm.meta_key = '_dfrps_is_dfrps_product' GROUP BY p.post_status" );

	$total_dfrps_products = [
		'publish' => 0,
		'draft'   => 0,
		'trash'   => 0,
	];

	foreach ( $dfrps_products as $key => $counts ) {
		$total_dfrps_products[ $counts->status ] = $counts->total;
	}

	/**
	 * SELECT p.ID as id, p.post_title as title, pm.meta_value as phase
	 * FROM wp_postmeta as pm
	 * INNER JOIN wp_posts as p
	 * ON p.ID = pm.post_id
	 * WHERE pm.meta_key = '_dfrps_cpt_update_phase'
	 * AND pm.meta_value > 0
	 */
	$currently_updating = $wpdb->get_row( "SELECT p.ID as id, p.post_title as title, pm.meta_value as phase FROM $wpdb->postmeta as pm INNER JOIN $wpdb->posts as p ON p.ID = pm.post_id WHERE pm.meta_key = '_dfrps_cpt_update_phase' AND pm.meta_value > 0" );

	$info['datafeedr-product-sets-plugin'] = [
		'label'       => __( 'Datafeedr Product Sets Plugin', 'datafeedr-product-sets' ),
		'description' => '',
		'fields'      => [
			'currently_updating'           => [
				'label' => __( 'Currently Updating', 'datafeedr-product-sets' ),
				'value' => isset( $currently_updating->title ) ? $currently_updating->title . ' / ID: ' . $currently_updating->id . ' / PHASE: ' . $currently_updating->phase : '—',
			],
			'product_sets_publish'         => [
				'label' => __( 'Product Sets (publish)', 'datafeedr-product-sets' ),
				'value' => (string) $total_product_sets->publish,
			],
			'product_sets_draft'           => [
				'label' => __( 'Product Sets (draft)', 'datafeedr-product-sets' ),
				'value' => (string) $total_product_sets->draft,
			],
			'product_sets_trash'           => [
				'label' => __( 'Product Sets (trash)', 'datafeedr-product-sets' ),
				'value' => (string) $total_product_sets->trash,
			],
			'woocommerce_products_publish' => [
				'label' => __( 'WC Products (publish)', 'datafeedr-product-sets' ),
				'value' => (string) $total_products->publish,
			],
			'woocommerce_products_draft'   => [
				'label' => __( 'WC Products (draft)', 'datafeedr-product-sets' ),
				'value' => (string) $total_products->draft,
			],
			'woocommerce_products_trash'   => [
				'label' => __( 'WC Products (trash)', 'datafeedr-product-sets' ),
				'value' => (string) $total_products->trash,
			],
			'dfrps_products_publish'       => [
				'label' => __( 'DFRPS Products (publish)', 'datafeedr-product-sets' ),
				'value' => (string) $total_dfrps_products['publish'],
			],
			'dfrps_products_draft'         => [
				'label' => __( 'DFRPS Products (draft)', 'datafeedr-product-sets' ),
				'value' => (string) $total_dfrps_products['draft'],
			],
			'dfrps_products_trash'         => [
				'label' => __( 'DFRPS Products (trash)', 'datafeedr-product-sets' ),
				'value' => (string) $total_dfrps_products['trash'],
			],
			'num_products_per_search'      => [
				'label' => __( 'Products per Search', 'datafeedr-product-sets' ),
				'value' => isset( $options['num_products_per_search'] ) && ! empty( $options['num_products_per_search'] ) ? $options['num_products_per_search'] : '—',
			],
			'default_filters'              => [
				'label' => __( 'Default Search Setting', 'datafeedr-product-sets' ),
				'value' => isset( $options['default_filters'] ) && ! empty( $options['default_filters'] ) ? serialize( $options['default_filters'] ) : '—',
			],
			'default_cpt'                  => [
				'label' => __( 'Default Custom Post Type', 'datafeedr-product-sets' ),
				'value' => isset( $options['default_cpt'] ) && ! empty( $options['default_cpt'] ) ? $options['default_cpt'] : '—',
			],
			'delete_missing_products'      => [
				'label' => __( 'Delete Missing Products', 'datafeedr-product-sets' ),
				'value' => isset( $options['delete_missing_products'] ) && ! empty( $options['delete_missing_products'] ) ? ucfirst( $options['delete_missing_products'] ) : '—',
				'debug' => isset( $options['delete_missing_products'] ) && ! empty( $options['delete_missing_products'] ) ? $options['delete_missing_products'] : '—',
			],
			'updates_enabled'              => [
				'label' => __( 'Updates', 'datafeedr-product-sets' ),
				'value' => isset( $options['updates_enabled'] ) && ! empty( $options['updates_enabled'] ) ? ucfirst( $options['updates_enabled'] ) : '—',
				'debug' => isset( $options['updates_enabled'] ) && ! empty( $options['updates_enabled'] ) ? $options['updates_enabled'] : '—',
			],
			'disable_updates_when_missing_merchants'      => [
				'label' => __( 'Disable Updates When Missing Merchants', 'datafeedr-product-sets' ),
				'value' => isset( $options['disable_updates_when_missing_merchants'] ) && ! empty( $options['disable_updates_when_missing_merchants'] ) ? ucfirst( $options['disable_updates_when_missing_merchants'] ) : '—',
				'debug' => isset( $options['disable_updates_when_missing_merchants'] ) && ! empty( $options['disable_updates_when_missing_merchants'] ) ? $options['disable_updates_when_missing_merchants'] : '—',
			],
			'update_interval'              => [
				'label' => __( 'Update Interval', 'datafeedr-product-sets' ),
				'value' => $update_interval,
				'debug' => isset( $options['update_interval'] ) && ! empty( $options['update_interval'] ) ? $options['update_interval'] : '—',
			],
			'cron_interval'                => [
				'label' => __( 'Cron Interval', 'datafeedr-product-sets' ),
				'value' => $cron_interval,
				'debug' => isset( $options['cron_interval'] ) && ! empty( $options['cron_interval'] ) ? $options['cron_interval'] : '—',
			],
			'num_products_per_update'      => [
				'label' => __( 'Products per Update', 'datafeedr-product-sets' ),
				'value' => isset( $options['num_products_per_update'] ) && ! empty( $options['num_products_per_update'] ) ? ucfirst( $options['num_products_per_update'] ) : '—',
				'debug' => isset( $options['num_products_per_update'] ) && ! empty( $options['num_products_per_update'] ) ? $options['num_products_per_update'] : '—',
			],
			'num_products_per_api_request' => [
				'label' => __( 'Products per API Request', 'datafeedr-product-sets' ),
				'value' => isset( $options['num_products_per_api_request'] ) && ! empty( $options['num_products_per_api_request'] ) ? ucfirst( $options['num_products_per_api_request'] ) : '—',
				'debug' => isset( $options['num_products_per_api_request'] ) && ! empty( $options['num_products_per_api_request'] ) ? $options['num_products_per_api_request'] : '—',
			],
			'preprocess_maximum'           => [
				'label' => __( 'Preprocess Maximum', 'datafeedr-product-sets' ),
				'value' => isset( $options['preprocess_maximum'] ) && ! empty( $options['preprocess_maximum'] ) ? ucfirst( $options['preprocess_maximum'] ) : '—',
				'debug' => isset( $options['preprocess_maximum'] ) && ! empty( $options['preprocess_maximum'] ) ? $options['preprocess_maximum'] : '—',
			],
			'postprocess_maximum'          => [
				'label' => __( 'Postprocess Maximum', 'datafeedr-product-sets' ),
				'value' => isset( $options['postprocess_maximum'] ) && ! empty( $options['postprocess_maximum'] ) ? ucfirst( $options['postprocess_maximum'] ) : '—',
				'debug' => isset( $options['postprocess_maximum'] ) && ! empty( $options['postprocess_maximum'] ) ? $options['postprocess_maximum'] : '—',
			],
		]
	];


	return $info;
} );
