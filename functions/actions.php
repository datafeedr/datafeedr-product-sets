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

	if ( ! function_exists( 'dfrapi_schedule_single_action' ) ) {
		dfrps_error_log( 'The dfrapi_schedule_single_action() function does not exist. Please upgrade your Datafeedr API plugin to the latest version.' );
	}

	$post_id = absint( $post['ID'] );

	$do_import_thumbnail = dfrps_do_import_product_thumbnail( $post_id );

	if ( is_wp_error( $do_import_thumbnail ) ) {
		dfrps_error_log( 'Skipping image import' . ': ' . print_r( $do_import_thumbnail, true ) );

		return;
	}

	dfrapi_schedule_single_action( date( 'U' ), 'dfrps_import_product_image', compact( 'post_id' ), 'dfrps' );
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

/**
 * Handle "No merchants selected" Product Set errors.
 *
 * @param string $error The error message (ex. No merchants selected)
 * @param array $data Error data
 * @param Dfrps_Update $product_set
 *
 * @since 1.3.6
 */
function dfrps_handle_no_merchants_selected_error( $error, $data, $product_set ) {

	if ( ! isset( $product_set->config['disable_updates_when_missing_merchants'] ) ) {
		return;
	}

	if ( $product_set->config['disable_updates_when_missing_merchants'] !== 'no' ) {
		return;
	}

	/**
	 * Only trigger the rest of the code if the error is equal to "No merchants selected".
	 */
	if ( $error !== 'No merchants selected' ) {
		return;
	}

	/**
	 * Set the update phase to "4" to skip step #2 and/or #3.
	 */
	update_post_meta( $product_set->set['ID'], '_dfrps_cpt_update_phase', 4 );

	/**
	 * Re-enable Product Set updates.
	 */
	$product_set->config['updates_enabled'] = 'enabled';
	update_option( 'dfrps_configuration', $product_set->config );

	/**
	 * Fire action to allow user to customize anything that might need to happen here.
	 */
	do_action( 'dfrps_handle_no_merchants_selected_error', $error, $data, $product_set );

	/**
	 * Get URL of the Product Set's "edit page.
	 */
	$url = add_query_arg( [ 'post' => $product_set->set['ID'], 'action' => 'edit' ], admin_url( 'post.php' ) );

	/**
	 * URL to documentation article regarding this error.
	 */
	$doc = 'https://datafeedrapi.helpscoutdocs.com/article/253-no-merchants-selected-error';

	/**
	 * Send email to user letting them know the Product Set is in "draft" mode and requires attention.
	 */
	$email            = [];
	$email['to']      = get_bloginfo( 'admin_email' );
	$email['subject'] = sprintf( '[ACTION REQUIRED]: Product Set (ID: %d) is Missing Merchants - %s', absint( $product_set->set['ID'] ), esc_html( get_bloginfo( 'name' ) ) );

	$email['message'] = sprintf( '<p>%s</p>', 'The following Product Set is missing merchants and its products have been removed from your store. Product Set updates have been re-enabled.' );
	$email['message'] .= sprintf( '<p>%s</p>', 'This issue <strong>MUST</strong> to be resolved before the products in this Product Set will be added to your store.' );
	$email['message'] .= sprintf(
		'<p>- %s: %s<br />- %s: %s<br />- %s: %s</p>',
		'Name',
		esc_html( $product_set->set['post_title'] ),
		'ID',
		absint( $product_set->set['ID'] ),
		'URL',
		sprintf( '<a href="%s">%s</a>', $url, $url )
	);
	$email['message'] .= sprintf( '<p>Learn more about this issue %s.</p>', sprintf( '<a href="%s">here</a>', $doc ) );

	add_filter( 'wp_mail_content_type', 'dfrps_set_html_content_type' );
	wp_mail( $email['to'], $email['subject'], $email['message'] );
	remove_filter( 'wp_mail_content_type', 'dfrps_set_html_content_type' );
}

add_action( 'dfrps_product_set_updates_disabled', 'dfrps_handle_no_merchants_selected_error', 10, 3 );

/**
 * If the current column is "dfrps_product_sets", display a link to the various Product Sets
 * the current Product ($product_id) is associated with.
 *
 * @param $column
 * @param $product_id
 *
 * @return void
 * @since 1.3.21
 */
function dfrps_add_products_sets_content_to_products_table_column( $column, $product_id ): void {

	if ( $column !== 'dfrps_product_sets' ) {
		return;
	}

	$product         = wc_get_product( $product_id );
	$product_set_ids = dfrpswc_get_product_set_ids_for_product( $product->get_id() );

	$arr = [];

	foreach ( $product_set_ids as $product_set_id ) {
		if ( dfrps_product_set_exists( $product_set_id ) ) {
			$url   = esc_url( get_edit_post_link( $product_set_id ) );
			$title = esc_html( get_the_title( $product_set_id ) );
			$arr[] = sprintf( '<a href="%s" title="View Product Set" target="_blank" rel="noopener">%s</a>', $url, $title );
		} else {
			$arr[] = sprintf( esc_html__( 'Product Set %d does not exist', 'datafeedr-product-sets' ), $product_set_id );
		}
	}

	echo implode( '<br/>', $arr );
}

add_action( 'manage_product_posts_custom_column', 'dfrps_add_products_sets_content_to_products_table_column', 10, 2 );

