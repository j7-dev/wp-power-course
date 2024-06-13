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

		const BUNDLE_IDS_META_KEY = 'pbp_bundlesell_ids';

		/**
		 * Constructor of this class.
		 *
		 * @param object $product product.
		 */
		public function __construct( $product ) {
			$this->product_type = Plugin::PRODUCT_TYPE;
			$this->supports[]   = 'ajax_add_to_cart';
			$this->bundle_ids   = $this->get_bundle_ids();

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
		 * @return array
		 */
		public function get_bundle_ids(): array {
			$bundle_ids = $this->get_meta( self::BUNDLE_IDS_META_KEY );
			if ( ! is_array( $bundle_ids ) ) {
				$bundle_ids = array();
			}
			return $bundle_ids;
		}

		/**
		 * Add bundle_ids
		 *
		 * @param int $product_id product_id.
		 * @return void
		 */
		public function add_bundle_ids( int $product_id ): void {
			$bundle_ids = $this->bundle_ids;
			if ( in_array( (string) $product_id, $bundle_ids, true ) ) {
				return;
			}
			$this->add_meta_data( self::BUNDLE_IDS_META_KEY, $product_id );
		}


		/**
		 * Set bundle_ids
		 * TODO
		 *
		 * @param array $product_ids product_ids.
		 * @return void
		 */
		public function set_bundle_ids( array $product_ids ): void {
		}

		/**
		 * Delete bundle_ids
		 *
		 * @param int $product_id product_id.
		 * @return void
		 */
		public function delete_bundle_ids( int $product_id ): void {
			$this->delete_meta_data_value( self::BUNDLE_IDS_META_KEY, $product_id );
		}
	}
}
