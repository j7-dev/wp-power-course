<?php
/**
 * Order
 * 處理訂單相關業務
 */

declare( strict_types=1 );

namespace J7\PowerCourse\Resources\Order;

use J7\PowerBundleProduct\BundleProduct;
use J7\PowerCourse\Admin\Product as AdminProduct;
use J7\PowerCourse\Utils\Course as CourseUtils;

/**
 * Class Order
 */
final class Order {
	use \J7\WpUtils\Traits\SingletonTrait;


	/**
	 * Constructor
	 */
	public function __construct() {
		\add_action( 'woocommerce_new_order', [ $this, 'add_course_item_meta' ], 10, 2 );
	}

	/**
	 * 訂單成立時，新增課程資訊到訂單
	 *
	 * @param int       $order_id 訂單 ID
	 * @param \WC_Order $order    訂單
	 *
	 * @return void
	 */
	public function add_course_item_meta( int $order_id, \WC_Order $order ): void {
		$items = $order->get_items();

		// 檢查訂單是否有銷售方案商品，如果有將課程限制條件存入為 order item
		foreach ( $items as $item ) {
			/**
			 * @var \WC_Order_Item_Product $item
			 */
			$product_id = $item->get_product_id();
			$product    = \wc_get_product( $product_id );

			if ( ! $product ) {
				continue;
			}

			// 如果是銷售方案商品，這訂單是購買銷售方案
			if ( BundleProduct::is_bundle_product( $product_id ) ) {
				$bundle_product       = new BundleProduct( $product );
				$included_product_ids = $bundle_product->get_product_ids(); // 綑綁的商品們

				foreach ( $included_product_ids as $included_product_id ) {
					$included_product = \wc_get_product( $included_product_id );
					if ( ! $included_product ) {
						continue;
					}

					$order->add_product(
						$included_product,
						1, // TODO: 應該也要記錄數量
						[
							'name'     => $bundle_product->get_name() . ' - ' . $included_product->get_name(),
							'subtotal' => 0,
							'total'    => 0,
						]
					);
				}
				$order->save();
			}
		}

		// 處理完銷售方案，重新拿一次 items
		$items = $order->get_items();
		foreach ( $items as $item ) {
			/**
			 * @var \WC_Order_Item_Product $item
			 */
			$product_id = $item->get_product_id();
			$product    = \wc_get_product( $product_id );

			if ( ! $product ) {
				continue;
			}
			$this->handle_add_course_item_meta_by_order_item( $item );
		}
	}


	/**
	 * 儲存課程限制資訊到 order item meta
	 *
	 * @param \WC_Order_Item|\WC_Order_Item_Product $item 訂單項目
	 *
	 * @return void
	 */
	private function handle_add_course_item_meta_by_order_item( $item ): void {
		if (!( $item instanceof \WC_Order_Item_Product )) {
			return;
		}

		$product_id = $item->get_product_id();
		$product    = \wc_get_product( $product_id );

		if ( ! $product ) {
			return;
		}

		// 如果是課程商品
		if ( CourseUtils::is_course_product( $product_id ) ) {
			// 將課程限制條件紀錄到訂單
			$meta_keys = [ 'limit_type', 'limit_value', 'limit_unit' ];
			foreach ( $meta_keys as $meta_key ) {
				$meta_value = $product->get_meta( $meta_key );
				$item->update_meta_data( '_' . $meta_key, $meta_value );

				// TODO 寫入 avl_course_meta table
			}
			$item->update_meta_data( '_' . AdminProduct::PRODUCT_OPTION_NAME, 'yes' );
			$item->save_meta_data();
		}
	}
}

Order::instance();
