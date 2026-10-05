<?php
/**
 * Security regression: AJAX type/term_ids validation (audit PS-04). Each case runs in its own process because the handlers end in die().
 *
 * Run from the site root:
 *   W="wp eval-file wp-content/plugins/datafeedr-product-sets/tests/security/ajax-input-validation.php"\n *   $W dfrps_ajax_update_import_into evil 5            # expect type unchanged, terms=[5] (no fatal)\n *   $W dfrps_ajax_update_import_into product '["7","abc",0,"9"]'   # expect terms=[7,9]\n *   $W dfrps_ajax_update_taxonomy x 12                 # expect terms=[12]\n *   (zsh: write each command out in full; a $W variable is not word-split)
 *
 * Writes the first Product Set's _dfrps_cpt_type and _dfrps_cpt_terms meta, then restores both on shutdown (see "restored=yes").
 */

defined( 'ABSPATH' ) || exit;
// args: action, type, term_ids_json_or_string
require_once DFRPS_PATH . 'functions/ajax.php';
$sid = get_posts( [ 'post_type' => DFRPS_CPT, 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids' ] )[0];
$bk  = [ get_post_meta( $sid, '_dfrps_cpt_type', true ), get_post_meta( $sid, '_dfrps_cpt_terms', true ) ];
register_shutdown_function( function () use ( $sid, $bk ) {
	$type = get_post_meta( $sid, '_dfrps_cpt_type', true ); $terms = get_post_meta( $sid, '_dfrps_cpt_terms', true );
	echo "RESULT type=" . $type . " terms=" . json_encode( $terms ) . "\n";
	update_post_meta( $sid, '_dfrps_cpt_type', $bk[0] ); update_post_meta( $sid, '_dfrps_cpt_terms', $bk[1] );
	echo "RESULT restored=" . ( get_post_meta( $sid, '_dfrps_cpt_type', true ) === $bk[0] && get_post_meta( $sid, '_dfrps_cpt_terms', true ) == $bk[1] ? 'yes' : 'NO' ) . "\n";
} );
wp_set_current_user( get_users( [ 'role' => 'administrator', 'number' => 1 ] )[0]->ID );
$tid = json_decode( $args[2], true ); if ( $tid === null ) { $tid = $args[2]; }
$_REQUEST = $_POST = [ 'dfrps_security' => wp_create_nonce( 'dfrps_ajax_nonce' ), 'postid' => $sid, 'type' => $args[1], 'term_ids' => $tid, 'cids' => $tid ];
echo "RESULT before type=" . $bk[0] . "\n";
call_user_func( $args[0] );
