<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if ( ! class_exists( 'Dfrps_Tabs' ) ) {

/*
 * @http://theme.fm/2011/10/how-to-create-tabs-with-the-settings-api-in-wordpress-2590/
 */
class Dfrps_Tabs {
	
	public static $key = 'dfrps_options';
	public static $tabs;
	public static $current_tab;
	public static $default_tab;
	public static $tabs_without_forms = array( 'export' );
	
	function __construct() {
		self::set_tabs();
		self::set_default_tab();
		self::set_current_tab();
		self::includes();
	}
	
	function set_tabs() {
		self::$tabs = array ( 
			'configuration' => __( 'Configuration', DFRPS_DOMAIN ),
			'networks' => __( 'Networks', DFRPS_DOMAIN ),
			'merchants' => __( 'Merchants', DFRPS_DOMAIN ),
			'tools' => __( 'Tools', DFRPS_DOMAIN ),
			'export' => __( 'Export', DFRPS_DOMAIN ),
			'import' => __( 'Import', DFRPS_DOMAIN ),
			'account' => __( 'Account', DFRPS_DOMAIN ),
		);		
	}
	
	function set_default_tab() {
		self::$default_tab = 'configuration';
	}	
	
	function set_current_tab() {
		self::$current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : self::$default_tab;
	}
	
	function includes() {
		foreach( array_keys( self::$tabs ) as $key ) {
			$filename = DFRPS_PATH . 'classes/class-'.DFRPS_PREFIX.'-'.$key.'-tab.php';
			if ( file_exists( $filename ) ) {
				require_once( $filename );
			}
		}
	}
	
	function plugin_options_page() {
		echo '<div class="wrap" id="' . self::$key . '_' . self::$current_tab . '">';
		self::plugin_options_tabs();
		
		if ( !in_array( self::$current_tab, self::$tabs_without_forms ) ) {
			echo '<form method="post" action="options.php">';
			wp_nonce_field( 'update-options' );
			settings_fields( self::$current_tab );
		}
		
		do_settings_sections( self::$current_tab );
		
		if ( !in_array( self::$current_tab, self::$tabs_without_forms ) ) {
			submit_button();
			echo '</form>';
		}
		
		echo '</div>';
	}
	
	function plugin_options_tabs() {
		echo '<h2 class="nav-tab-wrapper">';
		foreach ( self::$tabs as $tab_key => $tab_caption ) {
			$active = self::$current_tab == $tab_key ? 'nav-tab-active' : '';
			echo '<a class="nav-tab ' . $active . '" href="?page=' . self::$key . '&tab=' . $tab_key . '">' . $tab_caption . '</a>';	
		}
		echo '</h2>';
	}
}

$dfrps_tabs = new Dfrps_Tabs();

} // class_exists check
