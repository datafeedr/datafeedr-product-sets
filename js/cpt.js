jQuery(function($) {

	// Tabs on add/edit Product Set page (Search, Included Products & Excluded Products)
	$("#dfrps_cpt_tabs a").click(function (e) {
		var id = $(this).attr('id');
		$(".nav-tab")
			.removeClass("nav-tab-active")			// Remove "active" class from all tabs.
			.css("background-color", "#E4E4E4");	// Make background the right color.
		$(".dfrps_meta_box").hide();				// Hide all meta boxes. We'll show 1 later.
		$(this)
			.addClass("nav-tab-active")				// Make the clicked tab active.
			.css("background-color", "#F1F1F1");	// Make background the right color.
		$("#div_dfrps_tabs").show(); 				// Don't hide the tabs div.
		$("#div_dfrps_" + id).fadeIn(200);			// Un-hide the selected tab's div.
		e.preventDefault();
	});

	$('#dfrps_search_instructions_toggle').on('click',function(e) {
		$('#dfrps_search_instructions').slideToggle();
		e.preventDefault();
	});

	// More info for a product in search results.
	$(".datafeedr-productset_admin").on("click", ".more_info" ,function(e) {
		var table = $(this).closest(".product_block").attr("id");
		var table_id = "#" + table + " .more_info_row";
		$(table_id).slideToggle();
		e.preventDefault();
	});

	$(".datafeedr-productset_admin").on("click", "#dfrps_view_raw_query" ,function(e) {
		$("#dfrps_raw_api_query").slideToggle();
		e.preventDefault();
	});

	function dfrpsStyleUpdatingRow() {
		var row =  $(".wp-list-table").find(".dfrps_currently_updating").closest("tr").attr("id");
		if ( row !== undefined ) {
			$("tr#"+row).addClass("dfrps_updating_row");
		}
	}
	dfrpsStyleUpdatingRow();	

}); // jQuery(function($) {




       