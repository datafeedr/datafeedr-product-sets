<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Dfrps_Menu' ) ) {

class Dfrps_Menu {
	
	function __construct() {
		add_action( 'admin_menu', array( &$this, 'add_admin_menus' ) );
	}
		
	function add_admin_menus() {
	
		add_menu_page( 
			__( 'Datafeedr Product Sets', DFRPS_DOMAIN ), 
			__( 'Product Sets', DFRPS_DOMAIN ), 
			'edit_product_sets', 
			'dfrps',
			'edit.php?post_type='.DFRPS_CPT, 
			DFRPS_URL . 'images/datafeedr-menu-icon.png', 
			22.2 
		);
		
		add_submenu_page(
			'dfrps',
			__( 'Add a Product Set', DFRPS_DOMAIN ), 
			__( 'Add Product Set', DFRPS_DOMAIN ), 
			'edit_product_sets', 
			'post-new.php?post_type=' . DFRPS_CPT, 
			'' 
		);
		
		add_submenu_page(
			'dfrps',
			__( 'Configuration &#8212; Datafeedr Product Sets', DFRPS_DOMAIN ), 
			__( 'Configuration', DFRPS_DOMAIN ), 
			'manage_options', 
			'dfrps_options',
			array( 'Dfrps_Configuration_Tab', 'page' ) 
		);
		
	}
}

$dfrps_menu = new Dfrps_Menu();

} // class_exists check
