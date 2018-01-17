<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if ( ! class_exists( 'Dfrps_Initialize' ) ) {


class Dfrps_Initialize {

	public function __construct() {

		// Load required function files.
		require_once( DFRPS_PATH . 'functions/admin-functions.php' );
	
		// Load required classes.
		require_once( DFRPS_PATH . 'classes/class-dfrps-cpt.php' );				// Custom post type for "Product Sets".
		require_once( DFRPS_PATH . 'classes/class-dfrps-configuration.php' );	// Configuration page.
		require_once( DFRPS_PATH . 'classes/class-dfrps-tools.php' );			// Tools page.
		require_once( DFRPS_PATH . 'classes/class-dfrps-help.php' );			// Help tabs.
		require_once( DFRPS_PATH . 'classes/class-dfrps-image-importer.php' );	// Image Importer class.
		
		// Hooks
		add_action( 'admin_enqueue_scripts', 	array( $this, 'load_css' ) );
		add_action( 'admin_enqueue_scripts', 	array( $this, 'load_js' ) );
		add_action( 'plugins_loaded', 			array( $this, 'initialize_classes' ) );
		add_action( 'admin_menu', 				array( $this, 'admin_menu' ) );
		add_filter( 'plugin_row_meta', 			array( $this, 'plugin_row_meta' ), 10, 2 );
		add_filter( 'plugin_action_links_' . DFRPS_BASENAME, array( $this, 'action_links' ) );

		do_action( 'dfrps_loaded' );
	}

	function admin_menu() {
			
		if ( !Dfrapi_Env::api_keys_exist() || !dfrps_default_cpt_is_selected() ) {
			
			// Load "Configuration" page by default.			
			add_menu_page( 
				__( 'Datafeedr Product Sets', DFRPS_DOMAIN ), 
				__( 'Product Sets', DFRPS_DOMAIN ), 
				'manage_options', 
				'dfrps_configuration',
				'', 
				null, 
				44 
			);
			
		} else {
		
			// Load list of Product Sets by default.
			add_menu_page( 
				__( 'Datafeedr Product Sets', DFRPS_DOMAIN ), 
				__( 'Product Sets', DFRPS_DOMAIN ), 
				'edit_product_sets', 
				'dfrps',
				'edit.php?post_type='.DFRPS_CPT, 
				null, 
				44 
			);
		}
	}
	
	function initialize_classes() {
		// If a CPT is selected, show List/Add/Edit PS links.
		if ( dfrps_default_cpt_is_selected() ) {
			new Dfrps_Cpt();
		}
		new Dfrps_Configuration();
		new Dfrps_Tools();
	}
	
	function load_css() {
		wp_register_style( DFRPS_PREFIX . '_general_css', DFRPS_URL . 'css/general.css', false, DFRPS_VERSION );
		wp_enqueue_style( DFRPS_PREFIX . '_general_css' );
		wp_register_style( DFRPS_PREFIX . '_cpt_css', DFRPS_URL . 'css/cpt.css', false, DFRPS_VERSION );
		wp_enqueue_style( DFRPS_PREFIX . '_cpt_css' );
	}
	
    function load_js() {    
    	if  ( DFRPS_CPT != get_post_type() ) { return; }
    	wp_register_script( DFRPS_PREFIX . '_cpt_js', DFRPS_URL.'js/cpt.js', array( 'jquery', 'jquery-color' ), DFRPS_VERSION, false );
        wp_enqueue_script( DFRPS_PREFIX . '_cpt_js' );
    }
    
	function plugin_row_meta( $links, $plugin_file ) {
		if ( $plugin_file == DFRPS_BASENAME ) {
			/* $links[] = sprintf( '<a href="' . admin_url( 'plugin-install.php?tab=search&type=tag&s=dfrapi' ) . '">%s</a>', __( 'Datafeedr API Plugin', DFRPS_DOMAIN ) ); */
			/* $links[] = sprintf( '<a href="' . admin_url( 'plugin-install.php?tab=search&type=tag&s=dfrps' ) . '">%s</a>', __( 'Importer Plugins', DFRPS_DOMAIN ) ); */
			$links[] = sprintf( '<a href="' . DFRAPI_HELP_URL . '">%s</a>', __( 'Support', DFRPS_DOMAIN ) );
			return $links;
		}
		return $links;
	}

	function action_links( $links ) {
		return array_merge(
			$links,
			array(
				'config' => '<a href="' . admin_url( 'admin.php?page=dfrps_configuration' ) . '">' . __( 'Configuration', DFRPS_DOMAIN ) . '</a>',
			)
		);
	}
	
} // class Dfrps_Initialize

$dfrps_initialize = new Dfrps_Initialize();

} // class_exists check
