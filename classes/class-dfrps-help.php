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
				'title'	=> __( 'Product Set Overview', 'datafeedr-product-sets' ),
				'content'	=> 
					'<h2>' . __( "What is a Product Set?", 'datafeedr-product-sets' ) . '</h2>' .
					'<p>' . __( "A Product Set contains a collection of related products and is responsible for importing those products into your blog and keeping them up-to-date.", 'datafeedr-product-sets' ) . '</p>' .
					'<ul>' . 
						'<li><strong>' . __( "Adding products", 'datafeedr-product-sets' ) . '</strong> - ' . __( "Build your Product Set in one of two ways: By saving a search you can add all the products in your search results to your Product Set at once. By adding a single product individually you can handpick items to add to your set. You can use one or both methods of adding products when building a Product Set.", 'datafeedr-product-sets' ) . '</li>' .
						'<li><strong>' . __( "Importing products", 'datafeedr-product-sets' ) . '</strong> - ' . __( "When you publish your Product Set, it enters the update queue. The products will be imported into your blog when the Product Set reaches its turn in the queue.", 'datafeedr-product-sets' ) . '</li>' .
						'<li><strong>' . __( "Updating products", 'datafeedr-product-sets' ) . '</strong> - ' . __( "The Product Set is also responsible for keeping its imported products up-to-date. The update interval is configured on the Product Sets > Configuration page.", 'datafeedr-product-sets' ) . '</li>' .
					'</ul>' . 
					'<p>' . __( "", 'datafeedr-product-sets' ) . '</p>'
			) );

			$screen->add_help_tab( array(
				'id'		=> 'dfrps_docs_dashboard',
				'title'		=> __( 'Dashboard', 'datafeedr-product-sets' ),
				'content'	=> 
					'<h2>' . __( "Dashboard", 'datafeedr-product-sets' ) . '</h2>' .
					'<p>' . __( 'The Dashboard guides you through the process of creating a new Product Set. It gives you an overview of the Product Set\'s status, informs you about the update status after you publish, and provides quick links to perform additional actions.', 'datafeedr-product-sets' ) . '</p>' .
					'<p>' . __( "By default, the Dashboard can be found at the top of the right column.", 'datafeedr-product-sets' ) . '</p>'
				) );

			$screen->add_help_tab( array(
				'id'		=> 'dfrps_docs_title',
				'title'		=> __( 'Title', 'datafeedr-product-sets' ),
				'content'	=> 
					'<h2>' . __( "Product Set Title", 'datafeedr-product-sets' ) . '</h2>' .
					'<p>' . __( 'The Product Set title is for your reference only. Adding a title is optional, but allows you to identify the Product Set in the future. A short, descriptive title is recommended.', 'datafeedr-product-sets' ) . '</p>'
			) );

			$screen->add_help_tab( array(
				'id'		=> 'dfrps_docs_search',
				'title'		=> __( 'Search tab', 'datafeedr-product-sets' ),
				'content'	=> 
					'<h2>' . __( "Search Tab", 'datafeedr-product-sets' ) . '</h2>' .
					'<p>' . __( 'Start your search on the Search tab. Click <strong>+ add filter</strong> to add additional search fields or <img src="' . DFRAPI_URL . 'images/icons/removefilter.png" class="dfrps_valign_middle" /> to remove fields. Fill your search parameters, then click <strong>[Search]</strong>. You\'ll be able to save your search and add individual products to your Product Set.', 'datafeedr-product-sets' ) . '</p>'
			) );

			$screen->add_help_tab( array(
				'id'		=> 'dfrps_docs_saved_search',
				'title'		=> __( 'Saved Search tab', 'datafeedr-product-sets' ),
				'content'	=> 
					'<h2>' . __( "Saved Search Tab", 'datafeedr-product-sets' ) . '</h2>' .
					'<p>' . __( 'After you save a search, the results will appear on the Saved Search tab. You can also delete your saved search on this tab.', 'datafeedr-product-sets' ) . '</p>' .
					'<p>' . __( 'The number on the Saved Search tab indicates the products in your saved search results, not the total number of products in your Product Set. You can delete your saved search on this tab.', 'datafeedr-product-sets' ) . '</p>'
			) );

			$screen->add_help_tab( array(
				'id'		=> 'dfrps_docs_single_products',
				'title'		=> __( 'Single Products tab', 'datafeedr-product-sets' ),
				'content'	=> 
					'<h2>' . __( "Single Products Tab", 'datafeedr-product-sets' ) . '</h2>' .
					'<p>' . __( 'The Single Product tab lists all the products that you add to your Product Set one by one, using the <img src="' . DFRPS_URL . 'images/icons/plus.png" class="dfrps_valign_middle" /> button. The number on the tab shows how many products single products you’ve added, not the total number of products in your Product Set. To remove individually-added products from your Product Set, click the <img src="' . DFRPS_URL . 'images/icons/minus.png" class="dfrps_valign_middle" /> icon next to the item on the Single Product tab.', 'datafeedr-product-sets' ) . '</p>'
			) );

			$screen->add_help_tab( array(
				'id'		=> 'dfrps_docs_blocked_products',
				'title'		=> __( 'Blocked Products tab', 'datafeedr-product-sets' ),
				'content'	=> 
					'<h2>' . __( "Blocked Products Tab", 'datafeedr-product-sets' ) . '</h2>' .
					'<p>' . __( 'Remove products from your search results and Product Set by clicking the <img src="' . DFRPS_URL . 'images/icons/block.png" class="dfrps_valign_middle" /> button. All the products you\'ve blocked will be listed on the Blocked Products tab. To unblock products on this list, click <img src="' . DFRPS_URL . 'images/icons/unblock.png" class="dfrps_valign_middle" />.', 'datafeedr-product-sets' ) . '</p>'
			) );

			$screen->add_help_tab( array(
				'id'		=> 'dfrps_docs_record',
				'title'		=> __( 'Product Record', 'datafeedr-product-sets' ),
				'content'	=> 
					'<h2>' . __( "Product Record", 'datafeedr-product-sets' ) . '</h2>' .
					'<p>' . __( 'How to understand the hidden and displayed information in a product record:', 'datafeedr-product-sets' ) . '</p>' .
					'<p><img src="' . DFRPS_URL . 'images/icons/productrecord.png" /></p>' . 
					'<h2>' . __( "Action Links Legend", 'datafeedr-product-sets' ) . '</h2>
					<p><img src="' . DFRPS_URL . 'images/icons/plus.png" class="dfrps_valign_middle" /> ' . __( 'Click to add product to Product Set individually, as a Single Product.', 'datafeedr-product-sets' ) . '</p>
					<p><img src="' . DFRPS_URL . 'images/icons/checkmark.png" class="dfrps_valign_middle" /> ' . __( 'Indicates product was added to Product Set individually.', 'datafeedr-product-sets' ) . '</p>
					<p><img src="' . DFRPS_URL . 'images/icons/minus.png" class="dfrps_valign_middle" /> ' . __( 'Click to remove product from Single Products list.', 'datafeedr-product-sets' ) . '</p>
					<p><img src="' . DFRPS_URL . 'images/icons/block.png" class="dfrps_valign_middle" /> ' . __( 'Click to block product from Product Set and searches.', 'datafeedr-product-sets' ) . '</p>
					<p><img src="' . DFRPS_URL . 'images/icons/unblock.png" class="dfrps_valign_middle" /> ' . __( 'Click to remove product from Blocked Products list.', 'datafeedr-product-sets' ) . '</p>
					'
			) );

		// This is the "List" of product sets page (All Product Sets)
		} elseif ( $screen->id == 'edit-' . DFRPS_CPT ) {

			$screen->add_help_tab( array(
				'id'      => 'dfrps_docs_column_headers',
				'title'   => __( 'Column Headers', 'datafeedr-product-sets' ),
				'content' =>

					'<h2>' . __( "Column Headers", 'datafeedr-product-sets' ) . '</h2>' .

					'<p>' . __( "Information displayed in the Product Sets table:", 'datafeedr-product-sets' ) . '</p>' .
					'<ul>' . 
						'<li><strong>' . __( "Title", 'datafeedr-product-sets' ) . '</strong> - ' 			. __( "Product Set title is optional, for your reference only, and not publicly viewable.", 'datafeedr-product-sets' ) . '</li>' .
						'<li><strong>' . __( "Created", 'datafeedr-product-sets' ) . '</strong> - ' 		. __( "Date/time the set was published.", 'datafeedr-product-sets' ) . '</li>' .
						'<li><strong>' . __( "Modified", 'datafeedr-product-sets' ) . '</strong> - ' 		. __( "Date/time the set was last modified, ex. title changed.", 'datafeedr-product-sets' ) . '</li>' .
						'<li><strong>' . __( "Status", 'datafeedr-product-sets' ) . '</strong> - ' 			. __( "Publication status can be: Published; Draft; Pending; or Trash. Only \"Published\" or \"Scheduled\" Product Sets will be imported or updated.", 'datafeedr-product-sets' ) . '</li>' .
						'<li><strong>' . __( "Next Update", 'datafeedr-product-sets' ) . '</strong> - ' 	. __( "Date/time the set is scheduled to be updated.", 'datafeedr-product-sets' ) . '</li>' .
						'<li><strong>' . __( "Started", 'datafeedr-product-sets' ) . '</strong> - ' 		. __( "Date/time the set’s last update started.", 'datafeedr-product-sets' ) . '</li>' .
						'<li><strong>' . __( "Completed", 'datafeedr-product-sets' ) . '</strong> - ' 		. __( "Date/time the set’s last update completed.", 'datafeedr-product-sets' ) . '</li>' .
						'<li><strong>' . __( "Added", 'datafeedr-product-sets' ) . '</strong> - ' 			. __( "The number of products added or updated during the last update.", 'datafeedr-product-sets' ) . '</li>' .
						'<li><strong>' . __( "Deleted", 'datafeedr-product-sets' ) . '</strong> - ' 		. __( "The number of products deleted during the last update.", 'datafeedr-product-sets' ) . '</li>' .
						'<li><strong>' . __( "API Requests", 'datafeedr-product-sets' ) . '</strong> - ' 	. __( "The number of API requests required during the last update.", 'datafeedr-product-sets' ) . '</li>' .
					'</ul>' . 
					'<p>' . __( "<strong>TIP!</strong> To hide a column:", 'datafeedr-product-sets' ) . '</p>' .
					'<ol>' . 
						'<li>' . __( 'Close this Help box by clicking the "Help" tab label (lower right).', 'datafeedr-product-sets' ) . '</li>' .
						'<li>' . __( 'Open the "Screen Options" tab.', 'datafeedr-product-sets' ) . '</li>' .
						'<li>' . __( "Deselect the headers you want to hide.", 'datafeedr-product-sets' ) . '</li>' .
					'</ol>'
			) );

		// This is the Configuration page.
		} elseif ( $screen->id == 'product-sets_page_dfrps_configuration' ) {

			$screen->add_help_tab( array(
				'id'		=> 'dfrps_config_search',
				'title'		=> __( 'Search Settings', 'datafeedr-product-sets' ),
				'content'	=> 
					'<h2>' . __( "Search Settings", 'datafeedr-product-sets' ) . '</h2>' .
					'<p><strong>' . __( 'Products per Search', 'datafeedr-product-sets' ) . '</strong> - ' .
					__( "This sets how many products display per page of search results in the admin area of your site. This setting does not affect how many products display to visitors on the front end of your site.", 'datafeedr-product-sets' ) . '</p>' .
					'<p><strong>' . __( 'Default Search Setting', 'datafeedr-product-sets' ) . '</strong> - ' .
					__( "This setting configures how the product search form loads when creating a new Product Set. Changing the default settings will not affect already created Product Sets.", 'datafeedr-product-sets' ) . '</p>'
			) );

			$screen->add_help_tab( array(
				'id'		=> 'dfrps_config_general_update',
				'title'		=> __( 'General Update Settings', 'datafeedr-product-sets' ),
				'content'	=> 

					'<h2>' . __( "General Update Settings", 'datafeedr-product-sets' ) . '</h2>' .

					'<p><strong>' . __( 'Default Custom Post Type', 'datafeedr-product-sets' ) . '</strong> - ' .
					__( "This is the Custom Post Type that will be selected by default when creating a new Product Set.  In most cases there will only be one option.", 'datafeedr-product-sets' ) . '</p>' .

					'<p><strong>' . __( 'Delete Missing Products', 'datafeedr-product-sets' ) . '</strong> - ' .
					__( "This setting configures how products which are no longer available from the API are handled on your site. By default, those products will be moved to the Trash and deleted after ", 'datafeedr-product-sets' ) . EMPTY_TRASH_DAYS  . __( " days.", 'datafeedr-product-sets' ) . '</p>' .

					'<p><strong>' . __( 'Updates', 'datafeedr-product-sets' ) . '</strong> - ' .
					__( "This setting allows you to disable updates. If you've used all of your monthly API requests, updates become disabled automatically.", 'datafeedr-product-sets' ) . '</p>'
			) );

			$screen->add_help_tab( array(
				'id'      => 'dfrps_config_advanced_update',
				'title'   => __( 'Advanced Update Settings', 'datafeedr-product-sets' ),
				'content' =>

					'<h2>' . __( "Advanced Update Settings", 'datafeedr-product-sets' ) . '</h2>' .

					'<p class="dfrps_warning"><strong>' . __( 'WARNING', 'datafeedr-product-sets' ) . '</strong> - ' . __( "Updates are <strong>SERVER INTENSIVE</strong>. Modifying these values could cause server or hosting issues. Change with caution!", 'datafeedr-product-sets' ) . '</p>' .

					'<p><strong>' . __( 'Update Interval', 'datafeedr-product-sets' ) . '</strong> - ' .
					__( "This setting determines how often products in a Product Set should be updated.", 'datafeedr-product-sets' ) . '</p>' .

					'<p><strong>' . __( 'Cron Interval', 'datafeedr-product-sets' ) . '</strong> - ' .
					__( "This setting controls how often WordPress Cron will run: 1) to check if a Product Set needs to be updated, or 2) to perform the next step in the update process if a Product Set is currently being updated.", 'datafeedr-product-sets' ) . '</p>' .

					'<p><strong>' . __( 'Products per Update', 'datafeedr-product-sets' ) . '</strong> - ' .
					__( "This setting controls how many products per batch will update.", 'datafeedr-product-sets' ) . '</p>' .

					'<p><strong>' . __( 'Products per API Request', 'datafeedr-product-sets' ) . '</strong> - ' .
					__( "This setting controls the maximum number of products returned per API request.", 'datafeedr-product-sets' ) . '</p>' .

					'<p><strong>' . __( 'Preprocess Maximum', 'datafeedr-product-sets' ) . '</strong> - ' .
					__( "This sets the number of products per batch to prepare for updating. Preprocessing includes flagging all products in a Product Set as being ready for updating and modifying those products' categories.", 'datafeedr-product-sets' ) . '</p>' .

					'<p><strong>' . __( 'Postprocess Maximum', 'datafeedr-product-sets' ) . '</strong> - ' .
					__( "This sets the number of products to process per batch upon completion of a Product Set update. Postprocessing includes deleting any old or missing products.", 'datafeedr-product-sets' ) . '</p>'

			) );

		// This is the Tools page.
		} elseif ( $screen->id == 'product-sets_page_dfrps_tools' ) {

			$screen->add_help_tab( array(
				'id'		=> 'dfrps_tools',
				'title'		=> __( 'Tools', 'datafeedr-product-sets' ),
				'content'	=> 
					'<h2>' . __( "Datafeedr Product Sets Tools", 'datafeedr-product-sets' ) . '</h2>' .

					'<p><strong>' . __( 'Test HTTP Loopback', 'datafeedr-product-sets' ) . '</strong> - ' .
					__( "Use this tool to determine if your web host has disabled the WordPress Cron functionality.", 'datafeedr-product-sets' ) . '</p>' .

					'<p><strong>' . __( 'Reset Cron', 'datafeedr-product-sets' ) . '</strong> - ' .
					__( "Use this tool to reset your Datafeedr Product Sets import/update cron schedule.", 'datafeedr-product-sets' ) . '</p>' .

					'<p><strong>' . __( 'Fix Missing Images', 'datafeedr-product-sets' ) . '</strong> - ' .
					__( "Use this tool to import any product images that were not properly imported previously.", 'datafeedr-product-sets' ) . '</p>' .

					'<p><strong>' . __( 'Bulk Image Import', 'datafeedr-product-sets' ) . '</strong> - ' .
					__( "Use this tool to immediately begin importing product images.", 'datafeedr-product-sets' ) . '</p>'
			) );

		}

		// The following tabs appear on ALL screens.
		dfrapi_help_tab( $screen );


	}
}

endif;

return new Dfrps_Admin_Help();