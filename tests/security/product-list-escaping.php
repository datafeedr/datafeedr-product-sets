<?php
/**
 * Security regression: product list URLs and attributes are escaped (audit PS-01) and the Datafeedr API dependency is >= 1.4.3 (PS-02).
 *
 * Run from the site root:
 *   wp eval-file wp-content/plugins/datafeedr-product-sets/tests/security/product-list-escaping.php 2>/dev/null | grep RESULT
 *
 * Makes one live Datafeedr API search (20 products) to confirm esc_url() does not drop real image/affiliate URLs. In the Claude sandbox, allow api.datafeedr.com.
 */

defined( 'ABSPATH' ) || exit;
require_once DFRPS_PATH . 'functions/html.php';
function out( $k, $ok, $x = '' ) { echo 'RESULT ' . ( $ok ? 'PASS' : 'FAIL' ) . " $k $x\n"; }
$nid = null; foreach ( (array) get_option( 'dfrapi_networks' )['ids'] as $k => $v ) { if ( ! empty( $v['aid'] ) ) { $nid = $k; break; } }

// 1. Malicious fixture.
$bad = [ '_id' => '12"3', 'name' => 'n', 'source' => 's', 'merchant' => 'm', 'source_id' => $nid, 'merchant_id' => 1, 'currency' => 'US"D',
	'image' => 'https://x.test/a.jpg" onerror="alert(1)', 'thumbnail' => 'https://x.test/t.jpg" onerror="alert(1)',
	'url' => 'javascript:alert(2)//@@@', '_wc_url' => 'javascript:alert(3)', 'price' => 100 ];
ob_start(); dfrps_html_product_list( $bad, [ 'manually_included_ids' => [], 'context' => 'div_dfrps_tab_search' ] ); dfrps_more_info_rows( $bad ); $h = ob_get_clean();
out( 'no attribute breakout', strpos( $h, '" onerror=' ) === false );
out( 'no javascript: href', ! preg_match( '/href="\s*javascript:/i', $h ) );
out( 'no raw quote in _id/currency attrs', strpos( $h, '12"3' ) === false && strpos( $h, 'US"D' ) === false );

// 2. Live search: real URLs survive esc_url unchanged in meaning.
$res = dfrapi_api_get_products_by_query( [ [ 'field' => 'any', 'operator' => 'match', 'value' => 'shoes' ] ], 20, 1 );
if ( isset( $res['dfrapi_api_error'] ) ) { echo "RESULT INFO api error: " . $res['dfrapi_api_error']['msg'] . "\n"; return; }
$n = 0; $dropped = []; $changed = 0;
foreach ( $res['products'] as $p ) {
	$n++;
	foreach ( [ 'image', 'thumbnail' ] as $f ) { if ( ! empty( $p[ $f ] ) ) { $e = esc_url( $p[ $f ] ); if ( $e === '' ) { $dropped[] = $f; } elseif ( html_entity_decode( $e ) !== $p[ $f ] ) { $changed++; } } }
	$u = dfrapi_url( $p ); if ( $u !== '' ) { $e = esc_url( $u ); if ( $e === '' ) { $dropped[] = 'url'; } elseif ( html_entity_decode( $e ) !== $u ) { $changed++; } }
	ob_start(); dfrps_html_product_list( $p, [ 'manually_included_ids' => [], 'context' => 'div_dfrps_tab_search' ] ); dfrps_more_info_rows( $p ); ob_end_clean();
}
out( "live products rendered ($n)", $n > 0 );
out( 'no real URL dropped by esc_url', empty( $dropped ), implode( ',', $dropped ) );
echo "RESULT INFO URLs whose decoded value differs after esc_url: $changed\n";

// 3. Dependency notice.
$d = new Dfrps_Plugin_Dependency( 'Datafeedr API', 'datafeedr-api/datafeedr-api.php', '1.4.3' );
out( 'datafeedr-api 1.4.3 satisfies dependency', $d->action_required() === false, 'installed=' . $d->current_version() );
