<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Functions for integration DFRPS with importing plugins.
 */

function dfrps_register_cpt( $post_type, $args ) {

	$cpts = get_option( 'dfrps_registered_cpts', array() );

	// If $cpts is empty, set this CPT as the default CPT
	if ( !$cpts || empty( $cpts ) ) {
		$configuration = get_option( 'dfrps_configuration', array() );
		$configuration['default_cpt'] = $post_type;
		update_option( 'dfrps_configuration', $configuration );
	}

	$args['post_type'] = $post_type;
	$cpts[$post_type] = $args;
	update_option( 'dfrps_registered_cpts', $cpts );
}


function dfrps_unregister_cpt( $post_type = false ) {

	if ( ! $post_type ) {
		return;
	}

	/**
	 * Reset updates to prevent an importer from appearing not to finish
	 * (ie. displaying progress bar) on the Products Sets page.
	 */
	dfrps_reset_update();

	$cpts = get_option( 'dfrps_registered_cpts', array() );
	unset( $cpts[ $post_type ] );

	// Update default_cpt if there are other CPTs available.
	// Otherwise the user will see the "install importer plugin" nag.
	if ( ! empty( $cpts ) ) {
		$configuration = get_option( 'dfrps_configuration', array() );
		$default_cpt   = $configuration['default_cpt'];

		// The CPT being unregistered is the default CPT.  So update the default CPT with another arbitrary CPT.
		if ( $default_cpt == $post_type ) {
			foreach ( $cpts as $type => $values ) {
				$configuration['default_cpt'] = $type;
				update_option( 'dfrps_configuration', $configuration );
				break;
			}
		}
	}
	update_option( 'dfrps_registered_cpts', $cpts );
}

/**
 * Adds new terms and
 * Gets existing term IDs.
 *
 * ### EXAMPLE USAGE ###
 *
 *
 	$taxonomy = 'product_cat';
	$paths = array (
		'Fruit/Apple',
		'Pets/Large Pets/Cats',
		'Farm Animals/Pigs',
		'Farm Animals/Cows',
		'Vegetable/Asparagus',
		'Vegetable/Broccoli',
		'Vegetable/Lettuce',
		'Food/Vegetable',
		'Food/Vegetable/Asparagus',
		'Food/Meat/Pigs',
		'Food/Meat/Cows',
	);
	$ids = dfrps_add_term( $taxonomy, $paths );
 *
 *
 */
function dfrps_add_term( $taxonomy, $paths ) {

	$ids = array();
	$all_ids = array();

	foreach( $paths as $path ) {

		$names = explode('/', $path);
		$num_names = count( $names );

		for( $depth=0; $depth<$num_names; $depth++ ) {

			$parent = ( $depth > 0 ) ? $ids[( $depth - 1 )] : '';
			$term = term_exists( $names[$depth], $taxonomy, $parent );

			// Insert term.
			if ( $term === 0 || $term === null || $term === false ) {

				$args = ( $depth > 0 ) ? array( 'parent' => $ids[( $depth - 1 )]) : array();
				$term = wp_insert_term( $names[$depth], $taxonomy, $args );
			}

			if ( is_array( $term ) ) {
				$ids[$depth] = intval( $term['term_id'] );
			}

		}

		$all_ids = array_unique( array_merge( $all_ids, $ids ) );
	}

	return $all_ids;
}

/**
 * This adds the term IDs to the post (ie. product, coupon, etc) except for
 * the post that we are currently updating. If the post is not getting deleted,
 * then its term IDs will be added by the integration plugin.
 */
function dfrps_add_term_ids_to_post( $post_id, $set, $cpt, $taxonomy ) {

	// Get all Product Set IDs which added this product. This returns and array of ids.
	$set_ids = get_post_meta( $post_id, '_dfrps_product_set_id', FALSE );

	// Loop through all set_ids and remove this set's ID from the $set_ids array.
	$this_set_id = intval( $set['ID'] );
	if ( isset( $set_ids ) && !empty( $set_ids ) ) {
		$set_ids = array_map ( 'intval', $set_ids );
		foreach ( $set_ids as $set_id ) {
			if ( ( $key = array_search( $this_set_id, $set_ids ) ) !== false ) {
				unset( $set_ids[$key] );
			}
		}
	}

	// Loop through remaining sets (all set IDs expect this set id) and 
	// get the term ids for the other sets.
	$terms = array();
	if ( isset( $set_ids ) && !empty( $set_ids ) ) {
		foreach ( $set_ids as $id ) {
			$terms = array_merge( $terms, dfrps_get_cpt_terms( $id ) );
		}
	}
	$terms = array_map( 'intval', $terms ); // Make sure these $terms are integers
	$terms = array_unique( $terms );

	// Add term ids to this product.
	wp_defer_term_counting( true );
	wp_set_object_terms( $post_id, $terms, $taxonomy, false );
	wp_defer_term_counting( false );
}

/**
 * Returns array of post IDs that were added by this set ID.
 */
function dfrps_get_all_post_ids_by_set_id( $set_id ) {

	global $wpdb;

	$set_id = intval( $set_id );

	if ( $set_id < 1 ) {
		return array();
	}

	$posts = $wpdb->get_results( "
		SELECT post_id AS ID
		FROM $wpdb->postmeta
		WHERE meta_key = '_dfrps_product_set_id'
		AND meta_value = " . $set_id . "
	", ARRAY_A );

	if ( $posts == NULL ) {
		return array();
	}

	$ids = array();
	foreach ( $posts as $post ) {
		$ids[] = $post['ID'];
	}

	return array_unique( $ids );
}

/**
 * Returns post ARRAY if post already exists.
 * Returns FALSE if post does not exist.
 * Returns "skip" if $product['_id'] was already imported but dfrps_get_post_by_product_id() returns false. This handles race condition.
 *
 * @param  array  $product  Datafeedr Product array.
 * @param  array  $set  Product Set array.
 *
 * @return array|false|string
 */
function dfrps_get_existing_post( $product, $set ) {

	static $imported_product_ids = [];

	static $post_type = null;

	if ( $post_type === null ) {
		$post_type = get_post_meta( $set['ID'], '_dfrps_cpt_type', true );
	}

	$post = dfrps_get_post_by_product_id( $product['_id'], $post_type, true );

	// Return "skip" if the product is already in $imported_product_ids AND $post is null/false (which means it was imported but not yet queryable).
	if ( in_array( $product['_id'], $imported_product_ids ) && ! $post ) {
		return 'skip';
	}

	$imported_product_ids[] = $product['_id'];

	return $post;
}

function dfrps_int_to_price( $price ) {
	$price = intval( $price );
	return ( $price/100 );
}

/**
 * @param  int  $product_id  Datafeedr Product ID ($product['_id'])
 * @param  string  $post_type  The post_type this product is associated with.
 * @param  bool  $with_table_lock  Whether to LOCK the table before performing query. Default false.
 *
 * @return array|false Returns post array if post found else returns false.
 */
function dfrps_get_post_by_product_id( $product_id, $post_type, $with_table_lock = false ) {

	global $wpdb;

	if ( $with_table_lock ) {
		$wpdb->query( "LOCK TABLES $wpdb->postmeta WRITE, $wpdb->posts WRITE" );
	}

	$post = $wpdb->get_row( $wpdb->prepare( "
		SELECT * 
		FROM $wpdb->posts
		JOIN $wpdb->postmeta
			ON post_id = ID
		WHERE meta_key = '_dfrps_product_id' 
		AND meta_value = %s
		AND post_type = %s
	", $product_id, $post_type ), ARRAY_A );

	if ( $with_table_lock ) {
		$wpdb->query( "UNLOCK TABLES" );
	}

	return ( $post != null ) ? $post : false;
}



