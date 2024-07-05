=== Datafeedr Product Sets ===

Contributors: datafeedr.com
Tags: import csv, import datafeed, datafeed, import affiliate products
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Requires PHP: 7.4
Requires at least: 3.8
Tested up to: 6.6-RC2
Stable tag: 1.3.23

Build sets of products to import into your website.

== Description ==

**NOTE:** The *Datafeedr Product Sets* plugin requires the [Datafeedr API plugin](http://wordpress.org/plugins/datafeedr-api/).

**What is a Product Set?**

A Product Set is a collection of related products. Once you create a Product Set, the products in that set will be imported into your website (via an importer plugin). The Product Set is also responsible for updating those imported products with the newest information at an interval you choose.

The *Datafeedr Product Sets* plugin currently integrates with the following plugins:

* [Datafeedr API](https://wordpress.org/plugins/datafeedr-api/)
* [Datafeedr WooCommerce Importer](https://wordpress.org/plugins/datafeedr-woocommerce-importer/)

**How does it work?**

1. Create a new Product Set by performing a product search for specific keywords.  In this example lets use "rock climbing shoes" as our keywords.

1. The Datafeedr Product Sets plugin connects to the Datafeedr API and makes an API request querying 250 million affiliate products in the Datafeedr database for the keywords "rock climbing shoes".

1. The Datafeedr API returns the products in the database that match your search keywords.

1. At this point, you have 2 choices: You can "save" your search (so that all products returned are added to your Product Set) or you can pick and choose specific products to add to your Product Set.

1. After your Product Set has some products in it, you choose what WordPress Post Type and Category to import the Product Set into. For example, you could import all of the rock climbing shoes into your WooCommerce store in the "Climbing Shoes" product category.

1. Within a few seconds the Product Set will attempt to import those products into your WooCommerce product category. It will do so by getting all of the products in the Product Set and passing them to an importer plugin (in this case the [Datafeedr WooCommerce Importer plugin](http://wordpress.org/plugins/datafeedr-woocommerce-importer/)).

1. After a few minutes (depending on how many products are in your set and your update settings) your "Climbing Shoes" product category will be filled with products from your Product Set.

1. Lastly, at an interval you configure, the Product Set will trigger a product update. At this time, products no longer available via the Datafeedr API will be removed from your WooCommerce store, all product information will be updated and any new products that match your "saved search" will be added to your store.

The *Datafeedr Product Sets* plugin requires at least one importer plugin to import products from a Product Set into your blog.

We currently have one importer which imports products from your Product Sets into your WooCommerce store: [Datafeedr WooCommerce Importer plugin](http://wordpress.org/plugins/datafeedr-woocommerce-importer/). Additional importers will be developed over the coming months. Custom importers may also be written. Product Sets can be imported into one or more WordPress Post Types.

**Requirements**

* PHP 7.4 or greater
* MySQL version 5.6 or greater
* [WordPress memory limit of 256 MB or greater](https://codex.wordpress.org/Editing_wp-config.php#Increasing_memory_allocated_to_PHP)
* PHP's `CURL` enabled
* WordPress Cron enabled
* [Datafeedr WooCommerce Importer Plugin](http://wordpress.org/plugins/datafeedr-woocommerce-importer/)
* [HTTPS support](https://wordpress.org/news/2016/12/moving-toward-ssl/)

== Installation ==

This section describes how to install and configure the plugin:

1. Upload the `datafeedr-product-sets` folder to the `/wp-content/plugins/` directory.
1. Activate the *Datafeedr Product Sets* plugin through the 'Plugins' menu in WordPress.
1. Configure your Product Sets settings here: WordPress Admin Area > Product Sets > Configuration
1. Add a Product Set here: WordPress Admin Area > Product Sets > Add Product Set

== Frequently Asked Questions ==

= Where can I get help?  =

Our support area can be found here: [https://datafeedrapi.helpscoutdocs.com/](https://datafeedrapi.helpscoutdocs.com/). This support area is open to everyone.

== Screenshots ==

1. Product search form
2. Product Set Dashboard
3. List of Product Sets and their update status
4. Configuration: Search Settings
5. Configuration: Update Settings
6. Configuration: Advanced Update Settings

== Changelog ==

= 1.3.23 - 2024/07/05 =
* Updated "tested up to" value

= 1.3.22 - 2023/11/10 =
* Updated "tested up to" value

= 1.3.21 - 2023/05/16 =
* Added new "Product Sets" column to Products table (WordPress Admin Area > Products) to display all Product Sets for each Product.
* Configured Product Set's Dashboard to display [Bump] button during the first 60 seconds following a Product Set update completion.

= 1.3.20 - 2023/05/09 =
* Disable the "Delete permanently" option for Product Sets in the Trash for less than one week. Can be bypassed using the `dfrps_bypass_premature_delete_check` filter hook and returning `true`.

= 1.3.19 - 2023/04/26 =
* Added the product ID next to the product name in the search results on a Product Set create page.
* Added link to `direct_url` field.
* Prevent `image` field from expanding beyond the width of the browser.

= 1.3.18 - 2023/03/29 =
* Fixed trailing comma bug

= 1.3.17 - 2023/02/23 =
* Fixed warning for undefined array key.

= 1.3.16 - 2023/02/21 =
* Fixed Product Set "Last Update" metabox displaying inaccurate data when a Product Set has never updated or is in the process of updating for the first time.

= 1.3.15 - 2023/02/16 =
* Added Custom Update Schedule for Product Sets.
* Formatted `require_once()` calls differently.
* Added `dfrps_get_default_update_time()` function.
* Update parameter requirements for `dfrps_get_next_update_time()`

= 1.3.14 - 2023/02/08 =
* Fixed spelling and whitespace.

= 1.3.13 - 2022/10/28 =
* Updated readme

= 1.3.12 - 2022/03/02 =
* Added a minimum WordPress version check to the `register_activation_hook`
* Added a Multisite check to the `register_activation_hook` to ensure that plugin can only be activated at Site-Level, not Network-Level
* Replaced calls to `Dfrapi_Env::api_keys_exist()` with `dfrapi_datafeedr_api_keys_exist()`
* Added "Requires PHP: 7.4" to plugin headers

= 1.3.11 - 2022/02/10 =
* Updated text domain to datafeedr-product-sets

= 1.3.10 - 2022/02/02 =
* Added `if ( $thumbnail_id > 0 )` check for image importing.

= 1.3.9 - 2022/01/28 =
* Fixed some undefined notices in product search form.

= 1.3.8 - 2021/12/06 =
* Added alert to display [Query Complexity Score](https://datafeedrapi.helpscoutdocs.com/article/255-calculating-api-query-complexity-score) on Product Set edit page.

= 1.3.7 - 2021/11/16 =
* Fixed bug in `dfrps_do_import_product_thumbnail()` function.

= 1.3.6 - 2021/10/25 =
* Added new option to prevent Product Set updates from being disabled with encountering "No merchants selected" errors. [More info](https://datafeedrapi.helpscoutdocs.com/article/253-no-merchants-selected-error)
* Fixed "download importer" link.

= 1.3.5 - 2021/08/09 =
* Fixed Loopback help link and removed another invalid help link.

= 1.3.3 - 2021/03/16 =
* Changed the image importer function to use `dfrapi_schedule_single_action()` instead of `dfrapi_schedule_async_action()` so that the "Scheduled Date" column is set to something more meaningful than "0000-00-00 00:00:00"

= 1.3.2 - 2021/03/03 =
* Added support the new image import functionality coming soon in the Datafeedr API plugin.

= 1.3.1 - 2021/02/16 =
* Added logging to ActionScheduler by throwing an Exception if image import fails. Exception is caught and displayed by AS.
* Reduced timeout time from 20 to 10 when attempting to import an image.

= 1.3.0 - 2021/02/15 =
* REQUIRES Datafeedr API plugin version 1.2.0 (or greater).
* Fixes issue with latest Yoast SEO plugin version 15.8.
* Transfers image importing responsibility to ActionScheduler library.

= 1.2.53 - 2021/02/12 =
* Added more useful errors to `dfrps_do_import_product_thumbnail()` function.

= 1.2.52 - 2021/02/10 =
* Added `dfrps_error_log` function and added conditional check of `dfrps_log_errors` filter before logging.

= 1.2.51 - 2021/02/08 =
* Added Site Health Info.

= 1.2.50 - 2021/01/22 =
* Added `dfrps_get_product_field()` helper function.

= 1.2.49 - 2021/01/18 =
* Updated code which displays the price.

= 1.2.48 - 2020/12/29 =
* Fixed jQuery .click() handlers.

= 1.2.47 - 2020/12/10 =
* Fixed some undefined index notices.

= 1.2.46 - 2020/12/08 =
* Configured Product Set updates to become disabled if a Product Set returned a "No merchants selected" error message.

= 1.2.45 - 2020/12/01 =
* Fixed "PHP Notice:  Undefined index: source_id"

= 1.2.44 - 2020/10/12 =
* Fixed admin notice styling.

= 1.2.43 - 2020/09/30 =
* Updated readme

= 1.2.42 - 2020/07/27 =
* Fixed meta box titles.

= 1.2.41 - 2020/05/07 =
* Added new hook just before Product Set query is handled by Datafeedr API.

= 1.2.40 - 2020/03/11 =
* Updated readme to support WordPress 5.4.

= 1.2.39 - 2020/02/24 =
* Added more checks to prevent the same product from being imported more than one time (race conditions... fun!)

= 1.2.38 - 2019/11/12 =
* Updated readme

= 1.2.37 - 2019/10/15 =
* Fixed some CSS styles.

= 1.2.36 - 2019/05/21 =
* Added `dfrps` as the meta_value to the new `_owner_datafeedr` meta_key for images imported by the Datafeedr Product Sets plugin.

= 1.2.35 - 2019/05/06 =
* Updated readme.

= 1.2.34 - 2019/04/11 =
* Fixed formatting in bulk image importer.

= 1.2.33 - 2019/02/27 =
* Added 2 new Bulk Actions for Product Sets: Bump and Bump (with priority)

= 1.2.32 - 2019/02/19 =
* Updated readme

= 1.2.31 - 2018/12/07 =
* Updated readme

= 1.2.30 - 2018/05/07 =
* Fixed bug where unserializing the $product array in update class failed.

= 1.2.29 - 2018/04/09 =
* Fixed bug related to bulk import tool unable to move forward if an image URL was missing.

= 1.2.28 - 2018/03/13 =
* Updated readme.

= 1.2.27 - 2018/01/18 =
* Extracted a couple conditional statements to their own functions.

= 1.2.26 - 2018/01/17 =
* Updated Tested up to and added README.md.

= 1.2.25 - 2018/01/10 =
* Fixed bug related to new class.

= 1.2.24 - 2018/01/10 =
* Added new `Datafeedr_Plugin_Dependency` class.

= 1.2.23 - 2018/01/09 =
* Tweaked return values of `dfrps_import_post_thumbnail()`.
* Made reporting of `dfrps_ajax_batch_import_images()` more verbose.
* Tweaked bulk import result styling.

= 1.2.22 - 2018/01/08 =
* Added `dfrps_product()` helper function.
* Added `dfrps_do_import_product_thumbnail()` helper function.
* Added `dfrps_featured_image_url()` helper function.
* Added `dfrps_import_post_thumbnail()` helper function.
* Using new `dfrps_import_post_thumbnail()` function to import images if user is using newest version of Datafeedr API plugin.

= 1.2.21 - 2018/01/02 =
* Rewrote `dfrps_ajax_test_loopbacks()` function.

= 1.2.20 - 2017/12/11 =
* Added `dfrps_image_imported_successfully` action which gets called when and image is imported successfully.

= 1.2.19 - 2017/12/01 =
* Added `has_post_thumbnail` check to `dfrps_import_image()` function before initializing `Dfrps_Image_Importer`.

= 1.2.18 - 2017/11/30 =
* Changed Chrome user-agent to Firefox to deal with .webp issues during image import.
* Added error logging in image importing class.

= 1.2.17 - 2017/11/13 =
* Set `show_in_nav_menus` to false for Product Set custom post type.

= 1.2.16 - 2017/10/18 =
* Added 2 more mime types to the `convert_mime_to_ext()` function.

= 1.2.15 - 2017/10/06 =
* Removed the `preg_match()` from the `media_sideload_image()` method to force the importer script to rely solely on a `wp_remote_get()` call to get the image 'content-type'.
* Added `'image/webp' => 'webp'` to the `convert_mime_to_ext()` to support the .webp extension. Still requires this plugin to work: https://wordpress.org/plugins/wp-webp/

= 1.2.14 - 2017/08/02 =
* Added new admin notice if `DISABLE_WP_CRON` is set to anything other than `false` in the wp-config.php file.

= 1.2.13 - 2017/06/16 =
* Added additional column info for the "updated" column in the "dfrps_temp_product_data" table.

= 1.2.12 - 2017/05/03 =
* Fixed issue related to locking tables released in v1.2.11.

= 1.2.11 - 2017/05/03 =
* Updated the `dfrps_import_image()` function to prevent it from trying to import images for post types not supported by the Product Set plugin.
* Added `wp_defer_term_counting(true/false)` before and after `wp_set_object_terms()` in the `dfrps_add_term_ids_to_post()` function. This speeds up phase1 of the Product Set updater by about 4x.
* Added table locking to `dfrps_get_existing_post()` function to help offset any duplicate products being imported.
* Added new `dfrps_doing_update` filter to `dfrps_get_product_set_to_update()` to bypass the "doing update" check to speed up Product Set updates.

= 1.2.10 - 2016/05/24 =
* Added affiliate ID in impression URLs in the search results on Product Set pages. (#13237)

= 1.2.9 - 2015/12/11 =
* Removed redundant return of `$image_url` from the `set_image_url()` function.

= 1.2.8 - 2015/11/10 =
* Fixed image importer script to skip products which have no `image` field.

= 1.2.7 - 2015/10/28 =
* Fixed CSS on Product Set add/edit page to handle the change of h3 tags to h2 for metabox titles.
* Added priority arg to admin_body_class filter in ctp class.

= 1.2.6 - 2015/09/21 =
* Added code to updater to prevent it from importing any product without a "url" field. This is also related to the fixed "quirk" in the DFRAPI plugin (version 1.0.29).
* Fixed spelling of "Request".
* Added code to show "_wc_url" field for unavailable products. Related to above "quirk".
* Added new `dfrps_get_post_obj_by_postmeta()` function for things to come. ;)

= 1.2.5 - 2015/05/04 =
* Added cache bypassing functions to the Dfrps_Image_Importer class.

= 1.2.4 - 2015/04/29 =
* Added new 'uid' column to 'dfrps_temp_product_data' table. (#10866)
* Added uniqid() to 'dfrps_temp_product_data' table to avoid race conditions during updates. (#10866)
* Added cache bypassing functions when getting transients. (#10866)
* Added code to suspend cache addition and invalidation during import/update. (#10910)
* Added call to array_unique() in the dfrps_get_all_post_ids_by_set_id() function to avoid post ID duplication.

= 1.2.3 - 2015/04/15 =
* Renamed temp product table from 'dfrps_product_data' to 'dfrps_temp_product_data'.
* Added function to DROP dfrps_temp_product_data table after update is complete.
* DROP'd 'dfrps_product_data' table manually as it would have been stranded.
* Added new action 'dfrps_update_reset' to dfrps_reset_update() function.

= 1.2.2 - 2015/04/06 =
* Typecasted '_dfrps_product_check_image' as (int) value so it could be compared to 0.
* Added new icon to admin menu.
* Added new 128x128 and 256x256 plugin icons.
* Fixed broken URL to admin menu icons that have existed since the beginning of time.

= 1.2.1 - 2015/03/23 =
* Changed varchar(255) to varchar(50) in Update/Create Table statement to avoid "Specified key was too long; max key length is 767 bytes for query" errors (#10701).
* Fixed some grammar in the "Fix Missing Images" message.
* Replace spaces in Image URLs with "%20". This prevents images with spaces in the URL from failing to be imported.
* Fixed bug where default options were not being set upon plugin being activated which caused products to not be returned in searches.

= 1.2.0 - 2015/03/16 =
* Fixed bug where configuration settings were being saved at the wrong time.
* Added a filter to filter Product Sets by what CPT they import into.
* Fixed formatting (removed p tags) in admin notices.
* Added new parameter to cron SQL to check filter Product Sets by only active CPTs (ie. 'product').
* Readied code for adding additional importers to be added (#9167)

= 1.1.14 =
* Added a check/fix for mime types that include the type of encoding. Example: "image/jpeg;charset=UTF-8" 

= 1.1.13 =
* Fixed a hard-coded table name in bulk image importer SQL statement.

= 1.1.12 =
* Added back the 'dfrps_invalid_image' action hook which was inadvertently removed in version 1.1.10.

= 1.1.11 =
* Fixed a bug with the bulk image importer where it would import images for products which were having their '_dfrps_product_set_id' value deleted because they were being moved to the Trash. Now the bulk image importer only processes images for products where '_dfrps_product_set_id' does exist.

= 1.1.10 =
* Complete rewrite of image importer script. Now, allow_url_fopen is NOT required! :)
* Fixed bug with links generated under the bulk image importer not working for WordPress installed as a sub-directory.

= 1.1.9 =
* Fixed bug where extra postmeta data was being saved for non-productset post types.

= 1.1.8 =
* Changed most occurrences of unserialize() to maybe_unserialize() to deal with changes to get_metadata() in WP 4.1.0.
* Removed 2nd argument from dfrps_upload_images() to deal with changes to deal with do_action_ref_array() introducing the $this parameter in 4.1.0.
* Fixed bug where large Product Sets made up of lots of individually added products were not importing or updating all products in the Product Set. This only affected individually added products in Product Sets with over 100 products.

= 1.1.7 =
* Replaced get_the_post_thumbnail() with get_post_thumbnail_id() in image processing script.

= 1.1.6 =
* Added plugin icon for WordPress 4.0+.
* Fixed dashed tab styling for product sets.

= 1.1.5 =
* Fixed undefined 'price' index in html.php file.

= 1.1.4 =
* Changed dfrps_product_data's "data" column from TEXT to LONGTEXT.

= 1.1.3 =
* Added 'dfrps_set_update_complete' action when update is complete.

= 1.1.2 =
* Changed add_option to update_option in upgrade.php file.
* Added a new action to image.php file: "dfrps_invalid_image"

= 1.1.1 =
* Fixed issue with the sale price not displaying on 'single products' tab after set has updated. (#9210)

= 1.1.0 =
* Modified the 'Updater' class. Products are now inserted into a temporary table directly from the API query. Then the updater iterates over the temporary table until all products are processed and imported into WP. This change will make the update process slightly longer however it will prevent wasted API requests. It will also work to prevent import timeouts by separating the API Request and the Import into 2 different stages.
* Added upgrade.php file to track upgrades between versions.

= 1.0.10 =
* Fixed code if $links in ajax.php was not set.
* Added 'Searching X products...' to loading area when searching for products.

= 1.0.9 =
* Set update_phase to 0 when Product Set is moved to Trash. (#8705)
* Fixed undefined indexes.

= 1.0.8 =
* Updated 'tested up to' tag.

= 1.0.7 =
* Modified comment text.
* Fixed issue in dfrps_get_existing_post() related to 32-bit systems. Changed %d to %s.

= 1.0.6 =
* Forgot to update version in main plugin file.

= 1.0.5 =
* Tweaked search form css.
* Added help text to help tab for new Tools page.
* Changed default product update settings.

= 1.0.4 =
* Fixed "Requires at least" and "Tested up to" fields of the readme.txt file. Oops!
* Changes to a lot of help text on all pages.
* Readded Javascript regarding input#title which was accidentally removed in version 1.0.2.
* Fixed undefined indexes.

= 1.0.3 =
* Fixed commit.

= 1.0.2 =
* Changed contents of 'product set updates disabled' email.
* Converted emails sent from plain text to HTML.
* Fixed undefined indexes.
* Added filter to $postmeta in image.php.
* Removed screen_icon() from config page.
* Removed filesize check from functions/image.php because we already make sure it's an image with getimagesize().
* Added check in cron to see if at least 1 network and 1 merchant is selected before running update.
* Added new "Tools" page to perform different actions such as reset cron and bulk import images.
* Replaced Javascript on CPT pages to prevent conflict on onReady with other broken plugins.

= 1.0.1 =
* Fixed undefined indexes.
* Added do_action() to the beginning and end of each phase in the Update and Delete class.

= 1.0.0 =
* Updated "Contributors" and "Author" fields to match WP.org username.

= 0.9.6 =
* Fixed more undefined indexes.

= 0.9.5 =
* Fixed more undefined indexes.
* Updated plugin information.

= 0.9.4 =
* Added a nag if a default CPT had not been selected.
* Fixed undefined indexes.

= 0.9.3 =
* Initial release.

== Upgrade Notice ==

*None*

