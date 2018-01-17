<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Define NONCE stuff.
$nonce = wp_create_nonce( 'dfrps_ajax_nonce' );

// Add Javascript here (because we need access to PHP values)
?>

<script>

(function($) {

    var dashboard_refresh_time = 5000;
    var reload_dashboard = function() {
        
        // Only refresh dashboard if #dfrps_cpt_dashboard_metabox exists.
        if( !$("#dfrps_cpt_dashboard_metabox").length ) {
            return;
        }
        
        return $.ajax({
            type: "POST",
            url: "<?php echo admin_url( 'admin-ajax.php' ); ?>",
            cache: false,
            data: {
                action: "dfrps_ajax_dashboard",
                dfrps_security: "<?php echo $nonce; ?>",
                postid: <?php echo get_the_ID(); ?>
            },
            success: function(data) {
                var current_dashboard_data = $("#dfrps_cpt_dashboard_metabox #dfrps_dynamic_dashboard_area").html();
                var current_state = $(current_dashboard_data + " > div").attr("state");
                var new_state = $(data + " > div").attr("state");
                if ( current_state != new_state ) {
                    $("#dfrps_cpt_dashboard_metabox #dfrps_dynamic_dashboard_area").fadeOut(400, function() {
                        $("#dfrps_cpt_dashboard_metabox #dfrps_dynamic_dashboard_area").html(data).fadeIn(400);
                    });
                }
                setTimeout(reload_dashboard, dashboard_refresh_time);
            }
        });
    };
    
	/**
	 * Save category selection (upon checking checkbox).
	 */
	function save_category_selection(checklistElement) {
		var cpt = checklistElement.attr("cpt");
		var checklist = checklistElement.attr("id");
		
		// http://stackoverflow.com/a/14474805/2489248
		var categoryIds = $("#"+checklist+" input:checked").map(function() {
			return $(this).val();
		}).get();

		$(".dfrps_category_selection_panel input").attr("disabled", true);
		$(".dfrps_saving_taxonomy").css("display", "block");

		$.ajax({
			type: "POST",
			url: "<?php echo admin_url( 'admin-ajax.php' ); ?>",
			data: {
				action: "dfrps_ajax_update_taxonomy",
				dfrps_security: "<?php echo $nonce; ?>",
				postid: <?php echo get_the_ID(); ?>,
				cids: categoryIds,
				cpt: cpt
			}
		}).done(function( html ) {
			$(".dfrps_saving_taxonomy").css("display", "none");
			$(".dfrps_category_selection_panel input").removeAttr("disabled");
			
			// Line-through steps
			$("#dfrps_step_category").addClass( "dfrps_dashboard_step_completed" );	

		});
	}

	/**
	 * This is a helper function which gets the number for a given element.
	 */
	function dfrpsGetElementsIntValue( element ) {
		var el = $( element ).first().text();
		var intval = parseInt( el.replace( ',', '' ), 10 );
		if ( isNaN( intval ) ) {
			return 0;
		}
		if ( intval < 0 ) {
			return 0;
		}
		return intval;
	}

	/**
	 * This is a helper function which flashes a tab when something happens in another tab.
	 */			
	function dfrpsHighlightFadeTab( element ) {
		var current_bg_color = $(element).css("background-color");
		$(element).stop().animate({ backgroundColor: "yellow" }, 50 ).delay( 50 ).animate({ backgroundColor: current_bg_color }, 300 );
	}

	/**
	 * This is a helper function which adds 1 to an element.
	 */	
	 function dfrpsAddOne( element ) {
		var current_value = dfrpsGetElementsIntValue( element );
		var new_value = (current_value + 1);
		if ( new_value >= 0 ) {
			$(element).text(new_value);
		} else {
			$(element).text(0);
		}
	}

	/**
	 * This is a helper function which subtracts 1 from an element.
	 */	
	function dfrpsRemoveOne( element ) {
		var current_value = dfrpsGetElementsIntValue( element );
		var new_value = (current_value - 1);
		if ( new_value >= 0 ) {	
			$(element).text(new_value);
		} else {
			$(element).text(0);
		}
	}

	function dfrpsHideShowCategoryLists() {
		$(".dfrps_cpt_picker").each(function( index ) {
			var id = $(this).attr("id");
			if ($(this).is(':checked')) {
				$("#"+id+"_chooser").css("display", "block");
			} else {
				$("#"+id+"_chooser").css("display", "none");
			}
		});
	}

	/**
	 * Get content for Saved Search tab.
	 */
	function dfrpsGetSavedSearch() {
		var div = "div_dfrps_tab_saved_search";
		return $.ajax({
			type: "POST",
			url: "<?php echo admin_url( 'admin-ajax.php' ); ?>",
			data: { 
				action: "dfrps_ajax_get_products",
				dfrps_security: "<?php echo $nonce; ?>",
				postid: <?php echo get_the_ID(); ?>,
				context: div
			}
		}).done(function(html) {
			$("#"+div+" .inside").html(html);
			$("#tab_saved_search").removeClass('tab_disabled').addClass('tab_enabled');
			$("#tab_saved_search span.count").text( dfrpsGetElementsIntValue( "#"+div+" .dfrps_relevant_results" ) );
		});
	}

	/**
	 * Get content for Included Products tab.
	 */
	function dfrpsGetIncludedProducts() {
		var div = "div_dfrps_tab_included";
		return $.ajax({
			type: "POST",
			url: "<?php echo admin_url( 'admin-ajax.php' ); ?>",
			data: { 
				action: "dfrps_ajax_get_products",
				dfrps_security: "<?php echo $nonce; ?>",
				postid: <?php echo get_the_ID(); ?>,
				context: div
			}
		}).done(function(html) {
			$("#"+div+" .inside").html(html);
			$("#tab_included").removeClass('tab_disabled').addClass('tab_enabled');
			$("#tab_included span.count").text( dfrpsGetElementsIntValue( "#"+div+" .dfrps_relevant_results" ) );
		});
	}

	/**
	 * Get content for Blocked Products tab.
	 */
	function dfrpsGetBlockedProducts() {
		var div = "div_dfrps_tab_blocked";
		return $.ajax({
			type: "POST",
			url: "<?php echo admin_url( 'admin-ajax.php' ); ?>",
			data: { 
				action: "dfrps_ajax_get_products", 
				dfrps_security: "<?php echo $nonce; ?>",
				postid: <?php echo get_the_ID(); ?>,
				context: div
			}
		}).done(function(html) {
			$("#"+div+" .inside").html(html);
			$("#tab_blocked").removeClass('tab_disabled').addClass('tab_enabled');
			$("#tab_blocked span.count").text( dfrpsGetElementsIntValue( "#"+div+" .dfrps_relevant_results" ) );
		});
	}
	
	function dfrps_init() {

		dfrpsHideShowCategoryLists();
		reload_dashboard()
			.then(dfrpsGetBlockedProducts)
			.then(dfrpsGetIncludedProducts)
			.done(dfrpsGetSavedSearch);


		/**
		 * Line-through title when title is filled in.
		 */
		var timeoutReference;
		$('input#title').keypress(function() {
			var _this = $(this); // copy of this object for further usage

			if (timeoutReference) clearTimeout(timeoutReference);
			timeoutReference = setTimeout(function() {
				// Line-through steps
				$("#dfrps_step_title").addClass( "dfrps_dashboard_step_completed" );
			}, 3000);
		});

		/**
		 * Load search results.
		 */
		$(".datafeedr-productset_admin").on("click", "#dfrps_cpt_search", function (e) {

			// Set variables.
			var div = $(this).closest('.postbox').attr("id");
			var page = $(this).attr("page");

			// Disable search and save search buttons.
			$("#dfrps_cpt_save_search").addClass("button-primary-disabled");
			$("#dfrps_cpt_search").addClass("button-disabled").val("<?php echo dfrps_helper_js_text('searching'); ?>");

			// Display "loading..." image.				
			$("#div_dfrps_tab_search_results").html('<div class="dfrps_loading"></div><div class="dfrps_searching_x_products">Searching <?php echo dfrapi_get_total_products_in_db(TRUE, ''); ?> products...</div>');

			$.ajax({
				type: "POST",
				url: "<?php echo admin_url( 'admin-ajax.php' ); ?>",
				data: { 
					action: "dfrps_ajax_get_products",
					dfrps_security: "<?php echo $nonce; ?>",
					postid: <?php echo get_the_ID(); ?>,
					query: $("form .filter:visible :input").serialize(),
					page: page,
					context: div
				}
	
			}).done(function( html ) {

				// Undisable search and save search buttons.
				$("#dfrps_cpt_save_search").removeClass("button-primary-disabled");
				$("#dfrps_cpt_search").removeClass("button-disabled").val("<?php echo dfrps_helper_js_text('search'); ?>");
	
				// Display "Save Search" button & "view api request" link.
				$("#dfrps_save_update_search_actions").show();
				$(".dfrps_raw_query").show();
	
				// Display search results.
				$("#div_dfrps_tab_search_results").fadeOut(100).hide().fadeIn(100).html(html);
			
				// Line-through steps
				$("#dfrps_step_search").addClass( "dfrps_dashboard_step_completed" );	

	
			});

			e.preventDefault();
		});

		/**
		 * Pagination functionality.
		 */
		$(".datafeedr-productset_admin").on("click", ".dfrps_pager", function(e) {

			// Set variables.
			var page = $(this).attr("page");
			var div = $(this).closest('.postbox').attr("id");
			var element = ( div == 'div_dfrps_tab_search' ) ? "#"+div+" .inside #div_dfrps_tab_search_results" : "#"+div+" .inside";

			// Display "loading..." image.	
			$(element).html('<div class="dfrps_loading"></div>');

			$.ajax({
				type: "POST",
				url: "<?php echo admin_url( 'admin-ajax.php' ); ?>",
				data: {
					action: "dfrps_ajax_get_products",
					dfrps_security: "<?php echo $nonce; ?>",
					postid: <?php echo get_the_ID(); ?>,
					page: page,
					context: div
				}
	
			}).done(function( html ) {

				// Display product list.
				$(element).fadeOut(100).hide().fadeIn(100).html(html);

			});

			e.preventDefault();
		});

		/**
		 * Add a Single Product.
		 */
		$(".datafeedr-productset_admin").on("click", ".dfrps_add_individual_product a", function(e) {

			// Set variables.
			var pid = $(this).attr("product-id");
			var div = $(this).closest('.dfrps_meta_box').attr("id");
			var product_block = "#product_"+pid+"_"+div;
			var cloned_product_block = $(product_block).clone();

			// Display "loading..." image.	
			$(product_block + " .action_links .dfrps_add_individual_product").html('<img src="<?php echo plugins_url( "images/icons/loading.gif", dirname(__FILE__) ); ?>" />');

			$.ajax({
				type: "POST",
				url: "<?php echo admin_url( 'admin-ajax.php' ); ?>",
				data: { 
					action: "dfrps_ajax_add_individual_product",
					dfrps_security: "<?php echo $nonce; ?>",
					postid: <?php echo get_the_ID(); ?>,
					pid: pid 
				}
	
			}).done(function( html ) {
	
				// Flash "Single Products" tab
				dfrpsHighlightFadeTab("#tab_included");
	
				// Update counters
				dfrpsAddOne("#tab_included .count");
				dfrpsAddOne("#div_dfrps_tab_included .dfrps_pager_end");
				dfrpsAddOne("#div_dfrps_tab_included .dfrps_relevant_results");
	
				// Remove "add" icon and change to "checkmark".
				$(product_block + " .action_links .dfrps_add_individual_product").remove();
				$(product_block + " .action_links").prepend(html);
	
				// Push the cloned div to the Included page product list.
				$(cloned_product_block).attr("id", "product_"+pid+"_div_dfrps_tab_included");
				$("#div_dfrps_tab_included .product_list").prepend(cloned_product_block);
	
				// Remove message about not having added any individual products.
				$("#div_dfrps_tab_included .dfrps_alert").remove();
	
			});

			e.preventDefault();
		});

		/**
		 * Block a Product.
		 */
		$(".datafeedr-productset_admin").on("click", ".dfrps_block_individual_product a", function(e) {

			// Set variables.
			var pid = $(this).attr("product-id");
			var div = $(this).closest('.dfrps_meta_box').attr("id");
			var product_block = "#product_"+pid+"_"+div;
			var cloned_product_block = $(product_block).clone();

			// Display "loading..." image.	
			$(product_block + " .action_links .dfrps_block_individual_product").html('<img src="<?php echo plugins_url( "images/icons/loading.gif", dirname(__FILE__) ); ?>" />');

			// Change color of the border of the table.
			$(product_block + " table").css("border-color", "#d9534f");

			$.ajax({
				type: "POST",
				url: "<?php echo admin_url( 'admin-ajax.php' ); ?>",
				data: { 
					action: "dfrps_ajax_block_individual_product",
					dfrps_security: "<?php echo $nonce; ?>",
					postid: <?php echo get_the_ID(); ?>,
					pid: pid 
				}
	
			}).done(function( html ) {

				// Flash "Blocked Products" tab
				dfrpsHighlightFadeTab("#tab_blocked");
	
				// Update counters
				dfrpsAddOne("#tab_blocked .count");
				dfrpsAddOne("#div_dfrps_tab_blocked .dfrps_pager_end");
				dfrpsAddOne("#div_dfrps_tab_blocked .dfrps_relevant_results");
				dfrpsRemoveOne("#tab_saved_search .count" );
				dfrpsRemoveOne("#div_dfrps_tab_search .dfrps_pager_end");
				dfrpsRemoveOne("#div_dfrps_tab_search .dfrps_relevant_results");
				dfrpsRemoveOne("#div_dfrps_tab_saved_search .dfrps_pager_end");
				dfrpsRemoveOne("#div_dfrps_tab_saved_search .dfrps_relevant_results");
	
				// Change style of the border of the table slide up this product block.
				$(product_block + " table").css("border-style", "dotted");
				$(product_block).slideUp();
	
				// Remove "add" icon and change to "checkmark".
				$(product_block + " .action_links .dfrps_add_individual_product").remove();
				$(product_block + " .action_links").prepend(html);
	
				// Slide up on Search Results & Saved Search page, too.
				$("#product_"+pid+"_div_dfrps_tab_search").slideUp();
				$("#product_"+pid+"_div_dfrps_tab_saved_search").slideUp();
	
				// Push the cloned div to the Blocked Products list.
				$(cloned_product_block).attr("id", "product_"+pid+"_div_dfrps_tab_blocked");
				$("#div_dfrps_tab_blocked .product_list").prepend(cloned_product_block);	
	
				// Remove message about not having added any blocked products.
				$("#div_dfrps_tab_blocked .dfrps_alert").remove();				
	
			});

			e.preventDefault();
		});

		/**
		 * Save search.
		 */
		$(".datafeedr-productset_admin").on("click", "#dfrps_cpt_save_search", function(e) {

			// Set variables
			var num_products = dfrpsGetElementsIntValue( "#div_dfrps_tab_search .inside .dfrps_relevant_results" );
			var cloned_pagination_results_first = $( "#div_dfrps_tab_search_results .dfrps_pagination" ).first().clone();
			var cloned_pagination_results_last = $( "#div_dfrps_tab_search_results .dfrps_pagination" ).last().clone();

			// Disable 'save' button.
			$("#dfrps_cpt_save_search").addClass("button-primary-disabled").val("<?php echo dfrps_helper_js_text('saving'); ?>");

			$.ajax({
				type: "POST",
				url: "<?php echo admin_url( 'admin-ajax.php' ); ?>",
				data: { 
					action: "dfrps_ajax_save_query",
					dfrps_security: "<?php echo $nonce; ?>",
					postid: <?php echo get_the_ID(); ?>,
					num_products: num_products
				}
	
			}).done(function( html ) {
	
				// Flash "Saved Search" tab & update count.
				dfrpsHighlightFadeTab( "#tab_saved_search" );
				$("#tab_saved_search .count").text(num_products);
	
				// Remove any pagination and product list that already exists under Saved Search tab.
				$("#div_dfrps_tab_saved_search .dfrps_pagination").remove();
				$("#div_dfrps_tab_saved_search .product_list").empty();
	
				// Loop thru each product block and at it to the Saved Search tab area.
				$( "#div_dfrps_tab_search_results > .product_list > div" ).each(function() {
					var cloned_product_block = $(this).clone();
					var pid_class = $(this).attr("class").split(' ')[1];
					$(cloned_product_block).attr("id", pid_class + "_div_dfrps_tab_saved_search");
					$("#div_dfrps_tab_saved_search .product_list").append(cloned_product_block);
				});
	
				// show() product_list in case it was hidden.
				$("#div_dfrps_tab_saved_search .product_list").show();
	
				// Add pagination
				$("#div_dfrps_tab_saved_search .product_list").before(cloned_pagination_results_first);
				$("#div_dfrps_tab_saved_search .product_list").after(cloned_pagination_results_last);
	
				// Update [Save Search] button with "Update Saved Search" text.
				$("#dfrps_cpt_save_search").val(html);
	
				// Remove message about not having any saved search.
				$("#div_dfrps_tab_saved_search .dfrps_alert").remove();
			
				// Line-through steps
				$("#dfrps_step_save").addClass( "dfrps_dashboard_step_completed" );	
	
			});

			e.preventDefault();

		});			

		/**
		 * Delete Saved Search
		 */
		 $(".datafeedr-productset_admin").on("click", ".dfrps_delete_saved_search", function (e) {

			$("#div_dfrps_tab_saved_search .dfrps_pagination").fadeOut();
			$("#div_dfrps_tab_saved_search .product_list").fadeOut();			

			$.ajax({
				type: "POST",
				url: "<?php echo admin_url( 'admin-ajax.php' ); ?>",
				data: { 
					action: "dfrps_ajax_delete_saved_search",
					dfrps_security: "<?php echo $nonce; ?>",
					postid: <?php echo get_the_ID(); ?>
				}
			}).done(function(html) {
	
				// Update Saved Search Tab count with 0.
				$("#tab_saved_search .count").text(0);
	
				// Display "success" message.
				$("#div_dfrps_tab_saved_search .inside").prepend('<div class="dfrps_dismissible dfrps_alert dfrps_alert-success">'+html+'</div>');
	
			});
			e.preventDefault();
		});

		/**
		 * Remove product which was added individually.
		 */
		$(".datafeedr-productset_admin").on("click", ".dfrps_remove_individual_product a", function(e) {
			
			// Set variables.
			var pid = $(this).attr("product-id");
			var div = $(this).closest('.dfrps_meta_box').attr("id");
			var product_block = "#product_"+pid+"_"+div;

			// Display "loading..." image.	
			$(product_block + " .action_links .dfrps_remove_individual_product").html('<img src="<?php echo plugins_url( "images/icons/loading.gif", dirname(__FILE__) ); ?>" />');

			// Change color of the border of the table.
			$(product_block + " table").css("border-color", "#d9534f");

			$.ajax({
				type: "POST",
				url: "<?php echo admin_url( 'admin-ajax.php' ); ?>",
				data: { 
					action: "dfrps_ajax_remove_individual_product",
					dfrps_security: "<?php echo $nonce; ?>",
					postid: <?php echo get_the_ID(); ?>,
					pid: pid 
				}
	
			}).done(function( html ) {
	
				// Flash "Single Products" tab
				dfrpsHighlightFadeTab( "#tab_included" );
	
				// Update counters
				dfrpsRemoveOne( "#div_dfrps_tab_included .dfrps_pager_end" );
				dfrpsRemoveOne( "#div_dfrps_tab_included .dfrps_relevant_results" );
				dfrpsRemoveOne( "#tab_included .count" );
	
				// Change style of the border of the table slide up this product block.
				$(product_block + " table").css("border-style", "dotted");
				$(product_block).slideUp();
	
				// Make it "addable" again on the Search tab page.
				$("#product_"+pid+"_div_dfrps_tab_search .action_links .dfrps_product_already_included").remove();
				$("#product_"+pid+"_div_dfrps_tab_search .action_links").prepend('<div class="dfrps_add_individual_product"><a href="#" product-id="'+pid+'" title="<?php echo __("Add this product to this Product Set.", DFRPS_DOMAIN ); ?>"><img src="<?php echo plugins_url( "images/icons/plus.png", dirname(__FILE__) ); ?>" /></a></div>');
			});

			e.preventDefault();
		});

		/**
		 * Unblock blocked product.
		 */
		$(".datafeedr-productset_admin").on("click", ".dfrps_unblock_individual_product a", function(e) {
			
			// Set variables.
			var pid = $(this).attr("product-id");
			var div = $(this).closest('.dfrps_meta_box').attr("id");
			var product_block = "#product_"+pid+"_"+div;

			// Display "loading..." image.	
			$(product_block + " .action_links .dfrps_unblock_individual_product").html('<img src="<?php echo plugins_url( "images/icons/loading.gif", dirname(__FILE__) ); ?>" />');

			// Change color of the border of the table.
			$(product_block + " table").css("border-color", "#d9534f");

			$.ajax({
				type: "POST",
				url: "<?php echo admin_url( 'admin-ajax.php' ); ?>",
				data: { 
					action: "dfrps_ajax_unblock_individual_product",
					dfrps_security: "<?php echo $nonce; ?>",
					postid: <?php echo get_the_ID(); ?>,
					pid: pid 
				}
	
			}).done(function( html ) {

				// Flash "Single Products" tab
				dfrpsHighlightFadeTab( "#tab_blocked" );
	
				// Update counters.
				dfrpsRemoveOne( "#div_dfrps_tab_blocked .dfrps_pager_end" );
				dfrpsRemoveOne( "#div_dfrps_tab_blocked .dfrps_relevant_results" );
				dfrpsRemoveOne( "#tab_blocked .count" );

				// Change style of the border of the table slide up this product block.
				$(product_block + " table").css("border-style", "dotted");
				$(product_block).slideUp();

			});

			e.preventDefault();
		});

		$(".datafeedr-productset_admin").on("click", ".selectit input", function(e) {
			save_category_selection($(this).closest('.categorychecklist'));
		});

		// see wp-includes/js/wp-lists.js
		$(".datafeedr-productset_admin .categorychecklist").on('wpListAddEnd', function() {
			save_category_selection($(this));
		});

		$(".datafeedr-productset_admin").on('click', '.dfrps_cpt_picker', function() {

			$("#dfrps_cpt_picker_metabox input").attr("disabled", true);
			$(".dfrps_category_metabox").slideUp().fadeOut();

			var id = $(this).attr("id");
			
			var term_ids = $("#"+id+"_chooser .categorychecklist input:checked").map(function() {
				return $(this).val();
			}).get();
			
			var type = $("#dfrps_cpt_picker_metabox input:checked").val();

			if ($(this).is(':checked')) {
				$("#"+id+"_chooser").slideDown(1000);
			}
				
			$.ajax({
				type: "POST",
				url: "<?php echo admin_url( 'admin-ajax.php' ); ?>",
				data: {
					action: "dfrps_ajax_update_import_into",
					dfrps_security: "<?php echo $nonce; ?>",
					postid: <?php echo get_the_ID(); ?>,
					type: type,
					term_ids: term_ids
				}
			}).done(function( html ) {	
				$("#dfrps_cpt_picker_metabox input").removeAttr("disabled");

			});
		});

		$(".datafeedr-productset_admin").on('click', '#dfrps_set_next_update_time_to_now', function(e) {
			$(this).attr("disabled", true).replaceWith("<div class='dfrps_loading'></div>");
			$.ajax({
				type: "POST",
				url: "<?php echo admin_url( 'admin-ajax.php' ); ?>",
				data: {
					action: "dfrps_ajax_update_now",
					dfrps_security: "<?php echo $nonce; ?>",
					postid: <?php echo get_the_ID(); ?>
				}
			}).done(function(html) {
				$("#dfrps_dynamic_dashboard_area").slideUp(400, function() {
					$("#dfrps_dynamic_dashboard_area").html(html).slideDown(400);
				}).delay(500);
			
			});

			e.preventDefault();
		});	

	}; // function dfrps_init() {
	
	if (window.addEventListener) {
		window.addEventListener('load', dfrps_init, false); 
	} else if (window.attachEvent)  {
		window.attachEvent('onload', dfrps_init);
	}

})(jQuery); // (function($) {
		
</script>