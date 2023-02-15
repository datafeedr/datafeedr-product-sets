<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Dfrps_Menu' ) ) {

class Dfrps_Menu {

	function __construct() {
		add_action( 'admin_menu', array( &$this, 'add_admin_menus' ) );
	}

	function add_admin_menus() {

		add_menu_page( 
			__( 'Datafeedr Product Sets', 'datafeedr-product-sets' ),
			__( 'Product Sets', 'datafeedr-product-sets' ),
			'edit_product_sets', 
			'dfrps',
			'edit.php?post_type='.DFRPS_CPT, 
			DFRPS_URL . 'images/datafeedr-menu-icon.png', 
			22.2 
		);

		add_submenu_page(
			'dfrps',
			__( 'Add a Product Set', 'datafeedr-product-sets' ),
			__( 'Add Product Set', 'datafeedr-product-sets' ),
			'edit_product_sets', 
			'post-new.php?post_type=' . DFRPS_CPT, 
			'' 
		);

		add_submenu_page(
			'dfrps',
			__( 'Configuration &#8212; Datafeedr Product Sets', 'datafeedr-product-sets' ),
			__( 'Configuration', 'datafeedr-product-sets' ),
			'manage_options', 
			'dfrps_options',
			array( 'Dfrps_Configuration_Tab', 'page' ) 
		);

	}
}

$dfrps_menu = new Dfrps_Menu();

} // class_exists check
