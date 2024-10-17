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
use J7\PowerCourse\Utils\AVLCourseMeta;

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

		\add_action( 'woocommerce_order_status_completed', [ $this, 'add_meta_to_avl_course' ], 10, 1 );
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
			$this->_handle_add_course_item_meta_by_order_item( $item );
		}
	}


	/**
	 * 根據訂單項目儲存課程限制資訊到訂單項目元數據中。
	 *
	 * 此私有方法檢查傳入的訂單項目是否為 WooCommerce 的產品項目。如果是，則進一步檢查該產品是否被標記為課程商品。
	 * 對於課程商品，此方法會從產品中提取課程的限制條件（如限制類型、限制值和限制單位）並將這些資訊儲存到訂單項目的元數據中。
	 * 這樣做可以在後續處理中輕鬆訪問和使用這些課程限制資訊。
	 *
	 * @param \WC_Order_Item|\WC_Order_Item_Product $item 訂單項目，需為 WooCommerce 的產品項目實例。
	 *
	 * @return void
	 */
	private function _handle_add_course_item_meta_by_order_item( $item ): void {
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
			}
			$item->update_meta_data( '_' . AdminProduct::PRODUCT_OPTION_NAME, 'yes' );
			$item->save_meta_data();
		}
	}



	/**
	 * 將元數據添加到訂單中的可用課程。
	 *
	 * 此函數遍歷訂單中的每個商品，檢查是否為課程商品。如果是，則將課程的限制條件（如限制類型、限制值和限制單位）
	 * 紀錄到訂單中。根據這些限制條件，計算並設定課程的到期日存入 avl_coursemeta 表中。
	 *
	 * @param int $order_id 訂單ID。
	 * @return void
	 */
	public function add_meta_to_avl_course( int $order_id ): void {
		$order       = \wc_get_order($order_id);
		$customer_id = $order->get_customer_id();
		if (!$customer_id) {
			return;
		}

		$items = $order->get_items();
		foreach ( $items as $item ) {
			/**
			 * @var \WC_Order_Item_Product $item
			 */
			$product_id = $item->get_product_id();

			// TODO 或有 course_id 的 meta 紀錄

			// 如果是課程商品
			if ( CourseUtils::is_course_product( $product_id ) ) {

				// 先檢查用戶有沒有買過
				$avl_course_ids = \get_user_meta($customer_id, 'avl_course_ids');
				if (!\is_array($avl_course_ids)) {
					$avl_course_ids = [];
				}
				// 如果沒買過就新增
				if (!\in_array($product_id, $avl_course_ids)) {
					\add_user_meta( $customer_id, 'avl_course_ids', $product_id );
				}

				// 將課程限制條件紀錄到訂單
				$limit_type  = $item->get_meta( '_limit_type' );
				$limit_value = (int) $item->get_meta( '_limit_value' );
				$limit_unit  = $item->get_meta( '_limit_unit' );

				/**
				 * 計算到期日 expire_date
					 * $limit_type 'unlimited' | 'fixed' | 'assigned';
					 * $limit_value int
					 * $limit_unit 'timestamp' | 'day' | 'month' | 'year'
					 *
					 * $expire_date int timestamp $limit_type = unlimited 的話就是無期限，就是0
					 */
				$expire_date = 0;

				if ('assigned' === $limit_type) {
					$expire_date = $limit_value; // timestamp
				}
				if ('fixed' === $limit_type) {
					$expire_date = (int) strtotime("+{$limit_value} {$limit_unit}");
				}

				AVLCourseMeta::update( $product_id, $customer_id, 'expire_date', $expire_date);
			}
		}
	}
}

Order::instance();
