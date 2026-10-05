<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( is_admin() ) {
	add_action( 'wp_ajax_dfrps_ajax_add_individual_product', 'dfrps_ajax_add_individual_product' );
	add_action( 'wp_ajax_dfrps_ajax_get_products', 'dfrps_ajax_get_products' );
	add_action( 'wp_ajax_dfrps_ajax_block_individual_product', 'dfrps_ajax_block_individual_product' );
	add_action( 'wp_ajax_dfrps_ajax_remove_individual_product', 'dfrps_ajax_remove_individual_product' );
	add_action( 'wp_ajax_dfrps_ajax_unblock_individual_product', 'dfrps_ajax_unblock_individual_product' );
	add_action( 'wp_ajax_dfrps_ajax_save_query', 'dfrps_ajax_save_query' );
	add_action( 'wp_ajax_dfrps_ajax_update_taxonomy', 'dfrps_ajax_update_taxonomy' );
	add_action( 'wp_ajax_dfrps_ajax_update_import_into', 'dfrps_ajax_update_import_into' );
	add_action( 'wp_ajax_dfrps_ajax_update_now', 'dfrps_ajax_update_now' );
	add_action( 'wp_ajax_dfrps_ajax_delete_saved_search', 'dfrps_ajax_delete_saved_search' );
	add_action( 'wp_ajax_dfrps_ajax_update_progress_bar', 'dfrps_ajax_update_progress_bar' );
	add_action( 'wp_ajax_dfrps_ajax_dashboard', 'dfrps_ajax_dashboard' );
	add_action( 'wp_ajax_dfrps_ajax_test_loopbacks', 'dfrps_ajax_test_loopbacks' );
	add_action( 'wp_ajax_dfrps_ajax_reset_cron', 'dfrps_ajax_reset_cron' );
	add_action( 'wp_ajax_dfrps_ajax_fix_missing_images', 'dfrps_ajax_fix_missing_images' );
	add_action( 'wp_ajax_dfrps_ajax_batch_import_images', 'dfrps_ajax_batch_import_images' );
	add_action( 'wp_ajax_dfrps_ajax_start_batch_image_import', 'dfrps_ajax_start_batch_image_import' );
	add_action( 'wp_ajax_dfrps_ajax_stop_batch_image_import', 'dfrps_ajax_stop_batch_image_import' );
}

/**
 * Verifies the nonce and that the current user can manage the site-wide
 * Product Sets tools. Ends the request with a 403 if not.
 *
 * @since 1.3.25
 */
function dfrps_ajax_verify_tools_request() {

	check_ajax_referer( 'dfrps_ajax_nonce', 'dfrps_security' );

	$capability = apply_filters( 'dfrps_manage_tools_capability', 'manage_options' );

	if ( ! current_user_can( $capability ) ) {
		wp_die( -1, 403 );
	}
}

/**
 * Verifies the nonce, that $_REQUEST['postid'] is a Product Set and that
 * the current user can edit it. Ends the request with a 403 if not.
 *
 * @return int The verified Product Set ID.
 * @since 1.3.25
 */
function dfrps_ajax_verify_product_set_request() {

	check_ajax_referer( 'dfrps_ajax_nonce', 'dfrps_security' );

	$postid = isset( $_REQUEST['postid'] ) ? absint( $_REQUEST['postid'] ) : 0;

	if ( ! $postid || DFRPS_CPT !== get_post_type( $postid ) || ! current_user_can( 'edit_post', $postid ) ) {
		wp_die( -1, 403 );
	}

	return $postid;
}

function dfrps_ajax_test_loopbacks() {

	dfrps_ajax_verify_tools_request();

	$wp_cron = site_url( 'wp-cron.php' );

	$response = wp_remote_get( $wp_cron );
	$code     = wp_remote_retrieve_response_code( $response );
	$body     = wp_remote_retrieve_body( $response );

	if ( '200' == $code ) {
		echo '<div class="dfrps_alert dfrps_alert-success">';
		echo '<strong>' . __( 'Success: ', 'datafeedr-product-sets' ) . '</strong> ';
		_e( 'HTTP Loopbacks are enabled!', 'datafeedr-product-sets' );
		echo '</div>';
		die;
	}

	if ( '404' == $code ) {
		$msg = sprintf(
			__( 'The wp-cron.php file is missing. Please ensure that the following URL exists and is publicly accessible: <a href="%1$s" target="_blank">%2$s</a> ', 'datafeedr-product-sets' ),
			esc_url( $wp_cron ),
			esc_html( $wp_cron )
		);
	} elseif ( is_wp_error( $response ) ) {
		$msg = implode( '<br/>', array_map( 'esc_html', $response->get_error_messages() ) );
	} else {
		// The response body could be any HTML page (ie. a firewall or proxy error page), so display it as text.
		$msg = '<pre>' . esc_html( substr( $body, 0, 2000 ) ) . '</pre>';
	}

	echo '<div class="dfrps_alert dfrps_alert-danger">';
	echo '<strong>' . __( 'Error: ', 'datafeedr-product-sets' ) . '</strong> ';
	echo $msg;
	echo '</div>';

	die;
}

function dfrps_ajax_reset_cron() {
	dfrps_ajax_verify_tools_request();
	wp_clear_scheduled_hook( 'dfrps_cron' );
	wp_schedule_event( time(), 'dfrps_schedule', 'dfrps_cron' );
	_e( 'Cron was successfully reset.', 'datafeedr-product-sets' );
	die;
}

function dfrps_ajax_fix_missing_images() {

	dfrps_ajax_verify_tools_request();

	global $wpdb;

	/**
	 * SET '_dfrps_product_check_image' equal to 1
	 * WHERE post_status = 'publish'
	 * AND '_dfrps_product_check_image' equal = 0
	 * AND _thumbnail_id = NULL
	 */
	$update = $wpdb->query( "
		UPDATE $wpdb->postmeta pm1
			JOIN $wpdb->posts AS p
				ON p.ID = pm1.post_id
			LEFT JOIN $wpdb->postmeta AS pm2
				ON p.ID = pm2.post_id
				AND pm2.meta_key = '_thumbnail_id'
		SET pm1.meta_value = '1'
		WHERE pm1.meta_key = '_dfrps_product_check_image'
		AND pm1.meta_value = '0'
		AND pm2.post_id IS NULL
		AND p.post_status = 'publish'	
	" );

	if ( is_integer( $update ) ) {
		echo number_format( $update );
		if ( $update == 0 ) {
			_e( ' product images need fixing at this time.', 'datafeedr-product-sets' );
		} else {
			echo sprintf(
				_n(
					' product image is flagged to be fixed the next time it is displayed on your site.',
					' product images are flagged to be fixed the next time they are displayed on your site.',
					$update,
					'datafeedr-product-sets'
				),
				$update
			);
		}
	} else {
		_e( 'There was an error with your request.', 'datafeedr-product-sets' );
		echo '<pre>';
		echo esc_html( print_r( $update, true ) );
		echo '</pre>';
	}
	die;
}

function dfrps_ajax_start_batch_image_import() {
	dfrps_ajax_verify_tools_request();
	update_option( 'dfrps_do_batch_image_import', true );
	die;
}

function dfrps_ajax_stop_batch_image_import() {
	dfrps_ajax_verify_tools_request();
	delete_option( 'dfrps_do_batch_image_import' );
	sleep( 2 );
	die;
}

function dfrps_ajax_batch_import_images() {

	dfrps_ajax_verify_tools_request();

	$do_import = get_option( 'dfrps_do_batch_image_import', false );

	if ( ! $do_import ) {
		echo 'Stopped'; // Don't translate this. We use this value on the other side.
		die;
	}

	global $wpdb;

	/**
	 * SELECT id
	 * FROM posts
	 * WHERE post_status = publish
	 * AND '_dfrps_product_check_image' = 1
	 * AND '_thumbnail_id' IS NULL
	 * AND the meta_key '_dfrps_product_set_id' exists.
	 */
	$id = $wpdb->get_var( "
		SELECT pm1.post_id AS post_id 
		FROM $wpdb->postmeta AS pm1
		JOIN $wpdb->posts AS p
			ON p.ID = pm1.post_id
		LEFT JOIN $wpdb->postmeta AS pm2
			ON p.ID = pm2.post_id
			AND pm2.meta_key = '_thumbnail_id'
		LEFT JOIN $wpdb->postmeta AS pm3
			ON p.ID = pm3.post_id
		WHERE pm1.meta_key = '_dfrps_product_check_image'
		AND pm1.meta_value = '1'
		AND pm2.post_id IS NULL
		AND pm3.meta_key = '_dfrps_product_set_id'
		AND p.post_status = 'publish'
		ORDER BY post_id ASC
	" );

	if ( empty( $id ) ) {
		delete_option( 'dfrps_do_batch_image_import' );
		echo 'Complete'; // Don't translate this. We use this value on the other side.
		die;
	}

	$post = get_post( $id );

	/**
	 * @since 1.2.22
	 */
	if ( function_exists( 'datafeedr_import_image' ) ) {

		$url  = get_permalink( $post->ID );
		$name = esc_html( $post->post_title );
		$html = '<li>%1$s</li>';

		$time_start = microtime( true );

		$result = dfrps_import_post_thumbnail( $post->ID );

		// This handles the transition from Datafeedr_Image_Importer to Dfrapi_Image_Uploader
		if ( is_a( $result, 'Datafeedr_Image_Importer' ) ) {
			if ( $result->wp_error() ) {
				$result = $result->wp_error();
			}
		}

		if ( is_wp_error( $result ) ) {

			$error = [ 'function' => __FUNCTION__ . '()', 'WP_Error' => $result ];

			$msg = sprintf(
				__( 'There was an error importing the image for <a href="%1$s" target="_blank">%2$s</a>. See below for details.<br /><pre>%3$s</pre>' ),
				esc_url( $url ),
				esc_html( $name ),
				esc_html( print_r( $error, true ) )
			);

			echo sprintf( $html, $msg );
			die;
		}

		$time_stop = microtime( true );

		$execution_time = round( ( $time_stop - $time_start ), 2 );

		$msg = sprintf(
			__( 'Image imported successfully for <a href="%1$s" target="_blank">%2$s</a> in %3$s seconds.' ),
			esc_url( $url ),
			esc_html( $name ),
			esc_html( $execution_time )
		);

		echo sprintf( $html, $msg );
		die;
	}

	// Import the image.	
	if ( ! class_exists( 'Dfrps_Image_Importer' ) ) {
		require_once( DFRPS_PATH . 'classes/class-dfrps-image-importer.php' );
	}

	new Dfrps_Image_Importer ( $post );

	echo '<li>Image imported for <a href="' . esc_url( site_url( '/?p=' . absint( $post->ID ) ) ) . '" target="_blank">' . esc_html( $post->post_title ) . '</a></li>';
	die;
}

function dfrps_ajax_dashboard() {

	$postid = dfrps_ajax_verify_product_set_request();

	$html             = '';
	$post_title       = get_the_title( $postid );
	$post_status      = get_post_status( $postid );
	$meta             = get_post_custom( $postid );
	$type             = get_post_meta( $postid, '_dfrps_cpt_type', true );
	$update_phase     = ( isset( $meta['_dfrps_cpt_update_phase'][0] ) ) ? intval( $meta['_dfrps_cpt_update_phase'][0] ) : 0;
	$next_update_time = isset( $meta['_dfrps_cpt_next_update_time'][0] ) ? $meta['_dfrps_cpt_next_update_time'][0] : false;
	$last_update_time = $meta['_dfrps_cpt_last_update_time_completed'][0];
	$temp_query       = isset( $meta['_dfrps_cpt_temp_query'][0] ) ? $meta['_dfrps_cpt_temp_query'][0] : false;
	$saved_query      = isset( $meta['_dfrps_cpt_query'][0] ) ? $meta['_dfrps_cpt_query'][0] : false;
	$term_ids         = dfrps_get_cpt_terms( $postid, false );
	$links            = array();

	$cats_query = false;
	if ( $term_ids && ! empty( $type ) ) {
		$registered_cpts = get_option( 'dfrps_registered_cpts', array() );
		$tax             = $registered_cpts[ $type ]['taxonomy'];
		$cats_query      = true;
		foreach ( $term_ids as $id ) {
			$term = get_term( $id, $tax );
			if ( is_object( $term ) && isset( $term->count ) && $term->count > 0 ) {
				$links[] = array(
					'name' => $term->name,
					'url'  => esc_url( get_term_link( $term, $tax ) ),
				);
			}
		}
	}

	// Product Set 'type' is inactive. Just show message and don't show next update time or [Bump] button.
	if ( ! dfrps_set_is_active( $type ) ) {

		$html .= '<div state="inactive_type"></div>';
		$html .= '<p><span class="dashicons dashicons-flag"></span> ' . __( 'This Product Set is inactive. To re-activate this Product Set, install and activate the importer plugin responsible for importing products into the "' . esc_html( $type ) . '" custom post type.', 'datafeedr-product-sets' ) . '</p>';

	} else {

		// Status: auto-draft
		if ( $post_status != 'publish' && $post_status != 'future' ) {

			$title_class = ( $post_title != '' && $post_title != __( 'Auto Draft' ) ) ? ' dfrps_dashboard_step_completed' : '';
			$temp_class  = ( $temp_query != '' ) ? ' dfrps_dashboard_step_completed' : '';
			$saved_class = ( $saved_query != '' ) ? ' dfrps_dashboard_step_completed' : '';
			$cats_class  = ( $cats_query ) ? ' dfrps_dashboard_step_completed' : '';

			$html .= '<div state="00"></div>';
			$html .= '<p><span class="dashicons dashicons-smiley"></span> ' . __( 'Let\'s get started on a new Product Set!', 'datafeedr-product-sets' ) . '</p>';
			$html .= '<ol>';
			$html .= '<li id="dfrps_step_title" class="' . $title_class . '">' . __( 'Title your Product Set.', 'datafeedr-product-sets' ) . '</li>';
			$html .= '<li id="dfrps_step_search" class="' . $temp_class . '">' . __( 'Search for products.', 'datafeedr-product-sets' ) . '</li>';
			$html .= '<li id="dfrps_step_save" class="' . $saved_class . '">' . __( ' Click <strong>[Add as Saved Search]</strong> when you\'re happy with search results.', 'datafeedr-product-sets' ) . '</li>';
			$html .= '<li id="dfrps_step_category" class="' . $cats_class . '">' . __( 'Select a category to import into.', 'datafeedr-product-sets' ) . '</li>';
			$html .= '<li id="dfrps_step_publish">' . __( 'Click the <strong>[Publish]</strong> button to import these products into your site.', 'datafeedr-product-sets' ) . '</li>';
			$html .= '</ol>';
		}

		// Status: publish
		if ( $post_status == 'publish' || $post_status == 'future' ) {

			if ( $update_phase > 0 ) {

				$percent = dfrps_percent_complete( $postid );

				if ( $last_update_time == 0 ) {

					$html .= '<div state="updating_' . $percent . '"></div>';
					$html .= '<p><span class="dashicons dashicons-upload"></span>' . __( 'The products in this Product Set are currently being imported into your site.', 'datafeedr-product-sets' ) . '</p>';

				} else {

					$html .= '<div state="updating_' . $percent . '"></div>';
					$html .= '<p><span class="dashicons dashicons-upload"></span>' . __( 'The products in this Product Set are currently being updated.', 'datafeedr-product-sets' ) . '</p>';
				}

				if ( $percent ) {
					$html .= dfrps_progress_bar( $percent );
				}

			} elseif ( $last_update_time == 0 ) {

				if ( $post_status == 'future' ) {

					$html .= '<div state="future"></div>';
					$html .= '<p><span class="dashicons dashicons-clock"></span> ' . __( 'This Product Set is scheduled to be published at a future date. The products in this Set will be imported when the Set becomes published.', 'datafeedr-product-sets' ) . '</p>';

				} else {

					$html .= '<div state="queued"></div>';
					$html .= '<p><span class="dashicons dashicons-calendar"></span> ' . __( 'The products in this Product Set are queued to update on ', 'datafeedr-product-sets' ) . date_i18n( get_option( 'date_format' ) . ' @ ' . get_option( 'time_format' ), $next_update_time ) . '.</p>';
					$html .= '<p><span class="dashicons dashicons-info"></span><em>' . __( 'Actual update time is determined by its place in the queue as well as the next scheduled cron.', 'datafeedr-product-sets' ) . '</em></p>';

				}

			} elseif ( ( $last_update_time + 60 ) > date_i18n( 'U' ) ) {

				$html .= '<div state="recently_completed"></div>';
				$html .= '<p><span class="dashicons dashicons-yes"></span> ' . __( 'This Product Set completed a full product update less than 1 minute ago.', 'datafeedr-product-sets' ) . '</p>';

				$num_links = count( $links );
				if ( $num_links > 0 ) {
					$html .= '<p><span class="dashicons dashicons-welcome-view-site"></span> ' . __( 'View category: ', 'datafeedr-product-sets' );
					$i    = 1;
					foreach ( $links as $link ) {
						$html .= '<br/><a href="' . $link['url'] . '" target="_blank">' . esc_html( $link['name'] ) . '</a>';
						$i ++;
						if ( $i <= $num_links ) {
							$html .= ', ';
						}
					}
					$html . '</p>';

					$html .= '<p>
						<a href="#" class="button" id="dfrps_set_next_update_time_to_now">' . __( 'Bump', 'datafeedr-product-sets' ) . '</a><br />
						<small><em>' . __( 'Bump Product Set to front of update queue.', 'datafeedr-product-sets' ) . '</em></small>
					</p>';
				}

			} elseif ( date_i18n( 'U' ) > $next_update_time ) {

				$html .= '<div state="queued"></div>';
				$html .= '<p><span class="dashicons dashicons-calendar"></span> ' . __( 'The products in this Product Set are queued to update on ', 'datafeedr-product-sets' ) . date_i18n( get_option( 'date_format' ) . ' @ ' . get_option( 'time_format' ), $next_update_time ) . '.</p>';
				$html .= '<p><span class="dashicons dashicons-info"></span><em>' . __( 'Actual update time is determined by its place in the queue as well as the next scheduled cron.', 'datafeedr-product-sets' ) . '</em></p>';

			} else {

				$html .= '<div state="scheduled"></div>';
				$html .= '<p><span class="dashicons dashicons-calendar"></span> ' . __( 'The products in this Product Set are scheduled to update on ', 'datafeedr-product-sets' ) . date_i18n( get_option( 'date_format' ) . ' @ ' . get_option( 'time_format' ), $next_update_time ) . '.</p>';
				$html .= '<p>
					<a href="#" class="button" id="dfrps_set_next_update_time_to_now">' . __( 'Bump', 'datafeedr-product-sets' ) . '</a><br />
					<small><em>' . __( 'Bump Product Set to front of update queue.', 'datafeedr-product-sets' ) . '</em></small>
				</p>';
			}

		}
	}

	echo $html;
	die;
}

function dfrps_ajax_update_progress_bar() {

	$postid = dfrps_ajax_verify_product_set_request();

	$percent = dfrps_percent_complete( $postid );

	if ( ! $percent ) {
		echo '';
	} else {
		echo dfrps_progress_bar( $percent );
	}
	die;

}

function dfrps_ajax_delete_saved_search() {

	$postid = dfrps_ajax_verify_product_set_request();

	delete_post_meta( $postid, '_dfrps_cpt_query' );
	_e( 'Saved search successfully deleted!', 'datafeedr-product-sets' );
	die;

}

function dfrps_ajax_update_now() {

	$postid = dfrps_ajax_verify_product_set_request();

	update_post_meta( $postid, '_dfrps_cpt_next_update_time', date_i18n( 'U' ) );

	$html = '<div state="queued"></div>';
	$html .= '<p><span class="dashicons dashicons-calendar"></span>' . __( 'The products in this Product Set are queued to update on ', 'datafeedr-product-sets' ) . date_i18n( get_option( 'date_format' ) . ' @ ' . get_option( 'time_format' ), date_i18n( 'U' ) ) . '.</p>';
	$html .= '<p><span class="dashicons dashicons-info"></span><em>' . __( 'Actual update time is determined by its place in the queue as well as the next scheduled cron.', 'datafeedr-product-sets' );
	echo $html;

	//_e( 'This Product Set will update ASAP!', 'datafeedr-product-sets' );
	die;

}

function dfrps_ajax_update_import_into() {

	$postid = dfrps_ajax_verify_product_set_request();

	// Set $term_ids variable.
	$term_ids = ( isset( $_REQUEST['term_ids'] ) && '' !== $_REQUEST['term_ids'] ) ? (array) $_REQUEST['term_ids'] : array();
	$term_ids = array_values( array_filter( array_map( 'absint', $term_ids ) ) );

	// Update 'type' and 'term ids'. Only accept a 'type' that is a registered Custom Post Type.
	update_post_meta( $postid, '_dfrps_cpt_terms', $term_ids );

	$type = sanitize_key( $_REQUEST['type'] ?? '' );
	if ( array_key_exists( $type, (array) get_option( 'dfrps_registered_cpts', array() ) ) ) {
		update_post_meta( $postid, '_dfrps_cpt_type', $type );
	}

	echo '';
	die;
}

function dfrps_ajax_update_taxonomy() {

	$postid = dfrps_ajax_verify_product_set_request();

	// Get term ids.
	$term_ids = ( isset( $_REQUEST['cids'] ) && ! empty( $_REQUEST['cids'] ) ) ? $_REQUEST['cids'] : array();
	$term_ids = array_values( array_filter( array_map( 'absint', (array) $term_ids ) ) );

	// Store $cids
	update_post_meta( $postid, '_dfrps_cpt_terms', $term_ids );

	echo '';
	die;
}

/**
 * This saves a search when user clicks the
 * [Add as Saved Search] or [Update Saved Search] button.
 */
function dfrps_ajax_save_query() {

	$postid = dfrps_ajax_verify_product_set_request();

	// Get most recently stored TEMP query.
	$temp_query = get_post_meta( $postid, '_dfrps_cpt_temp_query', true );
	update_post_meta( $postid, '_dfrps_cpt_query', $temp_query );

	_e( 'Update Saved Search', 'datafeedr-product-sets' );
	die;
}

/**
 * This function is used to determine the context of
 * how we should return the products and load the
 * necessary function to get the products.
 *
 * This is ONLY called via an AJAX request, not
 * directly from another function.
 */
function dfrps_ajax_get_products() {

	/**
	 * Possible $_REQUEST values:
	 *
	 * query    - This is the API query (multiple filters)
	 * ids        - An array of product IDs.
	 * postid    - This is the post ID of the Product Set.
	 * page    - This is the page number being requested for pagination.
	 * context    - This will determine how to out put the list of products and pagination.
	 */

	$postid = dfrps_ajax_verify_product_set_request();

	// Possible contexts.
	$possible_contexts = array(
		'div_dfrps_tab_search',
		'div_dfrps_tab_saved_search',
		'div_dfrps_tab_included',
		'div_dfrps_tab_blocked',
	);

	// Set $context variable.
	$context = ( isset( $_REQUEST['context'] ) && in_array( $_REQUEST['context'], $possible_contexts ) ) ? $_REQUEST['context'] : false;

	// If $context doesn't validate, show error.
	if ( ! $context ) {
		_e( 'No context provided. Context is required.', 'datafeedr-product-sets' );
		die;
	}

	// Set $page variable.
	$page = ( isset( $_REQUEST['page'] ) ) ? intval( $_REQUEST['page'] ) : 1;

	// Get "num_products_per_search" value for $limit value.
	$configuration = (array) get_option( 'dfrps_configuration', array( 'num_products_per_search' => 10 ) );
	$limit         = $configuration['num_products_per_search'];

	// Initialize $args array.
	$args = array();

	// Set offset.
	$offset = ( $page > 0 ) ? ( ( $page - 1 ) * $limit ) : 0;

	// Make sure $limit doesn't go over 10,000.
	if ( ( $offset + $limit ) > 10000 ) {
		$limit = ( 10000 - $offset );
	}

	// Default $args to pass to any function
	$args['limit']  = $limit;
	$args['offset'] = $offset;

	/**
	 * Based on $context, determine what to do next.
	 */

	// Context is "div_dfrps_tab_search"
	if ( $context == 'div_dfrps_tab_search' ) {

		/**
		 * A search was performed. We need to save the query
		 * so that it can be requested on subsequent paginated pages.
		 */

		// Save post if query is performed but post has not been saved at all.
		if ( ( get_post_status( $postid ) == 'auto-draft' ) && ( $postid > 0 ) ) {
			$timezone_format = _x( 'Y-m-d G:i:s', 'timezone date format' );
			$post            = array(
				'ID'          => $postid,
				'post_title'  => __( 'Auto Save', 'datafeedr-product-sets' ) . ' - ' . date_i18n( $timezone_format ),
				'post_status' => 'draft',
			);
			wp_update_post( $post );
		}

		// Isolate the query
		if ( isset ( $_REQUEST['query'] ) ) {
			parse_str( $_REQUEST['query'], $query );
		} else {
			$query                     = array();
			$query['_dfrps_cpt_query'] = array();
		}

		// If query is not empty, store it as the temp query.
		if ( ! empty( $query['_dfrps_cpt_query'] ) ) {

			// Query exists so save it.
			$temp_query = dfrps_sanitize_query( $query['_dfrps_cpt_query'] );
			update_post_meta( $postid, '_dfrps_cpt_temp_query', $temp_query );

		} else {

			// No query exists so grab the last stored query.
			$temp_query = get_post_meta( $postid, '_dfrps_cpt_temp_query', true );
		}

		// Get manually blocked product IDs.
		$blocked = get_post_meta( $postid, '_dfrps_cpt_manually_blocked_ids', true );
		if ( is_array( $blocked ) && ! empty( $blocked ) ) {
			$manually_blocked = $blocked;
		} else {
			$manually_blocked = array();
		}

		// Add "manually_excluded" to the $args.
		$args['manually_blocked'] = $manually_blocked;

		// Query API if a temp query exists.
		if ( ! empty( $temp_query ) ) {
			$data = dfrapi_api_get_products_by_query( $temp_query, $limit, $page, $manually_blocked );
			//$data = dfrps_api_get_products_by_query( $temp_query, $postid, $context, $page );
		}

		// Print any errors.
		if ( is_array( $data ) && array_key_exists( 'dfrapi_api_error', $data ) ) {
			echo dfrapi_output_api_error( $data );
			die;
		}

		// Add a few more helpful values to the $data array;
		$data['page']   = $page;
		$data['postid'] = $postid;
		$data['limit']  = $limit;
		$data['offset'] = $offset;

		echo dfrps_format_product_list( $data, $context );
		die;

	} // if ( $context == 'div_dfrps_tab_search' )

	// Context is "div_dfrps_tab_saved_search"
	if ( $context == 'div_dfrps_tab_saved_search' ) {

		// Get query
		$saved_query = get_post_meta( $postid, '_dfrps_cpt_query', true );

		// Get manually blocked product IDs.
		$blocked = get_post_meta( $postid, '_dfrps_cpt_manually_blocked_ids', true );
		if ( is_array( $blocked ) && ! empty( $blocked ) ) {
			$manually_blocked = $blocked;
		} else {
			$manually_blocked = array();
		}

		// Add "manually_excluded" to the $args.
		$args['manually_blocked'] = $manually_blocked;

		// Query API if a saved query exists.
		if ( ! empty( $saved_query ) ) {
			$data = dfrapi_api_get_products_by_query( $saved_query, $limit, $page, $manually_blocked );
			//$data = dfrps_api_get_products_by_query( $saved_query, $postid, $context, $page );
		}

		// Print any errors.
		if ( isset( $data ) && is_array( $data ) && array_key_exists( 'dfrapi_api_error', $data ) ) {
			echo dfrapi_output_api_error( $data );
			die;
		}

		// Add a few more helpful values to the $data array;
		$data['page']   = $page;
		$data['postid'] = $postid;
		$data['limit']  = $limit;
		$data['offset'] = $offset;

		// Update number of products in this saved search.
		//update_post_meta( $postid, '_dfrps_cpt_saved_search_num_products', intval( $data['found_count'] ) );

		echo dfrps_format_product_list( $data, $context );
		die;

	} // if ( $context == 'div_dfrps_tab_saved_search' )

	// Context is "div_dfrps_tab_included"
	if ( $context == 'div_dfrps_tab_included' ) {

		$ids = get_post_meta( $postid, '_dfrps_cpt_manually_added_ids', true );
		$ids = array_filter( (array) $ids );

		// Query API if IDs exists.
		if ( ! empty( $ids ) ) {
			$data = dfrapi_api_get_products_by_id( $ids, $limit, $page );
		}

		// Print any errors.
		if ( isset( $data ) && is_array( $data ) && array_key_exists( 'dfrapi_api_error', $data ) ) {
			echo dfrapi_output_api_error( $data );
			die;
		}

		// Add a few more helpful values to the $data array;
		$data['page']   = $page;
		$data['postid'] = $postid;
		$data['limit']  = $limit;
		$data['offset'] = $offset;

		echo dfrps_format_product_list( $data, $context );
		die;

	} // if ( $context == 'div_dfrps_tab_included' )

	// Context is "div_dfrps_tab_blocked"
	if ( $context == 'div_dfrps_tab_blocked' ) {

		$ids = get_post_meta( $postid, '_dfrps_cpt_manually_blocked_ids', true );
		$ids = array_filter( (array) $ids );

		// Query API if IDs exists.
		if ( ! empty( $ids ) ) {
			$data = dfrapi_api_get_products_by_id( $ids, $limit, $page );
		}

		// Print any errors.
		if ( isset( $data ) && is_array( $data ) && array_key_exists( 'dfrapi_api_error', $data ) ) {
			echo dfrapi_output_api_error( $data );
			die;
		}

		// Add a few more helpful values to the $data array;
		$data['page']   = $page;
		$data['postid'] = $postid;
		$data['limit']  = $limit;
		$data['offset'] = $offset;

		echo dfrps_format_product_list( $data, $context );
		die;

	} // if ( $context == 'div_dfrps_tab_blocked' ) {

	echo 'Uh-oh. Something went wrong.';
	die;
}

/**
 * Add individual product to Product Set.
 */
function dfrps_ajax_add_individual_product() {
	$postid = dfrps_ajax_verify_product_set_request();
	$pid    = sanitize_text_field( wp_unslash( $_REQUEST['pid'] ?? '' ) );
	dfrps_helper_add_id_to_postmeta( $pid, $postid, '_dfrps_cpt_manually_added_ids' );
	echo '<div class="dfrps_product_already_included" title="' . __( 'Product successfully added to this Product Set.', 'datafeedr-product-sets' ) . '"><img src="' . plugins_url( "images/icons/checkmark.png", dirname( __FILE__ ) ) . '" /></div>';
	die;
}

/**
 * Add product ID to blocked_ids array.
 * Remove Product ID from manually included post meta.
 */
function dfrps_ajax_block_individual_product() {
	$postid = dfrps_ajax_verify_product_set_request();
	$pid    = sanitize_text_field( wp_unslash( $_REQUEST['pid'] ?? '' ) );
	dfrps_helper_add_id_to_postmeta( $pid, $postid, '_dfrps_cpt_manually_blocked_ids' );
	dfrps_helper_remove_id_from_postmeta( $pid, $postid, '_dfrps_cpt_manually_added_ids' );
	echo '';
	die;
}

/**
 * Remove individual product that was added manually from Product Set.
 */
function dfrps_ajax_remove_individual_product() {
	$postid = dfrps_ajax_verify_product_set_request();
	$pid    = sanitize_text_field( wp_unslash( $_REQUEST['pid'] ?? '' ) );
	dfrps_helper_remove_id_from_postmeta( $pid, $postid, '_dfrps_cpt_manually_added_ids' );
	echo '';
	die;
}

/**
 * Unblock individual product that already blocked from the Product Set.
 */
function dfrps_ajax_unblock_individual_product() {
	$postid = dfrps_ajax_verify_product_set_request();
	$pid    = sanitize_text_field( wp_unslash( $_REQUEST['pid'] ?? '' ) );
	dfrps_helper_remove_id_from_postmeta( $pid, $postid, '_dfrps_cpt_manually_blocked_ids' );
	echo '';
	die;
}

