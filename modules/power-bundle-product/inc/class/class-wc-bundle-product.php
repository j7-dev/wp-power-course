<?php
/**
 * Define custom product type.
 *
 * @package WooCommerce Custom Product Type
 */

declare (strict_types = 1);

namespace J7\PowerBundleProduct;

if ( ! class_exists( 'BundleProduct' ) ) {

	/**
	 * Custom Product class.
	 */
	final class BundleProduct extends \WC_Product {

		const INCLUDE_PRODUCT_IDS_META_KEY = 'pbp_product_ids'; // 綑綁商品裡面包含的商品 ids
		const LINK_TO_BUNDLE_IDS_META_KEY  = 'pbp_bundle_ids'; // 此商品連結到哪個 bundle product ids

		/**
		 * Constructor of this class.
		 *
		 * @param object $product product.
		 */
		public function __construct( $product = 0 ) {
			$this->product_type = Plugin::PRODUCT_TYPE;
			$this->supports[]   = 'ajax_add_to_cart';

			parent::__construct( $product );
		}

		/**
		 * Return the product type.
		 *
		 * @return string
		 */
		public function get_type() {
			return Plugin::PRODUCT_TYPE;
		}

		/**
		 * Get bundle_ids
		 *
		 * @return array string[] product_ids
		 */
		public function get_bundled_ids(): array {
			$meta_data_array = $this->get_meta( self::INCLUDE_PRODUCT_IDS_META_KEY, false );
			$bundle_ids      = [];

			foreach ( $meta_data_array as $meta_data ) {
				$value        = $meta_data->__get( 'value' );
				$bundle_ids[] = $value;
			}

			return $bundle_ids;
		}

		/**
		 * Add bundle_ids
		 *
		 * @param int $product_id product_id.
		 * @return void
		 */
		public function add_bundled_ids( int $product_id ): void {
			$bundle_ids = $this->get_bundled_ids();
			if ( in_array( (string) $product_id, $bundle_ids, true ) ) {
				return;
			}
			$this->add_meta_data( self::INCLUDE_PRODUCT_IDS_META_KEY, $product_id );
			$this->save_meta_data();
		}


		/**
		 * Set bundle_ids
		 * TODO
		 *
		 * @param array $product_ids product_ids.
		 * @return void
		 */
		public function set_bundled_ids( array $product_ids ): void {
		}

		/**
		 * Delete bundle_ids
		 *
		 * @param int $product_id product_id.
		 * @return void
		 */
		public function delete_bundled_ids( int $product_id ): void {
			$this->delete_meta_data_value( self::INCLUDE_PRODUCT_IDS_META_KEY, $product_id );
		}
	}
}
