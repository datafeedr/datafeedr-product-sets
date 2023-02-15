<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Dfrps_Tools' ) ) {

	/**
	 * Configuration page.
	 */
	class Dfrps_Tools {

		private $page = 'dfrps_tools';
		private $key;

		public function __construct() {
			$this->key = 'dfrps_tools';
			add_action( 'admin_init', array( &$this, 'register_settings' ) );
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
			add_action( 'admin_notices', array( $this, 'admin_notice' ) );
		}

		function admin_menu() {
			add_submenu_page(
				'dfrps',
				__( 'Tools &#8212; Datafeedr Product Sets', 'datafeedr-product-sets' ),
				__( 'Tools', 'datafeedr-product-sets' ),
				'manage_options', 
				$this->key,
				array( $this, 'output' ) 
			);
		}

		function admin_notice() {
			if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] == true && isset( $_GET['page'] ) && $this->key == $_GET['page'] ) {
				echo '<div class="updated"><p>';
				_e( 'Updated!', 'datafeedr-product-sets' );
				echo '</p></div>';
			}
		}

		function output() {
			echo '<div class="wrap" id="' . $this->key . '">';
			echo '<h2>' . __( 'Tools &#8212; Datafeedr Product Sets', 'datafeedr-product-sets' ) . '</h2>';
			?>

			<script>
			jQuery(function($) {
				$('#dfrps_test_loopbacks').on('click',function(e) {
					$("#dfrps_test_loopbacks_result").hide();
					$("#dfrps_test_loopbacks").text('<?php _e("Testing...", 'datafeedr-product-sets'); ?>').addClass('button-disabled');
					$.ajax({
						type: "POST",
						url: "<?php echo admin_url( 'admin-ajax.php' ); ?>",
						data: {
							action: "dfrps_ajax_test_loopbacks",
							dfrps_security: "<?php echo wp_create_nonce( 'dfrps_ajax_nonce' ); ?>"
						}
					}).done(function(html) {
						$("#dfrps_test_loopbacks").text('<?php _e("Test Loopbacks", 'datafeedr-product-sets'); ?>').removeClass('button-disabled');
						$("#dfrps_test_loopbacks_result").show().html(html);

					});
					e.preventDefault();
				});

				$('#dfrps_reset_cron').on('click',function(e) {
					$("#dfrps_reset_cron_result").hide();
					$("#dfrps_reset_cron").text('<?php _e("Resetting...", 'datafeedr-product-sets'); ?>').addClass('button-disabled');
					$.ajax({
						type: "POST",
						url: "<?php echo admin_url( 'admin-ajax.php' ); ?>",
						data: {
							action: "dfrps_ajax_reset_cron",
							dfrps_security: "<?php echo wp_create_nonce( 'dfrps_ajax_nonce' ); ?>"
						}
					}).done(function(html) {
						$("#dfrps_reset_cron").text('<?php _e("Reset Cron", 'datafeedr-product-sets'); ?>').removeClass('button-disabled');
						$("#dfrps_reset_cron_result").show().html(html);

					});
					e.preventDefault();
				});

				$('#dfrps_fix_missing_images').on('click',function(e) {
					$("#dfrps_fix_missing_images_result").hide();
					$("#dfrps_fix_missing_images").text('<?php _e("Processing...", 'datafeedr-product-sets'); ?>').addClass('button-disabled');
					$.ajax({
						type: "POST",
						url: "<?php echo admin_url( 'admin-ajax.php' ); ?>",
						data: {
							action: "dfrps_ajax_fix_missing_images",
							dfrps_security: "<?php echo wp_create_nonce( 'dfrps_ajax_nonce' ); ?>"
						}
					}).done(function(html) {
						$("#dfrps_fix_missing_images").text('<?php _e("Fix Missing Images", 'datafeedr-product-sets'); ?>').removeClass('button-disabled');
						$("#dfrps_fix_missing_images_result").show().html(html);

					});
					e.preventDefault();
				});

				$('#dfrps_start_batch_image_import').on('click',function(e) {
					$("#dfrps_start_batch_image_import_result").show().html('<div><?php _e("Starting...", 'datafeedr-product-sets'); ?></div>');
					$.ajax({
						type: "POST",
						url: "<?php echo admin_url( 'admin-ajax.php' ); ?>",
						data: {
							action: "dfrps_ajax_start_batch_image_import",
							dfrps_security: "<?php echo wp_create_nonce( 'dfrps_ajax_nonce' ); ?>"
						}
					}).done(function(html) {
						$("#dfrps_stop_batch_image_import").show().html('<?php _e("Stop", 'datafeedr-product-sets'); ?>');
						reload_batch_import();
					});
					e.preventDefault();
				});

				$('#dfrps_stop_batch_image_import').on('click',function(e) {
					$("#dfrps_stop_batch_image_import").text('<?php _e("Stopping...", 'datafeedr-product-sets'); ?>').addClass('button-disabled');
					$.ajax({
						type: "POST",
						url: "<?php echo admin_url( 'admin-ajax.php' ); ?>",
						data: {
							action: "dfrps_ajax_stop_batch_image_import",
							dfrps_security: "<?php echo wp_create_nonce( 'dfrps_ajax_nonce' ); ?>"
						}
					}).done(function(html) {
						$("#dfrps_stop_batch_image_import").text('<?php _e("Stop", 'datafeedr-product-sets'); ?>').removeClass('button-disabled').hide();
					});
					e.preventDefault();
				});

				var reload_batch_import = function() {
					return $.ajax({
						type: "POST",
						url: "<?php echo admin_url( 'admin-ajax.php' ); ?>",
						cache: false,
						data: {
							action: "dfrps_ajax_batch_import_images",
							dfrps_security: "<?php echo wp_create_nonce( 'dfrps_ajax_nonce' ); ?>"
						},
						success: function(data) {
							$("#dfrps_start_batch_image_import_result").prepend(data);

							if ( data != 'Stopped' && data != 'Complete' ) {
								reload_batch_import();
							}
						}
					});
				};

			}); // jQuery(function($) {
			</script>

			<?php
			settings_fields( $this->page );
			do_settings_sections( $this->page);
			echo '</div>';
		}

		function register_settings() {
			add_settings_section( 'loopback_test', __( 'Test HTTP Loopback', 'datafeedr-product-sets' ), array( &$this, 'section_loopback_test_desc' ), $this->page );
			add_settings_section( 'reset_cron', __( 'Reset Cron', 'datafeedr-product-sets' ), array( &$this, 'section_reset_cron_desc' ), $this->page );
			add_settings_section( 'fix_missing_images', __( 'Fix Missing Images', 'datafeedr-product-sets' ), array( &$this, 'section_fix_missing_images_desc' ), $this->page );
			add_settings_section( 'batch_image_import', __( 'Bulk Image Import', 'datafeedr-product-sets' ), array( &$this, 'section_batch_image_import_desc' ), $this->page );
		}

		function section_loopback_test_desc() { ?>
			<p>
				<?php _e( 'If HTTP Loopbacks are disabled by your web host, Product Sets will not import products into your site. Click the <strong>[Test Loopbacks]</strong> button below to determine if loopbacks are enabled or disabled on your server. ', 'datafeedr-product-sets' ); ?>
				<a href="https://wordpress.org/support/article/loopbacks/" target="_blank"><?php _e( 'Learn more about HTTP Loopbacks', 'datafeedr-product-sets' ); ?></a>.
			</p>
			<p><a href="#" id="dfrps_test_loopbacks" class="button"><?php _e("Test Loopbacks", 'datafeedr-product-sets'); ?></a></p>
			<div id="dfrps_test_loopbacks_result" style="padding: 10px; border: 1px solid silver; display: none; background: #FFF;"></div>
			<hr />
		<?php
		}

		function section_reset_cron_desc() { ?>
			<p><?php _e( 'If your Product Sets have stalled during an update or are past due, click the <strong>[Reset Cron]</strong> button below to attempt to jumpstart the cron again.', 'datafeedr-product-sets' ); ?></p>
			<p><a href="#" id="dfrps_reset_cron" class="button"><?php _e("Reset Cron", 'datafeedr-product-sets'); ?></a></p>
			<div id="dfrps_reset_cron_result" style="padding: 10px; border: 1px solid silver; display: none; background: #FFF;"></div>
			<hr />
		<?php
		}

		function section_fix_missing_images_desc() { ?>
			<p><?php _e( 'If a product has an image but it wasn\'t imported into your site succesfully, click the <strong>[Fix Missing Images]</strong> button to attempt to redownload the missing images the next time those products are displayed on your site.', 'datafeedr-product-sets' ); ?></p>
			<p><a href="#" id="dfrps_fix_missing_images" class="button"><?php _e("Fix Missing Images", 'datafeedr-product-sets'); ?></a></p>
			<div id="dfrps_fix_missing_images_result" style="padding: 10px; border: 1px solid silver; display: none; background: #FFF;"></div>
			<hr />
		<?php
		}

		function section_batch_image_import_desc() { ?>
			<p>
				<?php _e( 'When a product is first displayed on your website, its image is downloaded from the merchant\'s website. ', 'datafeedr-product-sets' ); ?>
				<?php _e( 'If you want to download product images before they are displayed on your website, click the <strong>[Start Image Import]</strong> button to begin importing images for products which do not already have an image.', 'datafeedr-product-sets' ); ?><br />
				<span class="dfrps_warning"><?php _e( 'This process is server intensive. Use with caution!', 'datafeedr-product-sets' ); ?>
			</p>
			<p>
				<a href="#" id="dfrps_start_batch_image_import" class="button"><?php _e("Start Image Import", 'datafeedr-product-sets'); ?></a>
				<a href="#" id="dfrps_stop_batch_image_import" class="button" style="display: none"><?php _e("Stop", 'datafeedr-product-sets'); ?></a>
			</p>
			<ol reversed id="dfrps_start_batch_image_import_result" style="margin-left: 0; padding: 10px; padding-left: 40px; border: 1px solid silver; display: none; background: #FFF;"></ol>
		<?php
		}

		function validate( $input ) {
			return $input;
		}

	} // class Dfrps_Tools

} // class_exists check
