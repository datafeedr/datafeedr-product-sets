<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Dfrps_Admin_Help' ) ) :

/**
 * Dfrps_Admin_Help Class
 */
class Dfrps_Admin_Help {

	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		add_action( "current_screen", array( $this, 'add_tabs' ), 50 );
	}

	/**
	 * Add help tabs
	 */
	public function add_tabs() {
		
		$screen = get_current_screen();
				
		/*
		[id] => datafeedr-productset
		[id] => edit-datafeedr-productset
		[id] => product-sets_page_dfrps_configuration
		[id] => product-sets_page_dfrpswc_options
		*/
		$possible_screens = array(
			DFRPS_CPT,
			'edit-' . DFRPS_CPT,
			'product-sets_page_dfrps_configuration',
			'product-sets_page_dfrps_tools',
			'product-sets_page_dfrpswc_options',
		);

		if ( ! in_array( $screen->id, $possible_screens ) ) { return; }
		
		// This is an Add/Edit page.
		if ( $screen->id == DFRPS_CPT ) {
		
			$screen->add_help_tab( array(
				'id'	=> 'dfrps_docs_overview',
				'title'	=> __( 'Product Set Overview', DFRPS_DOMAIN ),
				'content'	=> 
					'<h2>' . __( "What is a Product Set?", DFRPS_DOMAIN ) . '</h2>' . 
					'<p>' . __( "A Product Set contains a collection of related products and is responsible for importing those products into your blog and keeping them up-to-date.", DFRPS_DOMAIN ) . '</p>' .
					'<ul>' . 
						'<li><strong>' . __( "Adding products", DFRPS_DOMAIN ) . '</strong> - ' . __( "Build your Product Set in one of two ways: By saving a search you can add all the products in your search results to your Product Set at once. By adding a single product individually you can handpick items to add to your set. You can use one or both methods of adding products when building a Product Set.", DFRPS_DOMAIN ) . '</li>' .
						'<li><strong>' . __( "Importing products", DFRPS_DOMAIN ) . '</strong> - ' . __( "When you publish your Product Set, it enters the update queue. The products will be imported into your blog when the Product Set reaches its turn in the queue.", DFRPS_DOMAIN ) . '</li>' .
						'<li><strong>' . __( "Updating products", DFRPS_DOMAIN ) . '</strong> - ' . __( "The Product Set is also responsible for keeping its imported products up-to-date. The update interval is configured on the Product Sets > Configuration page.", DFRPS_DOMAIN ) . '</li>' .
					'</ul>' . 
					'<p>' . __( "", DFRPS_DOMAIN ) . '</p>' 
			) );
		
			$screen->add_help_tab( array(
				'id'		=> 'dfrps_docs_dashboard',
				'title'		=> __( 'Dashboard', DFRPS_DOMAIN ),
				'content'	=> 
					'<h2>' . __( "Dashboard", DFRPS_DOMAIN ) . '</h2>' . 
					'<p>' . __( 'The Dashboard guides you through the process of creating a new Product Set. It gives you an overview of the Product Set\'s status, informs you about the update status after you publish, and provides quick links to perform additional actions.', DFRPS_DOMAIN ) . '</p>' . 
					'<p>' . __( "By default, the Dashboard can be found at the top of the right column.", DFRPS_DOMAIN ) . '</p>' 
				) );
		
			$screen->add_help_tab( array(
				'id'		=> 'dfrps_docs_title',
				'title'		=> __( 'Title', DFRPS_DOMAIN ),
				'content'	=> 
					'<h2>' . __( "Product Set Title", DFRPS_DOMAIN ) . '</h2>' . 
					'<p>' . __( 'The Product Set title is for your reference only. Adding a title is optional, but allows you to identify the Product Set in the future. A short, descriptive title is recommended.', DFRPS_DOMAIN ) . '</p>' 
			) );
		
			$screen->add_help_tab( array(
				'id'		=> 'dfrps_docs_search',
				'title'		=> __( 'Search tab', DFRPS_DOMAIN ),
				'content'	=> 
					'<h2>' . __( "Search Tab", DFRPS_DOMAIN ) . '</h2>' . 
					'<p>' . __( 'Start your search on the Search tab. Click <strong>+ add filter</strong> to add additional search fields or <img src="' . DFRAPI_URL . 'images/icons/removefilter.png" class="dfrps_valign_middle" /> to remove fields. Fill your search parameters, then click <strong>[Search]</strong>. You\'ll be able to save your search and add individual products to your Product Set.', DFRPS_DOMAIN ) . '</p>'
			) );
		
			$screen->add_help_tab( array(
				'id'		=> 'dfrps_docs_saved_search',
				'title'		=> __( 'Saved Search tab', DFRPS_DOMAIN ),
				'content'	=> 
					'<h2>' . __( "Saved Search Tab", DFRPS_DOMAIN ) . '</h2>' . 
					'<p>' . __( 'After you save a search, the results will appear on the Saved Search tab. You can also delete your saved search on this tab.', DFRPS_DOMAIN ) . '</p>' . 
					'<p>' . __( 'The number on the Saved Search tab indicates the products in your saved search results, not the total number of products in your Product Set. You can delete your saved search on this tab.', DFRPS_DOMAIN ) . '</p>'
			) );
		
			$screen->add_help_tab( array(
				'id'		=> 'dfrps_docs_single_products',
				'title'		=> __( 'Single Products tab', DFRPS_DOMAIN ),
				'content'	=> 
					'<h2>' . __( "Single Products Tab", DFRPS_DOMAIN ) . '</h2>' . 
					'<p>' . __( 'The Single Product tab lists all the products that you add to your Product Set one by one, using the <img src="' . DFRPS_URL . 'images/icons/plus.png" class="dfrps_valign_middle" /> button. The number on the tab shows how many products single products you’ve added, not the total number of products in your Product Set. To remove individually-added products from your Product Set, click the <img src="' . DFRPS_URL . 'images/icons/minus.png" class="dfrps_valign_middle" /> icon next to the item on the Single Product tab.', DFRPS_DOMAIN ) . '</p>' 
			) );
		
			$screen->add_help_tab( array(
				'id'		=> 'dfrps_docs_blocked_products',
				'title'		=> __( 'Blocked Products tab', DFRPS_DOMAIN ),
				'content'	=> 
					'<h2>' . __( "Blocked Products Tab", DFRPS_DOMAIN ) . '</h2>' . 
					'<p>' . __( 'Remove products from your search results and Product Set by clicking the <img src="' . DFRPS_URL . 'images/icons/block.png" class="dfrps_valign_middle" /> button. All the products you\'ve blocked will be listed on the Blocked Products tab. To unblock products on this list, click <img src="' . DFRPS_URL . 'images/icons/unblock.png" class="dfrps_valign_middle" />.', DFRPS_DOMAIN ) . '</p>'
			) );
		
			$screen->add_help_tab( array(
				'id'		=> 'dfrps_docs_record',
				'title'		=> __( 'Product Record', DFRPS_DOMAIN ),
				'content'	=> 
					'<h2>' . __( "Product Record", DFRPS_DOMAIN ) . '</h2>' . 
					'<p>' . __( 'How to understand the hidden and displayed information in a product record:', DFRPS_DOMAIN ) . '</p>' . 
					'<p><img src="' . DFRPS_URL . 'images/icons/productrecord.png" /></p>' . 
					'<h2>' . __( "Action Links Legend", DFRPS_DOMAIN ) . '</h2>
					<p><img src="' . DFRPS_URL . 'images/icons/plus.png" class="dfrps_valign_middle" /> ' . __( 'Click to add product to Product Set individually, as a Single Product.', DFRPS_DOMAIN ) . '</p>
					<p><img src="' . DFRPS_URL . 'images/icons/checkmark.png" class="dfrps_valign_middle" /> ' . __( 'Indicates product was added to Product Set individually.', DFRPS_DOMAIN ) . '</p>
					<p><img src="' . DFRPS_URL . 'images/icons/minus.png" class="dfrps_valign_middle" /> ' . __( 'Click to remove product from Single Products list.', DFRPS_DOMAIN ) . '</p>
					<p><img src="' . DFRPS_URL . 'images/icons/block.png" class="dfrps_valign_middle" /> ' . __( 'Click to block product from Product Set and searches.', DFRPS_DOMAIN ) . '</p>
					<p><img src="' . DFRPS_URL . 'images/icons/unblock.png" class="dfrps_valign_middle" /> ' . __( 'Click to remove product from Blocked Products list.', DFRPS_DOMAIN ) . '</p>
					'
			) );

		// This is the "List" of product sets page (All Product Sets)
		} elseif ( $screen->id == 'edit-' . DFRPS_CPT ) {
		
			$screen->add_help_tab( array(
				'id'		=> 'dfrps_docs_column_headers',
				'title'		=> __( 'Column Headers', DFRPS_DOMAIN ),
				'content'	=> 
					
					'<h2>' . __( "Column Headers", DFRPS_DOMAIN ) . '</h2>' . 
					
					'<p>' . __( "Information displayed in the Product Sets table:", DFRPS_DOMAIN ) . '</p>' . 
					'<ul>' . 
						'<li><strong>' . __( "Title", DFRPS_DOMAIN ) . '</strong> - ' 			. __( "Product Set title is optional, for your reference only, and not publicly viewable.", DFRPS_DOMAIN ) . '</li>' .
						'<li><strong>' . __( "Created", DFRPS_DOMAIN ) . '</strong> - ' 		. __( "Date/time the set was published.", DFRPS_DOMAIN ) . '</li>' .
						'<li><strong>' . __( "Modified", DFRPS_DOMAIN ) . '</strong> - ' 		. __( "Date/time the set was last modified, ex. title changed.", DFRPS_DOMAIN ) . '</li>' .
						'<li><strong>' . __( "Status", DFRPS_DOMAIN ) . '</strong> - ' 			. __( "Publication status can be: Published; Draft; Pending; or Trash. Only \"Published\" or \"Scheduled\" Product Sets will be imported or updated.", DFRPS_DOMAIN ) . '</li>' .
						'<li><strong>' . __( "Next Update", DFRPS_DOMAIN ) . '</strong> - ' 	. __( "Date/time the set is scheduled to be updated.", DFRPS_DOMAIN ) . '</li>' .
						'<li><strong>' . __( "Started", DFRPS_DOMAIN ) . '</strong> - ' 		. __( "Date/time the set’s last update started.", DFRPS_DOMAIN ) . '</li>' .
						'<li><strong>' . __( "Completed", DFRPS_DOMAIN ) . '</strong> - ' 		. __( "Date/time the set’s last update completed.", DFRPS_DOMAIN ) . '</li>' .
						'<li><strong>' . __( "Added", DFRPS_DOMAIN ) . '</strong> - ' 			. __( "The number of products added or updated during the last update.", DFRPS_DOMAIN ) . '</li>' .
						'<li><strong>' . __( "Deleted", DFRPS_DOMAIN ) . '</strong> - ' 		. __( "The number of products deleted during the last update.", DFRPS_DOMAIN ) . '</li>' .
						'<li><strong>' . __( "API Requests", DFRPS_DOMAIN ) . '</strong> - ' 	. __( "The number of <a href=\"https://v4.datafeedr.com/node/373\" target=\"_blank\">API requests</a> required during the last update.", DFRPS_DOMAIN ) . '</li>' .
					'</ul>' . 
					'<p>' . __( "<strong>TIP!</strong> To hide a column:", DFRPS_DOMAIN ) . '</p>' . 
					'<ol>' . 
						'<li>' . __( 'Close this Help box by clicking the "Help" tab label (lower right).', DFRPS_DOMAIN ) . '</li>' . 
						'<li>' . __( 'Open the "Screen Options" tab.', DFRPS_DOMAIN ) . '</li>' . 
						'<li>' . __( "Deselect the headers you want to hide.", DFRPS_DOMAIN ) . '</li>' . 
					'</ol>'
			) );
		
		// This is the Configuration page.
		} elseif ( $screen->id == 'product-sets_page_dfrps_configuration' ) {

			$screen->add_help_tab( array(
				'id'		=> 'dfrps_config_search',
				'title'		=> __( 'Search Settings', DFRPS_DOMAIN ),
				'content'	=> 
					'<h2>' . __( "Search Settings", DFRPS_DOMAIN ) . '</h2>' . 
					'<p><strong>' . __( 'Products per Search', DFRPS_DOMAIN ) . '</strong> - ' . 
					__( "This sets how many products display per page of search results in the admin area of your site. This setting does not affect how many products display to visitors on the front end of your site.", DFRPS_DOMAIN ) . '</p>' .
					'<p><strong>' . __( 'Default Search Setting', DFRPS_DOMAIN ) . '</strong> - ' . 
					__( "This setting configures how the product search form loads when creating a new Product Set. Changing the default settings will not affect already created Product Sets.", DFRPS_DOMAIN ) . '</p>'
			) );
		
			$screen->add_help_tab( array(
				'id'		=> 'dfrps_config_general_update',
				'title'		=> __( 'General Update Settings', DFRPS_DOMAIN ),
				'content'	=> 
					
					'<h2>' . __( "General Update Settings", DFRPS_DOMAIN ) . '</h2>' . 
					
					'<p><strong>' . __( 'Default Custom Post Type', DFRPS_DOMAIN ) . '</strong> - ' . 
					__( "This is the Custom Post Type that will be selected by default when creating a new Product Set.  In most cases there will only be one option.", DFRPS_DOMAIN ) . '</p>' .

					'<p><strong>' . __( 'Delete Missing Products', DFRPS_DOMAIN ) . '</strong> - ' . 
					__( "This setting configures how products which are no longer available from the API are handled on your site. By default, those products will be moved to the Trash and deleted after ", DFRPS_DOMAIN ) . EMPTY_TRASH_DAYS  . __( " days.", DFRPS_DOMAIN ) . '</p>' .
					
					'<p><strong>' . __( 'Updates', DFRPS_DOMAIN ) . '</strong> - ' . 
					__( "This setting allows you to disable updates. If you've used all of your monthly API requests, updates become disabled automatically.", DFRPS_DOMAIN ) . '</p>'
			) );
		
			$screen->add_help_tab( array(
				'id'		=> 'dfrps_config_advanced_update',
				'title'		=> __( 'Advanced Update Settings', DFRPS_DOMAIN ),
				'content'	=> 
					
					'<h2>' . __( "Advanced Update Settings", DFRPS_DOMAIN ) . '</h2>' . 
					
					'<p class="dfrps_warning"><strong>' . __( 'WARNING', DFRPS_DOMAIN ) . '</strong> - ' . __( "Updates are <strong>SERVER INTENSIVE</strong>. Modifying these values could cause server or hosting issues. Change with caution!", DFRPS_DOMAIN ) . '</p>' .
					
					'<p><strong>' . __( 'Update Interval', DFRPS_DOMAIN ) . '</strong> - ' . 
					__( "This setting determines how often products in a Product Set should be updated.", DFRPS_DOMAIN ) . '</p>' .
					
					'<p><strong>' . __( 'Cron Interval', DFRPS_DOMAIN ) . '</strong> - ' . 
					__( "This setting controls how often WordPress Cron will run: 1) to check if a Product Set needs to be updated, or 2) to perform the next step in the update process if a Product Set is currently being updated.", DFRPS_DOMAIN ) . '</p>' .
					
					'<p><strong>' . __( 'Products per Update', DFRPS_DOMAIN ) . '</strong> - ' . 
					__( "This setting controls how many products per batch will update.", DFRPS_DOMAIN ) . '</p>' .
					
					'<p><strong>' . __( 'Products per API Request', DFRPS_DOMAIN ) . '</strong> - ' . 
					__( "This setting controls the maximum number of products returned per API request.", DFRPS_DOMAIN ) . '</p>' .
					
					'<p><strong>' . __( 'Preprocess Maximum', DFRPS_DOMAIN ) . '</strong> - ' . 
					__( "This sets the number of products per batch to prepare for updating. Preprocessing includes flagging all products in a Product Set as being ready for updating and modifying those products' categories.", DFRPS_DOMAIN ) . '</p>' .
					
					'<p><strong>' . __( 'Postprocess Maximum', DFRPS_DOMAIN ) . '</strong> - ' . 
					__( "This sets the number of products to process per batch upon completion of a Product Set update. Postprocessing includes deleting any old or missing products.", DFRPS_DOMAIN ) . '</p>'

			) );
		
		// This is the Tools page.
		} elseif ( $screen->id == 'product-sets_page_dfrps_tools' ) {

			$screen->add_help_tab( array(
				'id'		=> 'dfrps_tools',
				'title'		=> __( 'Tools', DFRPS_DOMAIN ),
				'content'	=> 
					'<h2>' . __( "Datafeedr Product Sets Tools", DFRPS_DOMAIN ) . '</h2>' . 
					
					'<p><strong>' . __( 'Test HTTP Loopback', DFRPS_DOMAIN ) . '</strong> - ' . 
					__( "Use this tool to determine if your web host has disabled the WordPress Cron functionality.", DFRPS_DOMAIN ) . '</p>' .
					
					'<p><strong>' . __( 'Reset Cron', DFRPS_DOMAIN ) . '</strong> - ' . 
					__( "Use this tool to reset your Datafeedr Product Sets import/update cron schedule.", DFRPS_DOMAIN ) . '</p>' . 
					
					'<p><strong>' . __( 'Fix Missing Images', DFRPS_DOMAIN ) . '</strong> - ' . 
					__( "Use this tool to import any product images that were not properly imported previously.", DFRPS_DOMAIN ) . '</p>' . 
					
					'<p><strong>' . __( 'Bulk Image Import', DFRPS_DOMAIN ) . '</strong> - ' . 
					__( "Use this tool to immediately begin importing product images.", DFRPS_DOMAIN ) . '</p>'
			) );
		
		}

		// The following tabs appear on ALL screens.		
		dfrapi_help_tab( $screen );


	}
}

endif;

return new Dfrps_Admin_Help();