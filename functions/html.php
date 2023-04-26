<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * This is responsible for displaying a list of products in the admin section.
 * The list of products could be generated from a search query,
 * a list of manually included products or a list of blocked products.
 */
if ( !function_exists( 'dfrps_html_product_list' ) ) {

	function dfrps_html_product_list( $product, $args=array() ) {

		// Image
        $image = $product['image'] ?? $product['thumbnail'] ?? plugins_url( 'images/icons/no-image.jpg', __DIR__ );

        // Currency
		$currency = $product['currency'] ?? 'USD';

		if ( function_exists( 'dfrapi_get_price' ) ) {
			$regularprice = isset( $product['price'] ) ? dfrapi_get_price( $product['price'], $currency, 'product-set-search-results' ) : '';
			$saleprice    = isset( $product['saleprice'] ) ? dfrapi_get_price( $product['saleprice'], $currency, 'product-set-search-results' ) : '';
		} else {
			$regularprice = isset( $product['price'] ) ? dfrapi_currency_code_to_sign( $currency ) . dfrapi_int_to_price( $product['price'] ) : '';
			$saleprice    = isset( $product['saleprice'] ) ? dfrapi_currency_code_to_sign( $currency ) . dfrapi_int_to_price( $product['saleprice'] ) : '';
		}

		// Product type
		$coupon_networks = get_option( 'dfrapi_coupon_networks' );
		if ( isset( $product['source_id'] ) ) {
			$type = ( array_key_exists( $product['source_id'], $coupon_networks ) ) ? 'coupon' : 'product';
		} else {
			$type = 'product';
		}

		// Has this product already been included?
		$already_included = in_array( $product['_id'], $args['manually_included_ids'] );

		?>
		<div id="product_<?php echo $product['_id']; ?>_<?php echo $args['context']; ?>" class="product_block product_<?php esc_attr_e( $product['_id'] ); ?>">
			<table class="dfrps_product_table type_<?php echo $type; ?>">
				<tr class="product">
					<td class="image" rowspan="2">
						<a href="<?php echo $image; ?>" title="<?php echo __('View image in new browser window.', 'datafeedr-product-sets' ); ?>" target="_blank">
							<img src="<?php echo $image; ?>" alt="Product image" />
						</a>
					</td>
					<td class="name">
						<div>
							<a href="#" class="more_info" title="<?php echo __('View more information about this product.', 'datafeedr-product-sets' ); ?>">
								<?php esc_html_e( $product['name'] ); ?>
							</a>
							<span class="product_id" title="<?php esc_attr_e('Internal Datafeedr Product ID', 'datafeedr-product-sets' ); ?>">(<?php esc_html_e( $product['_id'] ); ?>)</span>
						</div>
					</td>
					<td class="links">
						<div class="action_links">

							<?php if ( $already_included ) : ?>
								<div class="dfrps_product_already_included" title="<?php echo __('This product has already been manually added to this Product Set.', 'datafeedr-product-sets' ); ?>">
									<img src="<?php echo plugins_url( 'images/icons/checkmark.png', __DIR__ ); ?>" />
								</div>
							<?php else : ?>
								<div class="dfrps_add_individual_product">
									<a href="#" product-id="<?php echo $product['_id']; ?>" title="<?php echo __('Add this product to this Product Set.', 'datafeedr-product-sets' ); ?>">
										<img src="<?php echo plugins_url( 'images/icons/plus.png', __DIR__ ); ?>" />
									</a>
								</div>
							<?php endif; ?>

							<div class="dfrps_remove_individual_product">
								<a href="#" product-id="<?php echo $product['_id']; ?>" title="<?php echo __('Remove this product from the individually added list for this Product Set.', 'datafeedr-product-sets' ); ?>">
									<img src="<?php echo plugins_url( 'images/icons/minus.png', __DIR__ ); ?>" />
								</a>
							</div>

							<div class="dfrps_unblock_individual_product">
								<a href="#" product-id="<?php echo $product['_id']; ?>" title="<?php echo __('Unblock this product and allow it to show up in product searches for this Product Set.', 'datafeedr-product-sets' ); ?>">
									<img src="<?php echo plugins_url( "images/icons/unblock.png", __DIR__ ); ?>" />
								</a>
							</div>

							<div class="dfrps_block_individual_product">
								<a href="#" product-id="<?php echo $product['_id']; ?>" title="<?php echo __('Block this product from appearing in searches for this Product Set.', 'datafeedr-product-sets' ); ?>">
									<img src="<?php echo plugins_url( 'images/icons/block.png', __DIR__ ); ?>" />
								</a>
							</div>

						</div>
					</td>
					<td class="product_type" rowspan="3">
						<img src="<?php echo plugins_url( 'images/icons/' . $type . '-label.png', __DIR__ ); ?>" alt="Product type" />
					</td>
				</tr>
				<tr>
					<td class="info" colspan="2">

						<div class="description">
							<?php if ( isset( $product['description'] ) ) : ?>
								<?php esc_html_e ( strip_tags( $product['description'] ) ); ?>
							<?php endif; ?>
						</div>

						<div class="details">
							<div class="network" title="<?php echo __('Network', 'datafeedr-product-sets' ) . ': ' . esc_attr( $product['source'] ); ?>">
								<span class="bullet">&bull;</span>
								<span class="label"><?php esc_html_e( $product['source'] ); ?></span>
							</div>
							<div class="merchant" title="<?php echo __('Merchant', 'datafeedr-product-sets' ) . ': ' . esc_attr( $product['merchant'] ); ?>">
								<span class="bullet">&bull;</span>
								<span class="label"><?php esc_html_e ( substr( $product['merchant'], 0, 25 ) ); ?></span>
							</div>
							<?php if ( isset( $product['brand'] ) ) : ?>
								<div class="brand" title="<?php echo __('Brand', 'datafeedr-product-sets' ) . ': ' . esc_attr( $product['brand'] ); ?>">
									<span class="bullet">&bull;</span>
									<span class="label"><?php esc_html_e( $product['brand'] ); ?></span>
								</div>
							<?php endif; ?>
							<?php if ( isset( $product['price'] ) ) : ?>
                                <div class="price"
                                     title="<?php echo __( 'Price', 'datafeedr-product-sets' ) . ': ' . esc_attr( $regularprice ) . ' ' . $currency; ?>">
                                    <span class="bullet">&bull;</span>
                                    <span class="label"><?php echo $regularprice; ?></span>
                                </div>
							<?php endif; ?>
							<?php if ( isset( $product['saleprice'] ) ) : ?>
								<div class="saleprice"
								     title="<?php echo __( 'Sale Price', 'datafeedr-product-sets' ) . ': ' . esc_attr( $saleprice ) . ' ' . $currency; ?>">
									<span class="bullet">&bull;</span>
									<span class="label"><?php echo $saleprice; ?></span>
								</div>
							<?php endif; ?>
						</div>

					</td>
				</tr>
				<tr>
					<td colspan="3">
						<div class="more_info_row" style="display: none;">
							<table class="product_values">
								<?php echo dfrps_more_info_rows( $product ); ?>
							</table>
							<div class="more_info_note">* <span>Italicized rows</span> indicate field and value was generated by Datafeedr, not merchant.</div>
						</div>
					</td>
				</tr>
			</table>
		</div>
		<?php
	}
}
