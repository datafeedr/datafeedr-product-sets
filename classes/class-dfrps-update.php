<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if ( ! class_exists( 'Dfrps_Update' ) ) {

/**
 * Product Set Updater
 */
class Dfrps_Update {

	/**
	 * @var string Name of temporary table to store product IDs and data.
	 */
	private $temp_product_table;

	public function __construct( $post ) {
		global $wpdb;
		$this->temp_product_table = $wpdb->prefix . 'dfrps_temp_product_data';
		$this->action = 'update';
		$this->set = $post;
		$this->config = $this->get_configuration();
		$this->meta = $this->get_postmeta();
		$this->phase = 1;
		$this->set['postmeta'] = $this->meta;
		$this->update();
	}

	// Get user's configuration settings.
	function get_configuration() {
		return get_option( 'dfrps_configuration' );
	}

	// Load post meta.
	function get_postmeta() {
		return get_post_custom( $this->set['ID'] );
	}

	// Get the current phase of the update.
	function current_phase() {
		$phase = intval( get_post_meta( $this->set['ID'], '_dfrps_cpt_update_phase', TRUE ) );
		if ( $phase == 0 ) {
			$phase = 1;
			update_post_meta( $this->set['ID'], '_dfrps_cpt_update_phase', $phase );
		}
		return $phase;
	}

	// Get the CPT that this Product Set will import into.
	function get_cpt_type() {
		$type = get_post_meta( $this->set['ID'], '_dfrps_cpt_type', TRUE );
		return $type;
	}

	// Create temporary product table.
	function create_temp_product_table() {
		global $wpdb;
		$table = $this->temp_product_table;
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "
		CREATE TABLE IF NOT EXISTS $table
		(
			product_id varchar(50) DEFAULT '' PRIMARY KEY,
			data LONGTEXT,
			uid varchar(13) NOT NULL default '',
			updated TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 			KEY uid (uid)
		) $charset_collate ";
	   	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	// Insert into temporary product table.
	function insert_temp_product( $product ) {
		if ( ! isset( $product['_id'] ) || ! isset( $product['url'] ) ) {
			return false;
		}
		global $wpdb;
		$table  = $this->temp_product_table;
		$data   = array( 'product_id' => $product['_id'], 'data' => serialize( $product ) );
		$result = $wpdb->replace( $table, $data );
		// return TRUE; Removed in v1.1.8
	}

	// Get products from temp table to update.
	function select_products_for_update() {

		global $wpdb;

		// Set temp product table variable name.
		$table_name = $this->temp_product_table;

		// Set how many products should be updated in one pass.
		$limit = ( isset( $this->config['num_products_per_update'] ) ) ? intval( $this->config['num_products_per_update'] ) : 100;

		// Allow for 5 minutes to import 20 products. That should be enough...
		$mysql_interval = abs( ceil( $limit / 20 ) );

		// Update the temp product table to avoid the table remaining 'locked' (ticket #10889).
		$wpdb->query( "UPDATE $table_name SET uid='' WHERE uid != '' AND updated < DATE_SUB(NOW(), INTERVAL $mysql_interval MINUTE)" );

		// If other uids are in the table that means the update is already in progress. Return 'busy'. (ticket #10886)
		$pre_check = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE uid != ''" );
		if ( intval( $pre_check ) ) {
			return 'busy';
		}

		// Set a unique ID and update temp table with UID to avoid updating same product twice (ticket #10866).
		$uid = uniqid();
		$wpdb->query( "UPDATE $table_name SET uid='$uid' WHERE uid='' ORDER BY updated DESC LIMIT " . $limit );

		// Get products from temp table to update.
		$products = $wpdb->get_results( "SELECT * FROM $table_name WHERE uid='$uid' ORDER BY updated DESC", ARRAY_A );

		return $products;
	}

	// Delete a product record from the temp table.
	function delete_product_from_table( $id ) {
		global $wpdb;
		$table = $this->temp_product_table;
		$wpdb->delete( $table, array( 'product_id' => $id ) );
	}

	// Drop the temp product table.
	function drop_temp_product_table() {
		global $wpdb;
		$table = $this->temp_product_table;
		$wpdb->query( "DROP TABLE IF EXISTS $table" );
	}

	// Run update.
	function update() {

		wp_suspend_cache_addition( true );
		wp_suspend_cache_invalidation( true );

		// Set transient to 5 minutes time + cron interval.
		$cron_interval = intval( $this->config['cron_interval'] );
		$use_cache = wp_using_ext_object_cache( false );
		set_transient( 'dfrps_doing_update', 1, ( ( MINUTE_IN_SECONDS * 5 ) + $cron_interval ) );
		wp_using_ext_object_cache( $use_cache );

		// Begin endless loop
		while( 1 ) {

			// Get current phase.
			$current_phase = $this->current_phase();

			do_action( 'dfrps_begin_update_phase_' . $current_phase, $current_phase, $this );

			// Create method name.
			$phase = 'phase' . $current_phase;

			// Check if method exists.
			if ( ! method_exists( $this, $phase ) ) {
				break;
			}

			// Call method and get results of method.
			// Results: skip, ready, repeat, complete
			$result = $this->$phase();

			// Skip phase altogether.
			if ( $result == 'skip' ) {
				update_post_meta( $this->set['ID'], '_dfrps_cpt_update_phase', ( $current_phase + 1 ) );
				do_action( 'dfrps_end_update_phase_' . $current_phase, $current_phase, $this );
				continue; // repeat the loop
			}

			// Phase is complete.
			if ( $result == 'ready' ) {
				update_post_meta( $this->set['ID'], '_dfrps_cpt_update_phase', ( $current_phase + 1 ) );
				do_action( 'dfrps_end_update_phase_' . $current_phase, $current_phase, $this );
				break;  // update phase and stop the loop
			}

			// Phase is not complete, repeat it.
			if ( $result == 'repeat' ) {
				do_action( 'dfrps_end_update_phase_' . $current_phase, $current_phase, $this );
				break; // don't update phase, just stop the loop
			}

			// Full update is complete, update phase and break.
			if ( $result == 'complete' ) {
				update_post_meta( $this->set['ID'], '_dfrps_cpt_update_phase', 0 );
				do_action( 'dfrps_end_update_phase_' . $current_phase, $current_phase, $this );
				break;  // update phase and stop the loop
			}

		} // while( 1 ) {

		$use_cache = wp_using_ext_object_cache( false );
		delete_transient( 'dfrps_doing_update' );
		wp_using_ext_object_cache( $use_cache );

		wp_suspend_cache_addition( false );
		wp_suspend_cache_invalidation( false );
	}

	// Count each iteration of the update process.
	function count_iteration() {
		$iteration = get_post_meta( $this->set['ID'], '_dfrps_cpt_update_iteration', true );
		if( !empty( $iteration ) ) {
			$iteration = intval( $iteration );
			$iteration = ( $iteration + 1 );
			update_post_meta( $this->set['ID'], '_dfrps_cpt_update_iteration', $iteration );
		} else {
			$iteration = 1;
			add_post_meta( $this->set['ID'], '_dfrps_cpt_update_iteration', $iteration, true );
		}
	}

	function preprocess_complete_check() {
		$complete = get_post_meta( $this->set['ID'], '_dfrps_preprocess_complete_' . $this->get_cpt_type(), true );
		if ( empty( $complete ) ) {
			return false;
		}
		return true;
	}

	function postprocess_complete_check() {
		$complete = get_post_meta( $this->set['ID'], '_dfrps_postprocess_complete_' . $this->get_cpt_type(), true );
		if ( empty( $complete ) ) {
			return false;
		}
		return true;
	}

	function is_first_pass() {
		$first_pass = get_post_meta( $this->set['ID'], '_dfrps_cpt_update_phase' . $this->phase . '_first_pass', true );
		if ( empty( $first_pass ) ) {
			// This is the first pass for this phase.
			add_post_meta( $this->set['ID'], '_dfrps_cpt_update_phase' . $this->phase . '_first_pass', true, true );
			return TRUE;
		}
		return FALSE;
	}

	function delete_first_passes() {
		for( $i=1; $i<=5; $i++ ) {
			delete_post_meta( $this->set['ID'], '_dfrps_cpt_update_phase' . $i . '_first_pass' );
		}
	}

	function defer_counting( $bool ) {
		wp_defer_term_counting( $bool );
		wp_defer_comment_counting( $bool );
	}

	function handle_error( $data ) {

		// Regarding Ticket #8262
		$class = $data['dfrapi_api_error']['class'];
		$msg   = isset( $data['dfrapi_api_error']['msg'] ) ? trim( $data['dfrapi_api_error']['msg'] ) : '';

		// These are the ERROR classes that trigger updates to be disabled.
		$error_classes = apply_filters( 'dfrps_disable_updates_error_classes', array(
			'DatafeedrBadRequestError',
			'DatafeedrAuthenticationError',
			'DatafeedrLimitExceededError',
			'DatafeedrQueryError',
		) );

		// These are the ERROR messages that trigger updates to be disabled.
		$error_messages = apply_filters( 'dfrps_disable_updates_error_messages', array(
			'No merchants selected',
		) );

		if ( in_array( $class, $error_classes ) ) {
			$this->config['updates_enabled'] = 'disabled';
			update_option( 'dfrps_configuration', $this->config );
			$this->updates_disabled_email_user( $data );
			do_action( 'dfrps_product_set_updates_disabled', $class, $data, $this );
		} elseif ( in_array( $msg, $error_messages ) ) {
			$this->config['updates_enabled'] = 'disabled';
			update_option( 'dfrps_configuration', $this->config );
			$this->updates_disabled_email_user( $data );
			do_action( 'dfrps_product_set_updates_disabled', $msg, $data, $this );
		}
	}

	function updates_disabled_email_user( $obj ) {

		$params            = array();
		$params['to']      = get_bloginfo( 'admin_email' );
		$params['subject'] = get_bloginfo( 'name' ) . __( ': Datafeedr API Message (Product Set Update Failed)', 'datafeedr-product-sets' );

		$params['message']  = "<p>" . __( "This is an automated message generated by: ", 'datafeedr-product-sets' ) . get_bloginfo( 'wpurl' ) . "</p>";
		$params['message'] .= "<p>" . __( "An error occurred during the update of the ", 'datafeedr-product-sets' );
		$params['message'] .= "<a href=\"" . admin_url( 'post.php?post=' . $this->set['ID'] . '&action=edit' ) . "\">" . $this->set['post_title'] . "</a>";
		$params['message'] .= __( " product set.", 'datafeedr-product-sets' ) . "</p>";

		if ( isset( $obj['dfrapi_api_error']['class'] ) ) {

			// Have we exceeded the API request limit?
			if ( $obj['dfrapi_api_error']['class'] == 'DatafeedrLimitExceededError' ) {

				$params['message'] .= "<p>" . __( "You have used <strong>100%</strong> of your allocated Datafeedr API requests for this period. <u>You are no longer able to query the Datafeedr API to get product information.</u>", 'datafeedr-product-sets' ) . "</p>";
				$params['message'] .= "<p><strong>" . __( "What to do next?", 'datafeedr-product-sets' ) . "</strong></p>";
				$params['message'] .= "<p>" . __( "We strongly recommend that you upgrade to prevent your product information from becoming outdated.", 'datafeedr-product-sets' ) . "</p>";
				$params['message'] .= "<p><a href=\"" . dfrapi_user_pages( 'change' ) . "?utm_source=email&utm_medium=link&utm_campaign=updatesdisablednotice\"><strong>" . __( "UPGRADE NOW", 'datafeedr-product-sets' ) . "</strong></a></p>";
				$params['message'] .= "<p>" . __( "Upgrading only takes a minute. You will have <strong>instant access</strong> to more API requests. Any remaining credit for your current plan will be applied to your new plan.", 'datafeedr-product-sets' ) . "</p>";
				$params['message'] .= "<p>" . __( "You are under no obligation to upgrade. You may continue using your current plan for as long as you would like.", 'datafeedr-product-sets' ) . "</p>";

			} else {

				$params['message'] .= "<p>" . __( "The details of the error are below.", 'datafeedr-product-sets' ) . "</p>";
				$params['message'] .= "<tt>";
				$params['message'] .= "#################################################<br />";
				$params['message'] .= __( "CLASS: ", 'datafeedr-product-sets' ) . $obj['dfrapi_api_error']['class'] . "<br />";
				$params['message'] .= __( "CODE: ", 'datafeedr-product-sets' ) . $obj['dfrapi_api_error']['code'] . "<br />";
				$params['message'] .= __( "MESSAGE: ", 'datafeedr-product-sets' ) . $obj['dfrapi_api_error']['msg'] . "<br />";
				if ( !empty( $obj['dfrapi_api_error']['params'] ) ) {
					$query = dfrapi_display_api_request( $obj['dfrapi_api_error']['params'] );
					$params['message'] .= __( "<br />QUERY:<br />", 'datafeedr-product-sets' ) . $query . "<br />";
				}
				$params['message'] .= "#################################################";
				$params['message'] .= "</tt>";
			}
		}

		$params['message'] .= "<p>" . __( "In the meantime, all product updates have been disabled on your site. After you fix this problem you will need to ", 'datafeedr-product-sets' );
		$params['message'] .= "<a href=\"" . admin_url( 'admin.php?page=dfrps_configuration' ) . "\">" . __( "enable updates again", 'datafeedr-product-sets' ) . ".</p>";
		$params['message'] .= "<p>" . __( "If you have any questions about your account, please ", 'datafeedr-product-sets' );
		$params['message'] .= "<a href=\"" . DFRAPI_EMAIL_US_URL . "?utm_source=email&utm_medium=link&utm_campaign=updatesdisablednotice\">" . __( "contact us", 'datafeedr-product-sets' ) . "</a>.</p>";
		$params['message'] .= "<p>" . __( "Thanks,<br />Eric &amp; Stefan<br />The Datafeedr Team", 'datafeedr-product-sets' ) . "</p>";

		$params['message'] .= "<p>";
		$params['message'] .= "<a href=\"" . admin_url( 'admin.php?page=dfrapi_account' ) . "\">" . __( "Account Information", 'datafeedr-product-sets' ) . "</a> | ";
		$params['message'] .= "<a href=\"" . dfrapi_user_pages( 'change' ) . "?utm_source=email&utm_medium=link&utm_campaign=updatesdisablednotice\">" . __( "Upgrade Account", 'datafeedr-product-sets' ) . "</a> | ";
		$params['message'] .= "<a href=\"" . admin_url( 'admin.php?page=dfrps_configuration' ) . "\">" . __( "Enable Updates", 'datafeedr-product-sets' ) . "</a>";
		$params['message'] .= "</p>";

		add_filter( 'wp_mail_content_type', 'dfrps_set_html_content_type' );
		wp_mail( $params['to'], $params['subject'], $params['message'] );
		remove_filter( 'wp_mail_content_type', 'dfrps_set_html_content_type' );
	}

	// Phase 1, initialize update, set variables and update phase.
	function phase1() {

		$this->phase = 1;

		if( $this->is_first_pass() ) {

			// Set preprocess incomplete for CPT type that this set imports into.
			update_post_meta( $this->set['ID'], '_dfrps_preprocess_complete_' . $this->get_cpt_type(), false );

			delete_post_meta( $this->set['ID'], '_dfrps_cpt_errors' );

			unset( $this->meta['_dfrps_cpt_previous_update_info'] ); // Unset so array item is not duplicated
			update_post_meta( $this->set['ID'], '_dfrps_cpt_previous_update_info', $this->meta );
			update_post_meta( $this->set['ID'], '_dfrps_cpt_update_iteration', 1 );
			update_post_meta( $this->set['ID'], '_dfrps_cpt_offset', 1 );
			update_post_meta( $this->set['ID'], '_dfrps_cpt_last_update_time_started', date_i18n( 'U' ) );
			update_post_meta( $this->set['ID'], '_dfrps_cpt_last_update_time_completed', 0 );
			update_post_meta( $this->set['ID'], '_dfrps_cpt_last_update_num_products_added', 0 );
			update_post_meta( $this->set['ID'], '_dfrps_cpt_last_update_num_api_requests', 0 );
			update_post_meta( $this->set['ID'], '_dfrps_cpt_last_update_num_products_deleted', 0 );
		} else {
			$this->count_iteration();
		}

		do_action( 'dfrps_preprocess-' . $this->get_cpt_type(), $this );

		// Check if preprocess is complete (determined by importer scripts)
		$preprocess_complete = $this->preprocess_complete_check();

		// Move to phase 2 ONLY if all posts have been unset from their categories.
		if ( $preprocess_complete ) {
			// DROP TABLE to remove any remaining products from this table.
			// Products could still be in this table after a Product Set was moved to Trash.
			$this->drop_temp_product_table();
			$this->create_temp_product_table();
			return "ready";
		}

		return 'repeat';
	}

	// Phase 2, import saved search products into the options table.
	function phase2() {

		$this->phase = 2;

		$this->count_iteration();

		$query = ( isset( $this->meta['_dfrps_cpt_query'][0] ) ) ? maybe_unserialize( $this->meta['_dfrps_cpt_query'][0] ) : array();

		// Check that a saved search exists and move on if it doesn't.
		if ( empty( $query ) ) {
			return 'skip';
		}

		// Get manually blocked product IDs.
		$blocked = get_post_meta( $this->set['ID'], '_dfrps_cpt_manually_blocked_ids', true );
		$manually_blocked = ( is_array( $blocked ) && !empty( $blocked ) ) ? $blocked : array();

		// Run query.
		//$data = dfrapi_api_get_products_by_query( $query, $this->config['num_products_per_update'], $this->meta['_dfrps_cpt_offset'][0], $manually_blocked );
		$query = apply_filters( 'dfrps_update_phase2_query', $query, $this );
		$data = dfrapi_api_get_products_by_query( $query, $this->config['num_products_per_api_request'], $this->meta['_dfrps_cpt_offset'][0], $manually_blocked );

		// Update number of API requests.
		update_post_meta( $this->set['ID'], '_dfrps_cpt_last_update_num_api_requests', ( $this->meta['_dfrps_cpt_last_update_num_api_requests'][0] + 1 ) );

		// Handle errors & return.
		if ( is_array( $data ) && array_key_exists( 'dfrapi_api_error', $data ) ) {
			update_post_meta( $this->set['ID'], '_dfrps_cpt_errors', $data );
			$this->handle_error( $data );
			return 'repeat';
		}

		// Delete any errors that are currently being stored.
		update_post_meta( $this->set['ID'], '_dfrps_cpt_errors', false );

		// If there are products, store product data in options table.
		if ( isset( $data['products'] ) && !empty( $data['products'] ) ) {

			foreach ( $data['products'] as $product ) {
				$this->insert_temp_product( $product );
			}

		} else {

			// No products, update "Phase".
			update_post_meta( $this->set['ID'], '_dfrps_cpt_offset', 1 );
			return 'ready';
		}

		// All products in this batch have been imported into the options table.  Now update some meta stuff.
		update_post_meta( $this->set['ID'], '_dfrps_cpt_offset', ( $this->meta['_dfrps_cpt_offset'][0] + 1 ) );
		update_post_meta( $this->set['ID'], '_dfrps_cpt_last_update_num_products_added', ( $this->meta['_dfrps_cpt_last_update_num_products_added'][0] + count( $data['products'] ) ) );

		// If the number of products is less than the number of products per update
		// (that means subsequent queries wont return any more products).
		// Move to next phase so as not to incur 1 additional API request.
		if ( ( count( $data['products'] ) < $this->config['num_products_per_api_request'] ) ) {
			update_post_meta( $this->set['ID'], '_dfrps_cpt_offset', 1 );
			return 'ready';
		}

		return 'repeat';
	}

	// Phase 3, import single products into the options table.
	function phase3() {

		$this->phase = 3;

		$this->count_iteration();

		// Get included IDs and remove any duplicates or empty values.
		$ids = get_post_meta( $this->set['ID'], '_dfrps_cpt_manually_added_ids', true );
		$ids = array_filter( (array) $ids );

		// If no IDs, update phase and go to Phase 3.
		if ( empty( $ids ) ) {
			return 'skip';
		}

		// Query API
		//$data = dfrapi_api_get_products_by_id( $ids, $this->config['num_products_per_update'], $this->meta['_dfrps_cpt_offset'][0] );
		$data = dfrapi_api_get_products_by_id( $ids, $this->config['num_products_per_api_request'], $this->meta['_dfrps_cpt_offset'][0] );

		// Update number of API requests.
		update_post_meta( $this->set['ID'], '_dfrps_cpt_last_update_num_api_requests', ( $this->meta['_dfrps_cpt_last_update_num_api_requests'][0] + 1 ) );

		// Handle errors & return.
		if ( is_array( $data ) && array_key_exists( 'dfrapi_api_error', $data ) ) {
			update_post_meta( $this->set['ID'], '_dfrps_cpt_errors', $data );
			$this->handle_error( $data );
			return 'repeat';
		}

		// Delete any errors that are currently being stored.
		update_post_meta( $this->set['ID'], '_dfrps_cpt_errors', false );

		// If there are products, store product data in options table.
		if ( isset( $data['products'] ) && !empty( $data['products'] ) ) {
			foreach ( $data['products'] as $product ) {
				$this->insert_temp_product( $product );
			}
		}

		// All products in this batch have been imported into the options table.  Now update some meta stuff.
		update_post_meta( $this->set['ID'], '_dfrps_cpt_offset', ( $this->meta['_dfrps_cpt_offset'][0] + 1 ) );
		update_post_meta( $this->set['ID'], '_dfrps_cpt_last_update_num_products_added', ( $this->meta['_dfrps_cpt_last_update_num_products_added'][0] + count( $data['products'] ) ) );

		// If the number of product IDs is less than the number of products per update
		// (that means we've queried ALL product IDs).
		// Move to next phase.
		$offset = ( $this->config['num_products_per_api_request'] * ( $this->meta['_dfrps_cpt_offset'][0] - 1 ) );
		$length = $this->config['num_products_per_api_request'];
		$current_range_of_ids = array_slice( $ids, $offset, $length );

		if ( ( count( $current_range_of_ids ) < $this->config['num_products_per_api_request'] ) ) {
			update_post_meta( $this->set['ID'], '_dfrps_cpt_offset', 1 );
			return 'ready';
		}

		return 'repeat';
	}

	// Phase 4, now we loop through our saved product data in the options table and begin importing the products into the posts table.
	function phase4() {

		$this->phase = 4;

		$this->count_iteration();

		// Get products to update
		$products = $this->select_products_for_update();

		// This means the update is already in progress. Tries to prevent race condition.
		if ( $products == 'busy' ) {
			return 'repeat';
		}

		// There are no products, move to phase 5.
		if ( ! is_array( $products ) || empty( $products ) ) {
			return 'skip';
		}

		// Defer counting.
		$this->defer_counting( TRUE );

		// Loop through products, importing and then removing from options array.
		foreach ( $products as $product_data ) {

			$product = unserialize( $product_data['data'] );

			// @since 1.2.30
			if ( ! $product ) {
                $this->delete_product_from_table( $product_data['product_id'] );
                $this->defer_counting( false );

                return 'repeat';
            }

			// Let the integration plugin handle the group of products for this set.
			do_action( "dfrps_action_do_products_{$this->get_cpt_type()}", array( 'products' => array( $product ) ), $this->set );

			$this->delete_product_from_table( $product['_id'] );

		}

		// Reactivate term counting.
		$this->defer_counting( false );

		return 'repeat';

	}

	// Phase 5, clean up and finalize.
	function phase5() {

		$this->phase = 5;

		$this->count_iteration();

		if( $this->is_first_pass() ) {
			// Set postprocess incomplete for cpt type that this set imports into.
			update_post_meta( $this->set['ID'], '_dfrps_postprocess_complete_' . $this->get_cpt_type(), FALSE );
		}

		do_action( 'dfrps_postprocess-' . $this->get_cpt_type(), $this );

		// Check if preprocess is complete (determined by importer scripts)
		$postprocess_complete = $this->postprocess_complete_check();

		if ( $postprocess_complete ) {
			$this->delete_first_passes();
			$next_update_time = dfrps_get_next_update_time( $this->set['ID'] );
			$next_update_time = apply_filters( 'dfrps_cpt_next_update_time', $next_update_time, $this->set );
			update_post_meta( $this->set['ID'], '_dfrps_cpt_next_update_time', $next_update_time );
			update_post_meta( $this->set['ID'], '_dfrps_cpt_last_update_time_completed', date_i18n( 'U' ) );
			do_action( 'dfrps_set_update_complete', $this->set );
			$this->drop_temp_product_table();
			return 'complete';
		}

		return 'repeat';

	}


} // class Dfrps_Update

} // class_exists check
