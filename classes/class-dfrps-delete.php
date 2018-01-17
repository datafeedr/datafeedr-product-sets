<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if ( ! class_exists( 'Dfrps_Delete' ) ) {

/**
 * Product Set Deleter
 */
class Dfrps_Delete {

	public function __construct( $post ) {
		$this->action = 'delete';
		$this->set = $post;
		$this->config = $this->get_configuration();
		$this->meta = $this->get_postmeta();
		$this->phase = $this->get_phase();
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
	function get_phase() {
		if ( isset( $this->meta['_dfrps_cpt_update_phase'][0] ) ) {
			return intval( $this->meta['_dfrps_cpt_update_phase'][0] );
		}
		return 0;
	}
	
	// Get the CPT that this Product Set will import into.
	function get_cpt_type() {
		return get_post_meta( $this->set['ID'], '_dfrps_cpt_type', TRUE ); // Ticket: 9167
	} 

	// Run update.
	function update() {
		if ( $this->phase == 0 || $this->phase == 1 ) {
			$this->phase1();
		} elseif ( $this->phase == 2 ) {
			$this->phase2();
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
		for( $i=1; $i<=2; $i++ ) {
			delete_post_meta( $this->set['ID'], '_dfrps_cpt_update_phase' . $i . '_first_pass' );
		}
	}
	
	// Phase 1, initialize update, set variables and update phase.
	function phase1() {
	
		$this->phase = 1;

		do_action( 'dfrps_begin_phase', $this );
		do_action( 'dfrps_begin_phase_1', $this );
						
		if( $this->is_first_pass() ) {

			// Set preprocess incomplete for each CPT that this set imports into.
			update_post_meta( $this->set['ID'], '_dfrps_preprocess_complete_' . $this->get_cpt_type(), FALSE );
			
			delete_post_meta( $this->set['ID'], '_dfrps_cpt_errors' );
			update_post_meta( $this->set['ID'], '_dfrps_cpt_update_phase', 1 );
				
			unset( $this->meta['_dfrps_cpt_previous_update_info'] ); // Unset so array item is not duplicated
			update_post_meta( $this->set['ID'], '_dfrps_cpt_previous_update_info', $this->meta );
			//update_post_meta( $this->set['ID'], '_dfrps_cpt_update_iteration', 1 );
			update_post_meta( $this->set['ID'], '_dfrps_cpt_offset', 1 );
			update_post_meta( $this->set['ID'], '_dfrps_cpt_last_update_time_started', date_i18n( 'U' ) );
			update_post_meta( $this->set['ID'], '_dfrps_cpt_last_update_time_completed', 0 );
			update_post_meta( $this->set['ID'], '_dfrps_cpt_last_update_num_products_added', 0 );
			update_post_meta( $this->set['ID'], '_dfrps_cpt_last_update_num_api_requests', 0 );
			update_post_meta( $this->set['ID'], '_dfrps_cpt_last_update_num_products_deleted', 0 );
		}
		
		do_action( 'dfrps_preprocess-' . $this->get_cpt_type(), $this );
		
		// Check if preprocess is complete (detemined by importer scripts)
		$preprocess_complete = $this->preprocess_complete_check();
				
		// Move to phase 2 ONLY if all posts have been unset from their categories.
		if ( $preprocess_complete ) {
			$this->phase = 2;
			do_action( 'dfrps_end_phase', $this );
			update_post_meta( $this->set['ID'], '_dfrps_cpt_update_phase', 2 );
		}
		
		do_action( 'dfrps_end_phase', $this );
		do_action( 'dfrps_end_phase_1', $this );
		return;	
	}
	
	// Phase 2, clean up and finalize.
	function phase2() {
	
		$this->phase = 2;
		
		do_action( 'dfrps_begin_phase', $this );
				
		if( $this->is_first_pass() ) {	
			// Set postprocess incomplete for each CPT that this set imports into.
			update_post_meta( $this->set['ID'], '_dfrps_postprocess_complete_' . $this->get_cpt_type(), FALSE );
		}
				
		do_action( 'dfrps_postprocess-' . $this->get_cpt_type(), $this );
		
		// Check if preprocess is complete (detemined by importer scripts)
		$postprocess_complete = $this->postprocess_complete_check();
		
		if ( $postprocess_complete ) {
			$this->delete_first_passes();
			update_post_meta( $this->set['ID'], '_dfrps_cpt_next_update_time', 3314430671 );
			update_post_meta( $this->set['ID'], '_dfrps_cpt_last_update_time_completed', date_i18n( 'U' ) );
			$this->phase = 0;
			do_action( 'dfrps_end_phase', $this );
			update_post_meta( $this->set['ID'], '_dfrps_cpt_update_phase', 0 );
		}
		
		do_action( 'dfrps_end_phase', $this );
		do_action( 'dfrps_end_phase_2', $this );
		return;
	}

		
} // class Dfrps_Delete

} // class_exists check