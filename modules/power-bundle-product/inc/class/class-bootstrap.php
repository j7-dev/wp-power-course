<?php
/**
 * Bootstrap
 *
 * @see https://webkul.com/blog/how-to-create-custom-product-type-in-woocommerce/
 */

declare (strict_types = 1);

namespace J7\PowerBundleProduct;

/**
 * Class Bootstrap
 */
final class Bootstrap {
	use \J7\WpUtils\Traits\SingletonTrait;

	/**
	 * Constructor
	 */
	public function __construct() {

		require_once Plugin::$dir . '/inc/class/class-wc-bundle-product.php';

		\add_action( 'woocommerce_loaded', [ $this, 'load_bundle_product_class' ] );

		// 不需要顯示在前端

		// \add_filter( 'product_type_selector', array( $this, 'add_bundle_product_type' ) );

		// \add_filter( 'woocommerce_product_data_tabs', array( $this, 'modify_woocommerce_product_data_tabs' ) );

		// \add_action( 'woocommerce_' . Plugin::PRODUCT_TYPE . '_add_to_cart', array( $this, 'display_add_to_cart_button' ), 30 );

		// DELETE
		// \add_filter( 'woocommerce_product_add_to_cart_text', array( $this, 'add_to_cart_text' ), 10, 2 );
	}

	/**
	 * Load custom product.
	 */
	public function load_bundle_product_class() {
		require_once Plugin::$dir . '/inc/class/class-wc-bundle-product.php';
	}

	/**
	 * Custom product type.
	 *
	 * @param array $types Product types.
	 *
	 * @return array
	 */
	public function add_bundle_product_type( array $types ): array {
		$types[ Plugin::PRODUCT_TYPE ] = \esc_html__( '綑綁銷售商品', 'power-bundle-product' );

		return $types;
	}

	/**
	 * 控制要顯示那些 product data tabs.
	 *
	 * @param array $tabs List of product data tabs.
	 *
	 * @return array $tabs Product data tabs.
	 */
	public function modify_woocommerce_product_data_tabs( array $tabs ): array {
		if ( 'product' === get_post_type() ) {
			// phpcs:disable
			?>
					<script type='text/javascript'>
						document.addEventListener('DOMContentLoaded', () => {
							let optionGroupPricing = document.querySelector('.options_group.pricing');
							!!optionGroupPricing && optionGroupPricing.classList.add('show_if_<?php echo Plugin::PRODUCT_TYPE; ?>');

							let stockManagement = document.querySelector('._manage_stock_field');
							!!stockManagement && stockManagement.classList.add('show_if_<?php echo Plugin::PRODUCT_TYPE; ?>');

							let soldIndividuallyDiv = document.querySelector('.inventory_sold_individually');
							let soldIndividually = document.querySelector('._sold_individually_field');
							!!soldIndividuallyDiv && soldIndividuallyDiv.classList.add('show_if_<?php echo Plugin::PRODUCT_TYPE; ?>');
							!!soldIndividually && soldIndividually.classList.add('show_if_<?php echo Plugin::PRODUCT_TYPE; ?>');

						<?php if ( 'yes' === get_option( 'woocommerce_calc_taxes' ) ) { ?>
								let generalProductData = document.querySelectorAll('#general_product_data > .options_group');
								let taxDiv = !!generalProductData && Array.from(generalProductData).at(-1);
								!!taxDiv && taxDiv.classList.add('show_if_<?php echo Plugin::PRODUCT_TYPE; ?>');
							<?php } ?>
						});
					</script>
				<?php
			// phpcs:enable
		}

		foreach ( $tabs as $key => $val ) {
			$product_tabs = [ 'general', 'attribute', 'shipping', 'linked_product', 'advanced' ];

			if ( ! in_array( $key, $product_tabs ) ) {
				$tabs[ $key ]['class'][] = 'hide_if_' . Plugin::PRODUCT_TYPE;
			}
		}

		// Add your custom product data tabs.
		// $custom_tab = array(
		// 'wkwc_custom' => array(
		// 'label'    => __( 'Custom product settings', 'power-bundle-product' ),
		// 'target'   => 'wkwc_cusotm_product_data_html',
		// 'class'    => array( 'show_if_' . Plugin::PRODUCT_TYPE ),
		// 'priority' => 21,
		// ),
		// );

		return $tabs;
	}


	/**
	 * 顯示結帳按鈕
	 *
	 * @return void
	 */
	public function display_add_to_cart_button(): void {
		\wc_get_template( 'single-product/add-to-cart/simple.php' );
	}

	/**
	 * Add to cart text on the gift card product.
	 * DELETE
	 *
	 * @param string $text Text on add to cart button.
	 * @param object $product Product data.
	 *
	 * @return string $text Text on add to cart button.
	 */
	public function add_to_cart_text( $text, $product ) {
		if ( Plugin::PRODUCT_TYPE === $product->get_type() ) {
			$text = $product->is_purchasable() && $product->is_in_stock() ? __( 'Add to cart', 'power-bundle-product' ) : $text;
		}

		return $text;
	}
}
