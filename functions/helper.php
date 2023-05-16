<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Links to other related or required plugins.
 */
function dfrps_plugin_links( $plugin ) {
	$map = array(
		'dfrapi'    => 'http://wordpress.org/plugins/datafeedr-api/',
		'importers' => admin_url( 'plugins.php' ),
	);

	return $map[ $plugin ];
}

/**
 * Returns the default Product Set update time based on the user's
 * selection here WordPress Admin Area > Product Sets > Configuration
 *
 * @return int
 */
function dfrps_get_default_update_time(): int {
	$configuration = (array) get_option( DFRPS_PREFIX . '_configuration' );
	$interval      = (int) ( $configuration['update_interval'] ?? 7 ); // Update interval in "days". Could be -1 which means continuous.
	$now           = (int) date_i18n( 'U' );
	$future        = $interval * DAY_IN_SECONDS; // Days expressed in seconds.

	return $interval === - 1 ? $now : $now + $future;
}

/**
 * Gets the next update time for a Product Set.
 *
 * @param int $post_id
 *
 * @return float|int|string
 */
function dfrps_get_next_update_time( int $post_id ) {
	$schedule = get_post_meta( $post_id, '_dfrps_update_schedule', true );
	$enabled  = $schedule['enabled'] ?? '';

	if ( empty( $schedule ) || 'on' !== $enabled ) {
		$time = dfrps_get_default_update_time();
	} else {
		$time = dfrps_get_custom_update_time( $schedule );
	}

	return $time;
}

/**
 * Calculate the next time for Product Set to update based on custom schedule.
 *
 * The `$schedule` will be either Day of week, or Day of month and include a list of days and time to run.
 *
 * @param $schedule
 *
 * @return string
 */
function dfrps_get_custom_update_time( $schedule ) {

	$time = dfrps_get_default_update_time(); // If schedule is malformed return default update time.

	$interval = $schedule['interval'] ?? '';
	$days     = $schedule['days'] ?? [];
	$hour     = substr( $schedule['time'] ?? '', 0, 2 );
	$minute   = substr( $schedule['time'] ?? '', 3, 2 );
	$offset   = ( (int) $hour * HOUR_IN_SECONDS ) + ( (int) $minute * MINUTE_IN_SECONDS );

	if ( 'day_of_week' === $interval ) {
		// Days will be an array of values 0 to 7
		$day_of_week = date_i18n( 'w' );
		$found       = false;

		while ( ! $found ) {
			$day_of_week ++;
			if ( 8 === (int) $day_of_week ) {
				$day_of_week = 0;
			}
			if ( in_array( $day_of_week, $days ) ) {
				$found = true; // make sure this loop ends.
				switch ( $day_of_week ) {
					case 0:
						$name_of_day = 'sunday';
						break;
					case 1:
						$name_of_day = 'monday';
						break;
					case 2:
						$name_of_day = 'tuesday';
						break;
					case 3:
						$name_of_day = 'wednesday';
						break;
					case 4:
						$name_of_day = 'thursday';
						break;
					case 5:
						$name_of_day = 'friday';
						break;
					default:
						$name_of_day = 'saturday';
						break;
				}
				$date = strtotime( 'next ' . $name_of_day );
				$time = $date + $offset;
				break;
			}
		}
	} elseif ( 'day_of_month' === $interval ) {
		// Days will be 1 to 28
		$day_of_month  = (int) date_i18n( 'j' );
		$current_year  = (int) date_i18n( 'o' );
		$current_month = (int) date_i18n( 'n' );
		$found         = false;

		while ( ! $found ) {
			$day_of_month ++;
			if ( 29 === $day_of_month ) {
				$day_of_month = 1;
				$current_month ++;
				if ( 12 < $current_month ) {
					$current_month = 1;
					$current_year ++;
				}
			}
			if ( in_array( $day_of_month, $days ) ) {
				$found = true; // make sure this loop ends.
				$time  = strtotime( $current_year . '-' . $current_month . '-' . $day_of_month . ' ' . $hour . ':' . $minute . ':00' );
				break;
			}
		}
	}

	return $time;
}

function dfrps_pagination( $data, $context ) {

	// Initialize $html variable.
	$html = '';

	// Return nothing if there are no products.
	if ( empty( $data['products'] ) ) {
		return $html;
	}

	// Begin pagination class.
	$html .= '<div class="dfrps_pagination">';

	$current_page = $data['page'];
	$limit        = $data['limit'];
	$offset       = $data['offset'];
	$found_count  = $data['found_count'];
	$query        = ( isset( $data['query'] ) ) ? $data['query'] : array();
	$hard_limit   = dfrapi_get_query_param( $query, 'limit' );

	// Limit Found Count to hard limit if hard limit exists.
	if ( $hard_limit ) {
		if ( $found_count > $hard_limit['value'] ) {
			$found_count = $hard_limit['value'];
		}
	}

	// Maximum number of products.
	$max_num_products = ( ( $offset + count( $data['products'] ) ) > $found_count ) ? $found_count : ( $offset + count( $data['products'] ) );

	// Set total possible page.
	$total_possible_pages = ceil( $found_count / $limit );

	// Maximum number of pages.
	$max_total          = 10000;
	$max_possible_pages = ceil( $max_total / $limit );

	// Set total pages (if more pages that max_total value allows, adjust total).
	$total_pages = ( $max_possible_pages < $total_possible_pages ) ? $max_possible_pages : $total_possible_pages;

	// Number of relevant products.
	$relevant_results = ( $found_count > 10000 ) ? 10000 : $found_count;

	// "Showing 1 - 100 of 10,000 total relevant products found."
	$html .= '<div class="dfrps_pager_info">';
	$html .= __( 'Showing ', 'datafeedr-product-sets' );
	$html .= '<span class="dfrps_pager_start">';
	$html .= number_format( ( 1 + $offset ) );
	$html .= '</span>';
	$html .= ' - ';
	$html .= '<span class="dfrps_pager_end">';
	$html .= number_format( $max_num_products );
	$html .= '</span>';
	$html .= __( ' of ', 'datafeedr-product-sets' );
	$html .= '<span class="dfrps_relevant_results">';
	$html .= number_format( $relevant_results );
	$html .= '</span>';
	$html .= __( ' total products.', 'datafeedr-product-sets' );
	$html .= '<span style="float:right"><a class="dfrps_delete_saved_search" href="#">' . __( 'Delete Saved Search', 'datafeedr-product-sets' ) . '</a></span>';
	$html .= '</div>';

	// Return nothing if there are less than 2 pages.
	if ( $total_pages < 2 ) {
		$html .= '<div class="clearfix"></div>';
		$html .= '</div>'; // .dfrps_pagination

		return $html;
	}

	// There is more than 1 page. Start pager classes.
	$html .= '<div class="dfrps_pager_label_wrapper">';
	$html .= '<div class="dfrps_pager_label">' . __( 'Page', 'datafeedr-product-sets' ) . '</div>';
	$html .= '</div>'; // .dfrps_pager_label_wrapper

	$html .= '<div class="dfrps_pager_links">';
	for ( $i = 1; $i <= $total_pages; $i ++ ) {
		if ( $i == $current_page ) {
			$html .= '<span><strong>' . $i . '</strong></span>';
		} else {
			$html .= '<span> <a href="#" class="dfrps_pager" page="' . $i . '" context="' . $context . '">' . $i . '</a> </span>';
		}
	}
	$html .= '<div class="clearfix"></div></div>'; // .dfrps_pager_links

	$html .= '</div>'; // .dfrps_pagination

	return $html;
}

function dfrps_format_product_list( $data, $context ) {

	$msg = '';

	// Get manually included product IDs.
	$manually_included_ids = get_post_meta( $data['postid'], '_dfrps_cpt_manually_added_ids', true );
	if ( ! is_array( $manually_included_ids ) ) {
		$manually_included_ids = array();
	}
	$manually_included_ids = array_filter( $manually_included_ids );

	// Get manually blocked product IDs.
	$manually_blocked_ids = get_post_meta( $data['postid'], '_dfrps_cpt_manually_blocked_ids', true );
	if ( ! is_array( $manually_blocked_ids ) ) {
		$manually_blocked_ids = array();
	}
	$manually_blocked_ids = array_filter( $manually_blocked_ids );

	//Get pagination.
	$pagination = dfrps_pagination( $data, $context );

	// Message on "Search" tab.
	if ( empty( $data ) ) {

		if ( $context == 'div_dfrps_tab_search' ) {
			$msg .= '<div class="dfrps_alert dfrps_alert-info">';
			$msg .= __( 'Click the [Search] button to view products that match your search.', 'datafeedr-product-sets' );
			$msg .= '</div>';
		}

	} elseif ( empty( $data['products'] ) ) {

		if ( $context == 'div_dfrps_tab_search' ) {
			$msg .= '<div class="dfrps_alert dfrps_alert-info">';
			$msg .= __( 'No products matched your search.', 'datafeedr-product-sets' );
			$msg .= '</div>';
		}
	}

	if ( empty( $data ) || empty( $data['products'] ) ) {

		if ( $context == 'div_dfrps_tab_saved_search' ) {
			$msg .= '<div class="dfrps_alert dfrps_alert-info">';
			$msg .= __( 'You have not saved a search.', 'datafeedr-product-sets' );
			$msg .= '</div>';
		} elseif ( $context == 'div_dfrps_tab_included' ) {
			$msg .= '<div class="dfrps_alert dfrps_alert-info">';
			$msg .= __( 'You have not added any individual products to this Product Set.', 'datafeedr-product-sets' );
			$msg .= '</div>';
		} elseif ( $context == 'div_dfrps_tab_blocked' ) {
			$msg .= '<div class="dfrps_alert dfrps_alert-info">';
			$msg .= __( 'You have not blocked any products from this Product Set.', 'datafeedr-product-sets' );
			$msg .= '</div>';
		}

	} else {

		$args = array(
			'manually_included_ids' => $manually_included_ids,
			'manually_blocked_ids'  => $manually_blocked_ids,
			'context'               => $context,
		);

		if ( $context == 'div_dfrps_tab_search' ) {
			$msg .= '';
		} elseif ( $context == 'div_dfrps_tab_saved_search' ) {
			$msg .= '';
		} elseif ( $context == 'div_dfrps_tab_included' ) {
			$msg .= '';
		} elseif ( $context == 'div_dfrps_tab_blocked' ) {
			$msg .= '';
		}
	}

	// Loop through products and display them.
	echo $msg;

	// Query info
	if ( isset( $data['params'] ) && ! empty( $data['params'] ) ) { ?>
		<div class="dfrps_api_info" id="dfrps_raw_api_query">
			<div class="dfrps_head"><?php _e( 'API Request', 'datafeedr-product-sets' ); ?></div>
			<div class="dfrps_query"><span><?php echo dfrapi_display_api_request( $data['params'] ); ?></span></div>
		</div>
	<?php }

	echo dfrps_display_query_complexity_score( $data, $context );

	echo $pagination;
	echo '<div class="product_list">';
	if ( isset( $data['products'] ) && ! empty( $data['products'] ) ) {
		foreach ( $data['products'] as $product ) {
			dfrps_html_product_list( $product, $args );
		}
	}
	echo '</div>';
	echo $pagination;

}

/**
 * Returns the HTML alert letting the user know the complexity of their current search query.
 *
 * @param array $data
 * @param $context
 *
 * @return string
 */
function dfrps_display_query_complexity_score( $data, $context ) {

	$html            = '';
	$warning_percent = 0.7;

	/**
	 * If DFRAPI_COMPLEX_QUERY_SCORE is not defined that means "score" has not
	 * yet been added to the $data array because the user has not yet upgraded the
	 * Datafeedr API plugin to the latest version.
	 */
	if ( ! defined( 'DFRAPI_COMPLEX_QUERY_SCORE' ) ) {
		return $html;
	}

	// Just make sure the "score" item exists in the $data array.
	if ( ! isset( $data['score'] ) ) {
		return $html;
	}

	$score      = absint( $data['score'] ?? 0 );
	$label      = esc_html__( 'Query Complexity Score', 'datafeedr-product-sets' );
	$learn_more = esc_html__( 'Learn More', 'datafeedr-product-sets' );
	$doc_url    = esc_url( 'https://datafeedrapi.helpscoutdocs.com/article/255-calculating-api-query-complexity-score' );

	// Determine Query Complexity alert level.
	if ( $score >= DFRAPI_COMPLEX_QUERY_SCORE ) {
		$class = esc_attr( 'danger' );
		$title = esc_attr__( 'Search query is too complex. Please fix!', 'datafeedr-product-sets' );
	} elseif ( $score >= ( DFRAPI_COMPLEX_QUERY_SCORE * $warning_percent ) ) {
		$class = esc_attr( 'warning' );
		$title = esc_attr__( 'Search query is becoming too complex. Please forgo adding more search parameters.', 'datafeedr-product-sets' );
	} else {
		$class = esc_attr( 'success' );
		$title = esc_attr__( 'Search query complexity score is OK!', 'datafeedr-product-sets' );
	}

	$html .= sprintf( '<div class="dfrps-complex-query-alert dfrps-query-%s" title="%s">', $class, $title );
	$html .= sprintf( '<div><strong>%s:</strong> %s <span>/%s</span></div>', $label, number_format_i18n( $score ), number_format_i18n( DFRAPI_COMPLEX_QUERY_SCORE ) );
	$html .= sprintf( '<a href="%s" target="_blank"><small>%s</small></a>', $doc_url, $learn_more );
	$html .= '</div>';

	return $html;
}

function dfrps_more_info_rows( $product ) {

	$dfr_fields = array(
		'_id',
		'onsale',
		'merchant_id',
		'time_updated',
		'time_created',
		'source_id',
		'feed_id',
		'ref_url',
	);

	ksort( $product );
	$f = 1;
	foreach ( $product as $k => $v ) {
		$class1 = ( $f % 2 ) ? 'even' : 'odd';
		$class2 = ( in_array( $k, $dfr_fields ) ) ? ' dfrps_data' : '';
		echo '<tr class="' . $class1 . $class2 . '">';
		echo '<td class="count">' . $f . '</td>';
		echo '<td class="field">' . str_replace( array( "<", ">" ), array( "&lt;", "&gt;" ), $k ) . '</td>';
		if ( $k == 'image' || $k == 'thumbnail' ) {
			echo '
			<td class="value dfrps_force_wrap">
				<a href="' . $v . '" target="_blank" title="' . __( 'Open image in new window.', 'datafeedr-product-sets' ) . '">' . esc_attr( $v ) . '</a>
				<br />
				<img src="' . $v . '" style="max-width: 100%;" />
			</td>';
		} elseif ( $k == '_wc_url' ) {
			echo '
			<td class="value dfrps_force_wrap">
				<a href="' . $v . '" target="_blank" title="' . __( 'Search for product in store.', 'datafeedr-product-sets' ) . '">' . esc_attr( $v ) . '</a>
			</td>';
		} elseif ( $k == 'url' ) {
			echo '
			<td class="value dfrps_force_wrap">
				<a href="' . dfrapi_url( $product ) . '" target="_blank" title="' . __( 'Open affiliate link in new window.', 'datafeedr-product-sets' ) . '">' . esc_attr( dfrapi_url( $product ) ) . '</a>
			</td>';
		} elseif ( $k == 'ref_url' ) {
			echo '
			<td class="value dfrps_force_wrap">
				<a href="' . dfrapi_url( $product ) . '" target="_blank" title="' . __( 'Open affiliate link in new window.', 'datafeedr-product-sets' ) . '">' . esc_attr( dfrapi_url( $product ) ) . '</a>
			</td>';
		} elseif ( $k == 'direct_url' ) {
			echo '
			<td class="value dfrps_force_wrap">
				<a href="' . esc_url( $product[ $k ] ) . '" target="_blank" title="' . __( 'Open direct URL in new window.', 'datafeedr-product-sets' ) . '">' . esc_html( esc_url( $product[ $k ] ) ) . '</a>
			</td>';
		} elseif ( $k == 'impressionurl' && function_exists( 'dfrapi_impression_url' ) ) {
			echo '
			<td class="value dfrps_force_wrap">
				<a href="' . dfrapi_impression_url( $product ) . '" target="_blank" title="' . __( 'Open impression URL in new window.', 'datafeedr-product-sets' ) . '">' . esc_attr( dfrapi_impression_url( $product ) ) . '</a>
			</td>';
		} else {
			echo '<td class="value dfrps_force_wrap">' . esc_html( $v ) . '</td>';
		}
		echo '</tr>';
		$f ++;
	}
}

/**
 * This *estimates* the percentage of completion
 * of a product set.  It's just an estimate.
 */
function dfrps_percent_complete( $set_id ) {

	$meta = get_post_custom( $set_id );

	$update_phase = intval( $meta['_dfrps_cpt_update_phase'][0] );
	$last_update  = maybe_unserialize( $meta['_dfrps_cpt_previous_update_info'][0] );

	if ( $update_phase < 1 ) {
		return false;
	}

	if ( $last_update['_dfrps_cpt_last_update_time_completed'][0] == 0 ) {
		// There is no last update info (no iterations). Return percentage based on update phase.
		$percent = round( ( $update_phase / 5 ) * 100 );

		return $percent;
	}

	$current_iteration = intval( $meta['_dfrps_cpt_update_iteration'][0] );
	$total_iterations  = intval( $last_update['_dfrps_cpt_update_iteration'][0] );

	if ( $total_iterations > 0 ) {
		if ( $current_iteration <= $total_iterations ) {
			$percent = round( ( $current_iteration / $total_iterations ) * 100 );

			return $percent;
		} else {
			return 101;
		}
	}

	return false;
}

function dfrps_progress_bar( $percent ) {

	if ( ! $percent ) {
		return '';
	}

	if ( $percent <= 100 ) {

		return '
		<div id="dfrps_dynamic_progress_bar">
			<div><small>' . $percent . '% ' . __( 'complete', 'datafeedr-product-sets' ) . '</small></div>
			<div class="dfrps_progress">
				<div class="dfrps_progress-bar dfrps_progress-bar-success" role="progressbar" aria-valuenow="' . $percent . '" aria-valuemin="0" aria-valuemax="100" style="width: ' . $percent . '%">
					<span class="dfrps_sr-only">' . $percent . '% ' . __( 'complete', 'datafeedr-product-sets' ) . '</span>
				</div>
			</div>
		</div>
		';

	} else {

		return '
		<div id="dfrps_dynamic_progress_bar">
			<div><small><em>' . __( 'Unknown % complete', 'datafeedr-product-sets' ) . '</em></small></div>
		</div>
		';

	}
}

/**
 * Adds a Product ID to an existing or new postmeta value.
 */
function dfrps_helper_add_id_to_postmeta( $product_id, $post_id, $meta_key ) {

	// Get all Product IDs already stored for this $meta_key.
	$product_ids = get_post_meta( $post_id, $meta_key, true );

	// Add new $product_id to array of Product IDs.
	if ( ! empty( $product_ids ) ) {
		array_unshift( $product_ids, $product_id );
	} else {
		$product_ids = array( $product_id );
	}

	// Remove any empty array values.
	$product_ids = array_filter( $product_ids );

	// Update post meta.
	update_post_meta( $post_id, $meta_key, $product_ids );
}

/**
 * Removes a Product ID from an existing postmeta value.
 */
function dfrps_helper_remove_id_from_postmeta( $product_id, $post_id, $meta_key ) {

	// Get all Product IDs already stored for this $meta_key.
	$product_ids = get_post_meta( $post_id, $meta_key, true );

	if ( ! is_array( $product_ids ) ) {
		return;
	}

	// Remove Product ID from $product_ids array.
	$product_ids = array_diff( $product_ids, array( $product_id ) );

	// Remove any empty array values.
	$product_ids = array_filter( $product_ids );

	// Update post meta.
	update_post_meta( $post_id, $meta_key, $product_ids );
}

/**
 * This returns the text "Saving..." to JS.
 */
function dfrps_helper_js_text( $str ) {
	if ( $str == 'saving' ) {
		return __( "Saving...", 'datafeedr-product-sets' );
	} elseif ( $str == 'searching' ) {
		return __( "Searching...", 'datafeedr-product-sets' );
	} elseif ( $str == 'search' ) {
		return __( "Search", 'datafeedr-product-sets' );
	} elseif ( $str == 'deleting' ) {
		return __( "Deleting...", 'datafeedr-product-sets' );
	}
}

function dfrps_helper_include_product( $pid, $args ) {

	// Product has already been included?
	if ( in_array( $pid, $args['manually_included_ids'] ) ) {

		// What's the context of this page?
		if ( $args['context'] == 'div_dfrps_tab_search' ) {
			dfrps_html_included_product_icon();    // Search page, display "checkmark" icon.
		} elseif ( $args['context'] == 'div_dfrps_tab_included' ) {
			dfrps_html_remove_included_product_link( $pid ); // Included page, display "minus" icon/link.		
		}

		// Product has NOT already been included?
	} else {
		if ( $args['context'] != 'blocked' && $args['context'] != 'saved_search' ) {
			dfrps_html_include_product_link( $pid ); // Not already included and we're not in the "blocked" context, display "add" icon/link.
		}
	}

}

function dfrps_helper_block_product( $pid, $args ) {

	// Product has already been blocked?
	if ( in_array( $pid, $args['manually_blocked_ids'] ) ) {

		// What's the context of this page?
		if ( $args['context'] == 'div_dfrps_tab_blocked' ) {
			dfrps_html_unblock_product_link( $pid ); // Product is blocked, display "unblock" icon/link.
		}

		// Product has NOT already been blocked?
	} else {
		if ( $args['context'] != 'div_dfrps_tab_included' ) {
			dfrps_html_block_product_link( $pid ); // Not already blocked, display "block" icon/link.
		}
	}

}

function dfrps_date_in_two_rows( $date ) {
	if ( is_numeric( $date ) ) {
		//$html  = date('M d, G:i', $date );
		$html = '<div>' . date( 'M j', $date ) . ' ' . date( 'g:ia', $date ) . '</div>';
	} else {
		//$html  = date('M d, G:i', strtotime( $date ) );
		$html = '<div>' . date( 'M j', strtotime( $date ) ) . ' ' . date( 'g:ia', strtotime( $date ) ) . '</div>';
	}

	return $html;
}

function dfrps_registered_cpt_exists() {
	$registered_cpts     = get_option( 'dfrps_registered_cpts', array() );
	$num_registered_cpts = count( $registered_cpts );
	if ( $num_registered_cpts > 0 ) {
		return true;
	}

	return false;
}

function dfrps_default_cpt_is_selected() {
	$config      = get_option( 'dfrps_configuration', array() );
	$default_cpt = $config['default_cpt'];
	if ( ! is_array( $default_cpt ) ) {
		$default_cpt = array( $default_cpt );
	}
	$default_cpt = array_filter( $default_cpt );

	if ( ! empty( $default_cpt ) ) {
		return true;
	}

	return false;
}

/**
 * Returns the default CPT to import into. Returns FALSE if not set.
 */
function dfrps_get_default_cpt_type() {
	$configuration = (array) get_option( DFRPS_PREFIX . '_configuration' );
	$default_cpt   = ( ! empty( $configuration['default_cpt'] ) ) ? $configuration['default_cpt'] : false;

	return $default_cpt;
}

/**
 * Set Product sets CPT type to the default CPT type.
 */
function dfrps_set_cpt_type_to_default( $post_id ) {
	$default = dfrps_get_default_cpt_type();
	if ( $default ) {
		add_post_meta( $post_id, '_dfrps_cpt_type', $default, true );
	}
}

function dfrps_set_html_content_type() {
	return 'text/html';
}

function dfrps_reset_product_set_update( $set_id ) {

	// Update phase/added/deleted.
	update_post_meta( $set_id, '_dfrps_cpt_update_phase', 0 );

	// Delete first passes.
	for ( $i = 1; $i <= 10; $i ++ ) {
		delete_post_meta( $set_id, '_dfrps_cpt_update_phase' . $i . '_first_pass' );
	}
}

/**
 * Return array of term IDs that a product set is importing into.
 *
 * $set_id: Product Set ID
 */
function dfrps_get_cpt_terms( $set_id, $default = array() ) {
	// Related to Ticket: 9167
	$term_ids = get_post_meta( $set_id, '_dfrps_cpt_terms', true );
	if ( ! empty( $term_ids ) ) {
		$term_ids = array_map( 'intval', $term_ids );

		return $term_ids;
	}

	return $default;
}

/**
 * Returns current DB version if database is out of date.
 * Returns FALSE if DB is up to date.
 *
 * The constant DFRPS_DB_VERSION was added in version 1.2.0.
 *
 * @since 1.2.0
 */
function dfrps_db_is_outdated() {
	$current_db_version = get_option( 'dfrps_db_version', '1.0.0' );
	if ( version_compare( $current_db_version, DFRPS_DB_VERSION, '<' ) ) {
		return $current_db_version;
	}

	return false;
}

/**
 * Check if Product Set is active or inactive.
 *
 * This helper function returns true if the Product Set is currently active or inactive. 'active' means that the
 * custom post type which this Product Set imports into (ie. '_dfrps_cpt_type') currently is registered by its
 * corresponding importer plugin. For example, if the Datafeedr WooCommerce Importer plugin is not active, then passing
 * 'product' into this function will return false.
 *
 * @param string $set_type The 'post_type' to check registered_cpts against.
 *
 * @return boolean Return true if type is active, false if inactive.
 * @since 1.2.0
 *
 */
function dfrps_set_is_active( $set_type ) {
	$registered_cpts = get_option( 'dfrps_registered_cpts', array() );
	if ( ! in_array( $set_type, array_keys( $registered_cpts ) ) ) {
		return false;
	}

	return true;
}

/**
 * Upgrade Product Set to version 1.2.0.
 *
 * This upgrades a Product Set to version 1.2.0. This involves a number of actions:
 * - Set '_dfrps_cpt_type' to 'product'. Hardcoded because no other CPT existed before this version.
 * - Set Product Set as having been published. This prevents the Product Set from being imported into another CPT.
 * - Converts '_dfrps_cpt_categories' to '_dfrps_cpt_terms'.
 * - Delete deprecated post meta.
 * - Update Product Set version to 1.2.0.
 *
 * This is related to ticket #9167.
 *
 * @param mixed $post Should be a full Post Object, Full Post Array or a Post ID.
 *
 * @since 1.2.0
 *
 */
add_action( 'the_post', 'dfrps_upgrade_product_set_to_120', 20, 1 );
function dfrps_upgrade_product_set_to_120( $post ) {

	// Set $post_id and $post_type.
	if ( is_array( $post ) ) {
		$post_id   = $post['ID'];
		$post_type = ( isset( $post['post_type'] ) ) ? $post['post_type'] : '';
	} elseif ( is_object( $post ) ) {
		$post_id   = $post->ID;
		$post_type = ( isset( $post->post_type ) ) ? $post->post_type : '';
	} else {
		$post_id   = $post;
		$post_type = get_post_type( $post_id );
	}

	// Don't do anything if this is not a Datafeedr Product Set.
	if ( $post_type != DFRPS_CPT ) {
		return true;
	}

	// If we've already updated this Product Set, skip it.
	$current_set_version = get_metadata( 'post', $post_id, '_dfrps_current_version', true );
	if ( version_compare( $current_set_version, '1.2.0', '>=' ) ) {
		return true;
	}

	// Hardcoded because no other CPTs existed except 'product' before v1.2.0.
	add_post_meta( $post_id, '_dfrps_cpt_type', 'product', true );

	// Set Product Set as having been published.
	update_post_meta( $post_id, '_dfrps_has_been_published', true );

	// Get categories this Set is associated with.
	$cpt_categories = get_post_meta( $post_id, '_dfrps_cpt_categories', true );

	// Set term IDs. Hardcoded because no other CPTs existed before 'product' before version 1.2.0.
	$term_ids = ( isset( $cpt_categories['product'] ) && ! empty( $cpt_categories['product'] ) )
		? $cpt_categories['product']
		: array();

	if ( ! empty( $term_ids ) ) {
		$term_ids = array_map( 'intval', $term_ids );
		add_post_meta( $post_id, '_dfrps_cpt_terms', $term_ids, true );
	}

	// Delete deprecated post meta.
	delete_metadata( 'post', $post_id, '_dfrps_option_ids' ); // This is leftover from pre-beta.
	delete_metadata( 'post', $post_id, '_dfrps_cpt_categories' );
	delete_metadata( 'post', $post_id, '_dfrps_cpt_categories_history' );
	delete_metadata( 'post', $post_id, '_dfrps_cpt_import_into' );

	// Set Product Set as updated to v1.2.0
	update_post_meta( $post_id, '_dfrps_current_version', '1.2.0' );

	return true;
}

/**
 * Returns a Post Object which contains a matching meta_key and meta_value. If
 * no Post Object is found, then returns false.
 *
 * Example:
 *
 *      $meta_key   = '_dfrps_product_id';
 *      $meta_value = '3853200001322292';
 *      $compare    = 'IN';
 *
 *      $post = dfrps_get_post_obj_by_meta_value( $meta_key, $meta_value, $compare );
 *
 * @param string $meta_key The post_meta key.
 * @param string|array $meta_value The post_meta value.
 * @param string $compare The operator to use in the query.
 *
 * @return bool|WP_Post Return Post Object or false if nothing found.
 * @since 1.2.6
 *
 */
function dfrps_get_post_obj_by_postmeta( $meta_key, $meta_value, $compare ) {

	$args = array(
		'orderby'        => 'date',
		'order'          => 'DESC',
		'post_type'      => 'any',
		'post_status'    => array(
			'publish',
			'pending',
			'draft',
			'auto-draft',
			'future',
			'private',
			'inherit',
			'trash',
		),
		'posts_per_page' => '1',
		'meta_query'     => array(
			array(
				'key'     => $meta_key,
				'value'   => $meta_value,
				'compare' => $compare,
			)
		)
	);

	$posts = new WP_Query( $args );

	if ( $posts->have_posts() ) {

		while ( $posts->have_posts() ) {
			$posts->the_post();

			return $posts->post;
		}

	}

	wp_reset_postdata();

	return false;

}

/**
 * Returns a URL for installing a plugin.
 *
 * @param string $plugin_file Plugin file name formatted like: woocommerce/woocommerce.php
 *
 * @return string URL or empty string if user is not allowed.
 * @since 1.2.18
 *
 */
function dfrps_plugin_installation_url( $plugin_file ) {

	if ( ! current_user_can( 'install_plugins' ) ) {
		return '';
	}

	$plugin_name = explode( '/', $plugin_file );
	$plugin_name = str_replace( '.php', '', $plugin_name[1] );

	$url = add_query_arg( array(
		'action' => 'install-plugin',
		'plugin' => $plugin_name
	), wp_nonce_url( admin_url( 'update.php' ), 'install-plugin_' . $plugin_name ) );

	return $url;
}

/**
 * Returns a URL for activating a plugin.
 *
 * @param string $plugin_file Plugin file name formatted like: woocommerce/woocommerce.php
 *
 * @return string URL or empty string if user is not allowed.
 * @since 1.2.18
 *
 */
function dfrps_plugin_activation_url( $plugin_file ) {

	if ( ! current_user_can( 'activate_plugin', $plugin_file ) ) {
		return '';
	}

	$url = add_query_arg( array(
		'action' => 'activate',
		'plugin' => urlencode( $plugin_file ),
		'paged'  => '1',
		's'      => '',
	), wp_nonce_url( admin_url( 'plugins.php' ), 'activate-plugin_' . $plugin_file ) );

	return $url;
}

/**
 * Returns an unserialized version of the Datafeedr $product if the $post_id
 * is associated with a Datafeedr product.
 *
 * If the '_dfrps_product' meta_key is not found, this product was not added by the
 * Datafeedr Product Sets plugin. So this function will return an empty string.
 *
 * @param int $post_id Post ID
 *
 * @return array|string A Datafeedr Product array or an empty string if no product exists for $post_id.
 */
function dfrps_product( $post_id ) {
	return get_post_meta( $post_id, '_dfrps_product', true );
}

/**
 * Returns a value from a $field from the '_dfrps_product' array if it exists.
 * Otherwise, returns the $default value.
 *
 * @param int $post_id
 * @param string $field
 * @param mixed $default
 *
 * @return mixed
 */
function dfrps_get_product_field( $post_id, $field, $default = false ) {

	$fields = dfrps_product( $post_id );

	if ( ! is_array( $fields ) ) {
		return $default;
	}

	if ( ! isset( $fields[ $field ] ) ) {
		return $default;
	}

	return $fields[ $field ];
}

/**
 * Returns true if we should try to import an image for the Post.
 *
 * @param int $post_id
 *
 * @return true|WP_Error True if we should try to import an image for this $post_id. Otherwise returns WP_Error.
 */
function dfrps_do_import_product_thumbnail( $post_id ) {

	$post = get_post( $post_id );

	/**
	 * If post is of the type "attachment", return WP_Error.
	 */
	if ( $post->post_type == 'attachment' ) {
		return new WP_Error(
			'dfrps_cannot_import_attachment_of_attachment',
			__( 'Invalid $post->post_type.', 'datafeedr-product-sets' ),
			array( 'function' => __FUNCTION__, '$post' => $post )
		);
	}

	/**
	 * If $post already has a thumbnail, return WP_Error.
	 */
	if ( has_post_thumbnail( $post ) ) {
		$thumbnail_id = absint( get_post_thumbnail_id( $post->ID ) );

		/**
		 * Add this check here because for some reason we get past the
		 * has_post_thumbnail() check even through $thumbnail_id could be
		 * equal to 0.
		 *
		 * So we make sure that $thumbnail_id is actually greater than 0 (ie. an actual ID).
		 *
		 * @since 1.3.10 2022-02-02
		 */
		if ( $thumbnail_id > 0 ) {
			return new WP_Error(
				'dfrps_post_already_has_thumbnail',

				__( 'This $post already has a thumbnail with an ID of ' . $thumbnail_id, 'datafeedr-product-sets' ),
				[ 'function' => __FUNCTION__, '$post' => $post ]
			);
		}
	}

	/**
	 * If $post->post_type is not a registered CPT, return WP_Error.
	 */
	if ( ! dfrps_post_is_registered_cpt( $post->ID ) ) {
		return new WP_Error(
			'dfrps_post_type_is_not_registered_cpt',
			__( 'The $post->post_type "' . esc_html( $post->post_type ) . '" is not a registered CPT.', 'datafeedr-product-sets' ),
			[ 'function' => __FUNCTION__, '$post' => $post ]
		);
	}

	/**
	 * If this $post is not associated with any Datafeedr product, return WP_Error.
	 */
	$product = dfrps_product( $post->ID );
	if ( empty( $product ) ) {
		return new WP_Error(
			'dfrps_post_not_associated_with_datafeedr_product',
			__( 'This $post is not associated with any product imported by the Datafeedr Product Sets plugin.', 'datafeedr-product-sets' ),
			[ 'function' => __FUNCTION__, '$post' => $post ]
		);
	}

	/**
	 * If we have already attempted to import an image for this product
	 * since the last Product Set update, return WP_Error.
	 */
	if ( dfrps_image_import_attempted( $post->ID, '_dfrps_product_check_image' ) ) {
		return new WP_Error(
			'dfrps_image_import_already_attempted',
			__( 'The image import for this $post was already attempted since the product\'s Product Set\'s last update. No additional attempts will be made until after this product\'s Product Set\'s next update.', 'datafeedr-product-sets' ),
			[ 'function' => __FUNCTION__, '$post' => $post ]
		);
	}

	/**
	 * Don't import images for products which are not valid posts statuses.
	 */
	$valid_post_statuses = apply_filters( 'dfrps_valid_post_statuses_for_thumbnail_import', [
		'publish',
		'draft',
	], $post );

	if ( ! in_array( $post->post_status, $valid_post_statuses ) ) {
		return new WP_Error(
			'dfrps_invalid_post_status_for_importing_images',
			sprintf(
				__( 'Products with a post_status of "%s" will not have their images imported.', 'datafeedr-product-sets' ),
				esc_html( $post->post_status )
			),
			[ 'function' => __FUNCTION__, '$post' => $post, '$valid_post_statuses' => $valid_post_statuses ]
		);
	}

	$do_import = true;

	/**
	 * Allow user to override returning true.
	 *
	 * @param bool $do_import true
	 * @param WP_Post $post
	 * @param array $product A Datafeedr Product array.
	 *
	 * @since 1.2.22
	 *
	 */
	$do_import = apply_filters( 'dfrps_do_import_product_thumbnail/do_import', $do_import, $post, $product );

	return $do_import;
}

/**
 * Returns the feature image URL for this $post_id or an empty string if none exists.
 *
 * @param int $post_id Post ID
 *
 * @return string
 * @since 1.2.22
 *
 */
function dfrps_featured_image_url( $post_id ) {

	$url = get_post_meta( $post_id, '_dfrps_featured_image_url', true );

	/**
	 * Allows the $url string to be modified before returning.
	 *
	 * @param string $url URL of image to send to Datafeedr_Image_Importer constructor.
	 * @param int $post_id Post ID
	 *
	 * @since 1.2.22
	 *
	 */
	return apply_filters( 'dfrps_featured_image_url/url', $url, $post_id );
}

/**
 * Import an image as the featured image (ie. thumbnail) for a
 * given $post_id.
 *
 * Returns an instance of Datafeedr_Image_Importer if the import was successful, otherwise
 * returns an instance of WP_Error.
 *
 * @param int $post_id
 *
 * @return Datafeedr_Image_Importer|int|WP_Error
 * @since 1.3.2 Will return either the Attachment ID or WP_Error if there was an error importing the image.
 *
 * @since 1.0.71
 */
function dfrps_import_post_thumbnail( $post_id ) {

	$post = get_post( $post_id );

	/**
	 * If no $post found, return WP_Error.
	 */
	if ( ! $post ) {
		return new WP_Error(
			'dfrps_post_not_found',
			__( 'Invalid $post_id.', 'datafeedr-product-sets' ),
			array( 'function' => __FUNCTION__, '$post_id' => $post_id, '$post' => $post )
		);
	}

	$do_import_thumbnail = dfrps_do_import_product_thumbnail( $post->ID );

	if ( is_wp_error( $do_import_thumbnail ) ) {
		return $do_import_thumbnail;
	}

	$url = dfrps_featured_image_url( $post->ID );

	if ( empty( $url ) ) {
		dfrps_set_product_check_image( $post->ID, 0 );

		return new WP_Error(
			'dfrps_url_empty',
			__( '$url is empty.', 'datafeedr-product-sets' ),
			array( 'function' => __FUNCTION__, '$post' => $post, '$url' => $url )
		);
	}

	$args = array(
		'title'             => $post->post_title,
		'file_name'         => $post->post_title,
		'post_id'           => $post->ID,
		'description'       => $post->post_content,
		'caption'           => $post->post_title,
		'alt_text'          => $post->post_title,
		'user_id'           => $post->post_author,
		'is_post_thumbnail' => true,
		'timeout'           => 10,
		'_source_plugin'    => 'dfrps',
	);

	/**
	 * Allows the $args array to be modified before importing the image.
	 *
	 * See Datafeedr_Image_Importer::default_args() for possible values.
	 *
	 * @param array $args Array of args to send to Datafeedr_Image_Importer constructor.
	 * @param string $url URL of image to send to Datafeedr_Image_Importer constructor.
	 * @param WP_Post $post
	 *
	 * @since 1.2.22
	 *
	 */
	$args = apply_filters( 'dfrps_import_post_thumbnail/args', $args, $url, $post );

	$image_importer = datafeedr_import_image( $url, $args );

	dfrps_set_product_check_image( $post->ID, 0 );

	return $image_importer;
}

/**
 * Checks whether or not the $post->post_type is in the registered Custom Post Types array.
 *
 * Returns true if $post is a registered CPT, otherwise returns false.
 *
 * If $post->post_type is not a registered CPT, return false.
 *
 * @param int $post_id
 *
 * @return bool
 * @since 1.2.27
 *
 */
function dfrps_post_is_registered_cpt( $post_id ) {
	$post            = get_post( $post_id );
	$registered_cpts = get_option( 'dfrps_registered_cpts', array() );
	if ( array_key_exists( $post->post_type, $registered_cpts ) ) {
		return true;
	}

	return false;
}

/**
 * If we have already attempted to import an image for this $post_id
 * with this key, return true. Otherwise return false.
 *
 * @param integer $post_id
 * @param string $key The meta_key to check.
 *
 * @return bool
 * @since 1.2.27
 *
 */
function dfrps_image_import_attempted( $post_id, $key ) {

	$result = get_post_meta( $post_id, $key, true );

	/**
	 * If $key is not found, the result will be an empty string meaning
	 * no attempt has been made to import an image for this key.
	 */
	if ( '' == $result ) {
		return false;
	}

	/**
	 * If the $result is 1, this means we may have attempted to import this image previously
	 * but for some reason it failed or the value was overwritten (ie. by the WooCommerce Importer plugin)
	 * and therefore we should check again.
	 */
	if ( '1' == $result ) {
		return false;
	}

	/**
	 * If we made it this far, we return true indicating that we HAVE attempted
	 * to import the image for this key and we should not try again.
	 */
	return true;
}

/**
 * Update "_dfrps_product_check_image" post_meta value.
 *
 * @param integer $post_id ID of the Product we are updating
 * @param integer $value Either 1 or 0. 1 if product's image should be checked else 0 if it should not be checked.
 *
 * @since 1.2.29
 *
 */
function dfrps_set_product_check_image( $post_id, $value ) {
	$value = ( 1 == $value ) ? 1 : 0;
	update_post_meta( $post_id, '_dfrps_product_check_image', $value );
}

/**
 * @param string $message
 * @param null $message_type
 * @param null $destination
 * @param null $additional_headers
 */
function dfrps_error_log( $message, $message_type = null, $destination = null, $additional_headers = null ) {
	if ( apply_filters( 'dfrps_log_errors', false ) ) {
		error_log( $message, $message_type, $destination, $additional_headers );
	}
}

/**
 * Determines if a post ID is that of an existing Product Set.
 *
 * @param int $product_set_id ID of Product Set to check.
 *
 * @return bool True if the Product Set exists; otherwise, false.
 * @since 1.3.21
 */
function dfrps_product_set_exists( int $product_set_id ): bool {
	return get_post_type( $product_set_id ) === DFRPS_CPT;
}
