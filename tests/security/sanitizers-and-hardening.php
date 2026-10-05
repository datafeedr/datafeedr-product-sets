<?php
/**
 * Security regression: query/schedule sanitizers, malformed-schedule loop guard, schedule nonce, Configuration validation, email escaping, notice gating, prepared SQL (audit PS-03 to PS-15).
 *
 * Run from the site root:
 *   wp eval-file wp-content/plugins/datafeedr-product-sets/tests/security/sanitizers-and-hardening.php 2>/dev/null | grep RESULT
 *
 * Expect every line to be PASS. The save_post checks write the first Product Set's schedule meta and restore it from a backup (see "schedule meta restored"). No emails are sent (pre_wp_mail short-circuits).
 */

defined( 'ABSPATH' ) || exit;
foreach ( [ 'class-dfrps-cpt', 'class-dfrps-configuration', 'class-dfrps-update' ] as $c ) { require_once DFRPS_PATH . "classes/$c.php"; }
function out( $k, $ok, $x = '' ) { echo 'RESULT ' . ( $ok ? 'PASS' : 'FAIL' ) . " $k $x\n"; }
$admin = get_users( [ 'role' => 'administrator', 'number' => 1 ] )[0]->ID;
$sub   = get_users( [ 'role' => 'subscriber', 'number' => 1 ] )[0]->ID;
$X = '<script>alert(1)</script>';
$sets = get_posts( [ 'post_type' => DFRPS_CPT, 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids' ] );

// 1. Real saved queries round-trip through dfrps_sanitize_query unchanged.
$q_total = 0; $q_changed = [];
foreach ( $sets as $id ) {
	foreach ( [ '_dfrps_cpt_query', '_dfrps_cpt_temp_query' ] as $k ) {
		$q = get_post_meta( $id, $k, true );
		if ( ! is_array( $q ) || ! $q ) { continue; }
		$q_total++;
		if ( dfrps_sanitize_query( $q ) != $q ) { $q_changed[] = "$id:$k"; }
	}
}
$cfg = get_option( 'dfrps_configuration', [] );
$dq  = $cfg['default_filters']['dfrps_query'] ?? [];
if ( $dq ) { $q_total++; if ( dfrps_sanitize_query( $dq ) != $dq ) { $q_changed[] = 'config'; } }
out( "real queries unchanged ($q_total checked)", empty( $q_changed ), implode( ',', array_slice( $q_changed, 0, 5 ) ) );
if ( $q_changed ) { list( $i, $k ) = explode( ':', $q_changed[0] ) + [ 1 => '' ]; $q = $k ? get_post_meta( $i, $k, true ) : $dq; echo 'RESULT INFO first diff: ' . json_encode( [ 'before' => $q, 'after' => dfrps_sanitize_query( $q ) ] ) . "\n"; }

// 2. Malicious query.
$bad = dfrps_sanitize_query( [ 3 => [ 'field' => 'name"x', 'operator' => 'match', 'value' => "red $X \"shoes\"|boots ^a \$", 'evil' => 'x' ], 'x' => 'notarow', 5 => [ 'operator' => 'is' ], 6 => [ 'field' => 'merchant_id', 'value' => [ 'arr' ] ] ] );
out( 'query sanitizer', isset( $bad[3] ) && $bad[3]['field'] === 'namex' && strpos( $bad[3]['value'], '<' ) === false && strpos( $bad[3]['value'], '"shoes"|boots ^a $' ) !== false && ! isset( $bad[3]['evil'] ) && ! isset( $bad[5] ) && ! isset( $bad[6]['value'] ), json_encode( $bad ) );

// 3. Schedules: real ones unchanged, bad ones fixed, loop bounded.
$s_total = 0; $s_changed = [];
foreach ( $sets as $id ) {
	$s = get_post_meta( $id, '_dfrps_update_schedule', true );
	if ( ! is_array( $s ) || ! $s ) { continue; }
	$s_total++;
	$h = explode( ':', $s['time'] );
	$c = dfrps_sanitize_schedule( [ 'interval' => $s['interval'], 'days' => $s['days'], 'hour' => $h[0] ?? 0, 'minute' => $h[1] ?? 0 ] );
	if ( $c != $s ) { $s_changed[] = $id . ':' . json_encode( [ $s, $c ] ); }
}
out( "real schedules unchanged ($s_total checked)", empty( $s_changed ), implode( ' ', array_slice( $s_changed, 0, 2 ) ) );
$c = dfrps_sanitize_schedule( [ 'interval' => 'evil', 'days' => [ '9', '-1', 'x', '3', '3' ], 'hour' => '99', 'minute' => '7' ] );
out( 'schedule sanitizer', $c === [ 'enabled' => 'on', 'interval' => 'day_of_week', 'days' => [ '3' ], 'time' => '23:07' ], json_encode( $c ) );
$t = microtime( true );
dfrps_get_custom_update_time( [ 'interval' => 'day_of_week', 'days' => [ '9' ], 'time' => '05:00' ] );
dfrps_get_custom_update_time( [ 'interval' => 'day_of_month', 'days' => [ '31' ], 'time' => '05:00' ] );
out( 'malformed schedule no longer hangs', ( microtime( true ) - $t ) < 2, round( microtime( true ) - $t, 3 ) . 's' );
$good = dfrps_get_custom_update_time( [ 'interval' => 'day_of_week', 'days' => [ '1' ], 'time' => '05:00' ] );
out( 'valid schedule still computes', $good > time() && $good < time() + 8 * DAY_IN_SECONDS, date( 'D Y-m-d H:i', $good ) );

// 4. save_post: no nonce = schedule untouched; nonce + admin = sanitized save. Backup/restore.
$sid = $sets[0];
$bk  = [ get_post_meta( $sid, '_dfrps_update_schedule', true ), get_post_meta( $sid, '_dfrps_cpt_next_update_time', true ) ];
$cpt = ( new ReflectionClass( 'Dfrps_Cpt' ) )->newInstanceWithoutConstructor();
try {
	update_post_meta( $sid, '_dfrps_update_schedule', [ 'enabled' => 'on', 'interval' => 'day_of_week', 'days' => [ '2' ], 'time' => '01:00' ] );
	wp_set_current_user( $admin );
	$_POST = [];   // like quick edit / programmatic save
	$cpt->save_post( $sid );
	out( 'save without nonce keeps schedule (quick-edit bug fixed)', get_post_meta( $sid, '_dfrps_update_schedule', true )['days'] === [ '2' ] );
	$_POST = [ 'dfrps_schedule_nonce' => wp_create_nonce( 'dfrps_save_schedule_' . $sid ), 'enabled' => 'on', 'interval' => 'day_of_month', 'month' => [ '31', '15' ], 'week' => [ '9' ], 'hour' => '07', 'minute' => '5' ];
	$cpt->save_post( $sid );
	$saved = get_post_meta( $sid, '_dfrps_update_schedule', true );
	out( 'save with nonce stores sanitized schedule', $saved === [ 'enabled' => 'on', 'interval' => 'day_of_month', 'days' => [ '15' ], 'time' => '07:05' ], json_encode( $saved ) );
	wp_set_current_user( $sub );
	$_POST['month'] = [ '20' ];
	$cpt->save_post( $sid );
	out( 'subscriber with nonce cannot change schedule', get_post_meta( $sid, '_dfrps_update_schedule', true )['days'] === [ '15' ] );
} finally {
	$bk[0] ? update_post_meta( $sid, '_dfrps_update_schedule', $bk[0] ) : delete_post_meta( $sid, '_dfrps_update_schedule' );
	update_post_meta( $sid, '_dfrps_cpt_next_update_time', $bk[1] );
	$_POST = [];
	out( 'schedule meta restored', get_post_meta( $sid, '_dfrps_update_schedule', true ) == $bk[0] && get_post_meta( $sid, '_dfrps_cpt_next_update_time', true ) == $bk[1] );
}

// 5. Configuration validate.
wp_set_current_user( $admin );
$conf = new Dfrps_Configuration(); $conf->load_settings();
$_POST = [];
$internal = [ 'updates_enabled' => 'disabled', 'default_filters' => [ 'dfrps_query' => [ [ 'field' => 'any', 'value' => 'x' ] ] ] ];
out( 'internal update_option still bypasses', $conf->validate( $internal ) === $internal );
$_POST = [ 'option_page' => 'dfrps_configuration' ];  // form post WITHOUT the action field
$v = $conf->validate( [ 'updates_enabled' => 'yes', 'default_cpt' => 'evil', 'cron_interval' => '5', 'dfrps_query' => [ [ 'field' => 'any', 'value' => $X ] ] ] );
$_POST = [];
out( 'form post validated even without action field', $v['updates_enabled'] === 'enabled' && $v['cron_interval'] === 300 && $v['default_cpt'] === ( $cfg['default_cpt'] ?? '' ) && strpos( $v['default_filters']['dfrps_query'][0]['value'], '<' ) === false, json_encode( $v ) );

// 6. Email escaping.
$upd = ( new ReflectionClass( 'Dfrps_Update' ) )->newInstanceWithoutConstructor();
$upd->set = [ 'ID' => $sid, 'post_title' => "Title $X" ];
$mail = null;
add_filter( 'pre_wp_mail', function ( $r, $atts ) use ( &$mail ) { $mail = $atts['message']; return true; }, 10, 2 );
$upd->updates_disabled_email_user( [ 'dfrapi_api_error' => [ 'class' => "C$X", 'code' => "1$X", 'msg' => "M$X", 'params' => [] ] ] );
out( 'email escaped', $mail && strpos( $mail, '<script>' ) === false && strpos( $mail, '&lt;script&gt;' ) !== false );

// 7. Notices gated.
add_filter( 'pre_option_dfrps_configuration', function () { return [ 'updates_enabled' => 'disabled' ]; } );
foreach ( [ 'subscriber' => $sub, 'admin' => $admin ] as $label => $uid ) {
	wp_set_current_user( $uid );
	ob_start(); dfrps_updates_disabled(); $n = ob_get_clean();
	out( "notice gating $label", $label === 'admin' ? $n !== '' : $n === '' );
}
remove_all_filters( 'pre_option_dfrps_configuration' );

// 8. prepare() SQL returns the same rows as before.
global $wpdb;
foreach ( array_slice( $sets, 0, 3 ) as $id ) {
	$old = $wpdb->get_col( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_dfrps_product_set_id' AND meta_value = " . (int) $id );
	out( "set $id post IDs identical", array_values( array_unique( $old ) ) == array_values( dfrps_get_all_post_ids_by_set_id( $id ) ), count( $old ) . ' rows' );
}
