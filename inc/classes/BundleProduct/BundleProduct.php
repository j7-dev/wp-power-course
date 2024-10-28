<?php
/**
 * Define custom product type.
 *
 * @package WooCommerce Custom Product Type
 */

declare ( strict_types=1 );

namespace J7\PowerCourse\BundleProduct;

/**
 * Custom Product class.
 */
final class BundleProduct extends \WC_Product {

	// DELETE 起初是因為前端篩選商品需要用到，但現在沒有使用到
	public const PRODUCT_TYPE = 'power_bundle_product';

	public const INCLUDE_PRODUCT_IDS_META_KEY = 'pbp_product_ids'; // 綑綁商品裡面包含的商品 ids

	public const LINK_TO_BUNDLE_IDS_META_KEY = 'pbp_bundle_ids';          // 此商品連結到哪個 bundle product ids

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
		return self::PRODUCT_TYPE;
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
		$bundle_type = \get_post_meta( $product, 'bundle_type', true );

		return ! ! $bundle_type;
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
