<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Load generic helper functions.
 */
require_once( DFRPS_PATH . 'functions/helper.php' );

/**
 * Load upgrade functions.
 */
require_once( DFRPS_PATH . 'functions/upgrade.php' );

/**
 * Load cron functions. This loads on all page loads.
 */
require_once( DFRPS_PATH . 'functions/cron.php' );

/**
 * Load integration functions. This loads on all page loads.
 */
require_once( DFRPS_PATH . 'functions/integration.php' );
