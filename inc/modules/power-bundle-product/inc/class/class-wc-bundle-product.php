<?php
/**
 * Define custom product type.
 *
 * @package WooCommerce Custom Product Type
 */

declare ( strict_types=1 );

namespace J7\PowerBundleProduct;

if ( ! class_exists( 'BundleProduct' ) ) {
	/**
	 * Custom Product class.
	 */
	final class BundleProduct extends \WC_Product {

		/**
		 * 商品類型
		 *
		 * @var string
		 */
		public $product_type = Plugin::PRODUCT_TYPE;

		public const INCLUDE_PRODUCT_IDS_META_KEY = 'pbp_product_ids'; // 綑綁商品裡面包含的商品 ids

		const LINK_TO_BUNDLE_IDS_META_KEY = 'pbp_bundle_ids';          // 此商品連結到哪個 bundle product ids

		/**
		 * Constructor of this class.
		 *
		 * @param object $product product.
		 */
		public function __construct( $product = 0 ) {
			/**
			 * @var \WC_Product $this
			 */

			$this->supports[] = 'ajax_add_to_cart';

			parent::__construct( $product );
		}

		/**
		 * Return the product type.
		 *
		 * @return string
		 */
		public function get_type(): string {
			return Plugin::PRODUCT_TYPE;
		}

		/**
		 * 是否為 bundle_product
		 *
		 * @param \WC_Product|int $product product.
		 *
		 * @return bool
		 */
		public static function is_bundle_product( \WC_Product|int $product ): bool {
			if ( ! is_numeric( $product ) ) {
				$product = $product->get_id();
			}
			$included_products = \get_post_meta( $product, self::INCLUDE_PRODUCT_IDS_META_KEY, false );

			return ! ! $included_products;
		}

		/**
		 * 此銷售方案都有哪些商品
		 *
		 * @return array string[] 被綑綁的 product_ids
		 */
		public function get_product_ids(): array {
			$id                 = $this->get_id();
			$product_ids        = (array) \get_post_meta( $id, self::INCLUDE_PRODUCT_IDS_META_KEY );
			$unique_product_ids = array_unique( $product_ids );

			return $unique_product_ids;
		}

		/**
		 * 往銷售方案裡面添加商品 id
		 *
		 * @param int $product_id product_id.
		 *
		 * @return void
		 */
		public function add_bundled_ids( int $product_id ): void {
			$bundle_ids = $this->get_product_ids();
			if ( in_array( (string) $product_id, $bundle_ids, true ) ) {
				return;
			}
			$this->add_meta_data( self::INCLUDE_PRODUCT_IDS_META_KEY, $product_id );
			$this->save_meta_data();
		}


		/**
		 * 直接設定銷售方案裡面商品 ids
		 *
		 * @param array<int> $product_ids product_ids.
		 *
		 * @return void
		 */
		public function set_bundled_ids( array $product_ids ): void {
			$this->delete_meta_data( self::INCLUDE_PRODUCT_IDS_META_KEY );
			foreach ($product_ids as $product_id) {
				$this->add_meta_data( self::INCLUDE_PRODUCT_IDS_META_KEY, $product_id );
			}
			$this->save_meta_data();
		}

		/**
		 * Delete bundle_ids
		 *
		 * @param int $product_id product_id.
		 *
		 * @return void
		 */
		public function delete_bundled_ids( int $product_id ): void {
			$this->delete_meta_data_value( self::INCLUDE_PRODUCT_IDS_META_KEY, $product_id );
			$this->save_meta_data();
		}
	}
}
