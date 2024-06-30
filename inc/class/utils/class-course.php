<?php
/**
 * Base
 */

declare ( strict_types=1 );

namespace J7\PowerCourse\Utils;

use J7\PowerCourse\Admin\Product as AdminProduct;
use J7\PowerCourse\Resources\Chapter\RegisterCPT;
use WC_Product;


/**
 * Class Utils
 */
abstract class Course {

	/**
	 * 檢查商品是否為課程商品
	 *
	 * @param WC_Product|int $product Product.
	 *
	 * @return bool
	 */
	public static function is_course_product( WC_Product|int $product ): bool {
		if ( is_numeric( $product ) ) {
			$product = \wc_get_product( $product );
		}

		return $product?->get_meta( '_' . AdminProduct::PRODUCT_OPTION_NAME ) === 'yes';
	}

	/**
	 * 檢查課程開課了沒
	 *
	 * @param \WC_Product|int $product Product.
	 *
	 * @return bool
	 */
	public static function is_course_ready( \WC_Product|int $product ): bool {
		if ( is_numeric( $product ) ) {
			$product = \wc_get_product( $product );
		}

		return $product->get_meta( 'course_schedule' ) < time();
	}

	/**
	 * 取得課程章節
	 *
	 * @param \WC_Product $product 商品
	 * @param bool|null   $return_ids 是否只回傳 id
	 *
	 * @return array
	 */
	public static function get_sub_chapters( \WC_Product $product, ?bool $return_ids = false ): array {
		$args = [
			'posts_per_page' => - 1,
			'order'          => 'ASC',
			'orderby'        => 'menu_order',
			'post_parent'    => $product->get_id(),
			'post_status'    => 'publish',
			'post_type'      => RegisterCPT::POST_TYPE,
			'fields'         => 'ids',
		];

		$chapter_ids = \get_children( $args );

		$sub_chapters = [];
		foreach ( $chapter_ids as $chapter_id ) :
			$args = [
				'posts_per_page' => - 1,
				'order'          => 'ASC',
				'orderby'        => 'menu_order',
				'post_parent'    => $chapter_id,
				'post_status'    => 'publish',
				'post_type'      => RegisterCPT::POST_TYPE,
			];

			if ( $return_ids ) {
				$args['fields'] = 'ids';
			}

			$sub_chapters = array_merge( $sub_chapters, \get_children( $args ) );
		endforeach;

		return $sub_chapters;
	}


	/**
	 * 取得 bundle_ids (銷售方案 ids) by product
	 * 用商品反查有哪些銷售方案
	 *
	 * @param int $product_id 商品 id
	 *
	 * @return array bundle_ids (銷售方案 ids)
	 */
	public static function get_bundle_ids_by_product( int $product_id ): array {
		return \get_posts(
			[
				'post_type'   => 'product',
				'numberposts' => - 1,
				'post_status' => [ 'publish', 'draft' ],
				'fields'      => 'ids', // 只取 id
				'meta_key'    => 'pbp_product_ids',
				'meta_value'  => $product_id,
			]
		);
	}

	/**
	 * 取得課程限制條件名稱
	 *
	 * @param WC_Product $product 商品
	 *
	 * @return string
	 */
	public static function get_limit_label_by_product( \WC_Product $product ): string {
		$limit_type       = $product->get_meta( 'limit_type' );
		$limit_type_label = match ( $limit_type ) {
			'fixed'    => '固定時間',
			'assigned' => '指定日期',
			default    => '無限制',
		};

		$limit_unit       = $product->get_meta( 'limit_unit' );
		$limit_unit_label = match ( $limit_unit ) {
			'second' => '秒',
			'month'  => '月',
			'year'   => '年',
			default  => '天',
		};

		$limit_value = $product->get_meta( 'limit_value' );

		return "{$limit_type_label} {$limit_value} {$limit_unit_label}";
	}


	/**
	 * 取得用戶已購買的課程商品 WC_Product[]
	 *
	 * @param array|null $args 參數
	 *                         - numberposts int 每頁数量
	 *                         - offset int 跳過的数量
	 *                         - order string 排序
	 *                         - user_id int 用户 ID 查询
	 *
	 * @return array \WC_Product[]
	 */
	public static function get_courses_by_user( ?array $args = [] ): array {
		$order_item_ids = self::get_course_order_item_ids_by_user( $args );

		$courses = [];

		foreach ( $order_item_ids as $order_item_id ) {
			$order_item = new \WC_Order_Item_Product( $order_item_id );
			$product    = $order_item->get_product();
			if ( $product ) {
				$courses[] = $product;
			}
		}

		return $courses;
	}

	/**
	 * 取得用戶已購買的課程 order_item_id[]
	 *
	 * @param array|null $args 參數
	 *                         - numberposts int 每頁数量
	 *                         - offset int 跳過的数量
	 *                         - order string 排序
	 *                         - user_id int 用户 ID 查询
	 *                         - status string[]|string 訂單狀態，預設找已完成 'any' | 'wc-completed' | 'wc-processing' | 'wc-on-hold' | 'wc-pending' | 'wc-cancelled' | 'wc-refunded' | 'wc-failed'
	 *
	 * @return array string[] order_item_ids
	 */
	public static function get_course_order_item_ids_by_user( ?array $args = [] ): array {
		$defaults = [
			'numberposts' => 10,                                  // 每頁数量
			'offset'      => 0,                                   // 跳過的数量
			// 'orderby' => 'date', // TODO 排序字段
			'order'       => 'DESC',                              // 排序
			'user_id'     => get_current_user_id(),               // 用户 ID 查询
			'status'      => [ 'wc-completed' ],                  // 訂單狀態
		];

		$args = \wp_parse_args( $args, $defaults );
		[
			'numberposts' => $numberposts,
			'offset'      => $offset,
			'order'       => $order,
			'user_id'     => $user_id,
			'status'      => $statuses,
		]     = $args;

		if ( is_array( $statuses ) ) {
			$statuses_string  = implode(
				',',
				array_map(
					function ( $status ) {
						return '"' . $status . '"';
					},
					$statuses
				)
			);
			$status_condition = sprintf(
				'AND posts.post_status IN ( %1$s )',
				$statuses_string
			);
		} else {
			$status_condition = ( $statuses === 'any' ) ? '' : sprintf(
				'AND posts.post_status = %1$s',
				$statuses
			);
		}

		global $wpdb;
		$prepare = $wpdb->prepare(
			"
	WITH ranked_items AS (
        SELECT
            order_items.order_item_id,
            order_items.order_id,
            order_items.order_item_name,
            MAX(CASE WHEN product_id.meta_key = '%1\$s' THEN product_id.meta_value END) AS product_id,
            posts.post_date,
            ROW_NUMBER() OVER (PARTITION BY MAX(CASE WHEN product_id.meta_key = '%2\$s' THEN product_id.meta_value END) ORDER BY posts.post_date DESC, order_items.order_item_id) AS rn
        FROM {$wpdb->prefix}woocommerce_order_items AS order_items
        LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS qty_meta
            ON order_items.order_item_id = qty_meta.order_item_id AND qty_meta.meta_key = '%3\$s'
        LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS product_id
            ON order_items.order_item_id = product_id.order_item_id
        LEFT JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID
        WHERE qty_meta.meta_value = '%4\$s'
        AND posts.post_author = %5\$d
        %6\$s
        GROUP BY order_items.order_item_id, order_items.order_id, order_items.order_item_name, posts.post_date
    )
    SELECT order_item_id, order_id, order_item_name, product_id, post_date
    FROM ranked_items
    WHERE rn = 1
    ORDER BY post_date %7\$s
    LIMIT %8\$d, %9\$d",
			'_product_id',                 // %1$s
			'_product_id',                 // %2$s
			'_is_course',                  // %3$s - meta_key
			'yes',                         // %4$s - meta_value
			$user_id,                      // %5$d - post_author
			$status_condition,             // %6$s - status condition
			$order,                        // %7$s - order
			$offset,                       // %8$d - offset
			$numberposts                   // %9$d - limit
		);

		\J7\WpUtils\Classes\Log::info( str_replace( '\"', '"', $prepare ) );

		return $wpdb->get_col( str_replace( '\"', '"', $prepare ) );
	}

	/**
	 * 取得用戶已購買的課程 order_item_product[]
	 *
	 * @hint 如果想知道當時購買的限制條件可以用這個
	 *
	 * @param array|null $args 參數
	 *                         - numberposts int 每頁数量
	 *                         - offset int 跳過的数量
	 *                         - order string 排序
	 *                         - user_id int 用户 ID 查询
	 *                         - status string[]|string 訂單狀態，預設找已完成 'any' | 'wc-completed' | 'wc-processing' | 'wc-on-hold' | 'wc-pending' | 'wc-cancelled' | 'wc-refunded' | 'wc-failed'
	 *
	 * @return array \WC_Order_Item_Product[] order_items
	 */
	public static function get_course_order_items_by_user( ?array $args = [] ): array {
		$order_item_ids = self::get_course_order_item_ids_by_user( $args );

		$course_order_items = [];

		foreach ( $order_item_ids as $order_item_id ) {
			$order_item_product   = new \WC_Order_Item_Product( $order_item_id );
			$course_order_items[] = $order_item_product;
		}

		return $course_order_items;
	}

	/**
	 * 檢查用戶是否購買過指定商品
	 *
	 * @param int|array  $target_product_ids
	 * @param array|null $args
	 * - user_id int 使用者 ID，預設 current_user_id
	 * - status string[]|string 訂單狀態 'any' | 'wc-completed' | 'wc-processing' | 'wc-on-hold' | 'wc-pending' | 'wc-cancelled' | 'wc-refunded' | 'wc-failed' , 預設 [ 'wc-completed' ]
	 *
	 * @return bool
	 */
	public static function has_bought( int|array $target_product_ids, ?array $args = [] ): bool {
		$defaults = [
			'user_id' => get_current_user_id(),               // 用户 ID 查询
			'status'  => [ 'wc-completed' ],                  // 訂單狀態
		];

		$args = \wp_parse_args( $args, $defaults );
		[
			'user_id' => $user_id,
			'status'  => $statuses,
		]     = $args;

		// 構建 status 查詢條件
		if ( is_array( $statuses ) ) {
			$statuses_string  = implode(
				',',
				array_map(
					function ( $status ) {
						return '"' . $status . '"';
					},
					$statuses
				)
			);
			$status_condition = sprintf(
				'AND posts.post_status IN ( %1$s )',
				$statuses_string
			);
		} else {
			$status_condition = ( $statuses === 'any' ) ? '' : sprintf(
				'AND posts.post_status = %1$s',
				$statuses
			);
		}
		// 構建 target_product_ids 查詢條件
		if ( is_array( $target_product_ids ) ) {
			$target_product_ids_string = implode(
				',',
				array_map(
					function ( $target_product_id ) {
						return '"' . $target_product_id . '"';
					},
					$target_product_ids
				)
			);
		} else {
			$target_product_ids_string = $target_product_ids;
		}

		global $wpdb;
		// phpcs:disable
		$prepare = $wpdb->prepare(
			"
	WITH ranked_items AS (
        SELECT
            order_items.order_item_id,
            order_items.order_id,
            order_items.order_item_name,
            MAX(CASE WHEN product_id.meta_key = '%1\$s' THEN product_id.meta_value END) AS product_id,
            posts.post_date,
            ROW_NUMBER() OVER (PARTITION BY MAX(CASE WHEN product_id.meta_key = '%2\$s' THEN product_id.meta_value END) ORDER BY posts.post_date DESC, order_items.order_item_id) AS rn
        FROM {$wpdb->prefix}woocommerce_order_items AS order_items
        LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS qty_meta
            ON order_items.order_item_id = qty_meta.order_item_id AND qty_meta.meta_key = '%3\$s'
        LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS product_id
            ON order_items.order_item_id = product_id.order_item_id
        LEFT JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID
        WHERE qty_meta.meta_value = '%4\$s'
        AND posts.post_author = %5\$d
        %6\$s
        GROUP BY order_items.order_item_id, order_items.order_id, order_items.order_item_name, posts.post_date
    )
    SELECT order_item_id, order_id, order_item_name, product_id, post_date
    FROM ranked_items
    WHERE rn = 1
    AND product_id IN ( %7\$s )",
			'_product_id',                       // %1$s
			'_product_id',                       // %2$s
			'_is_course',                        // %3$s - meta_key
			'yes',                               // %4$s - meta_value
			$user_id,                            // %5$d - post_author
			$status_condition,                   // %6$s - status condition
			$target_product_ids_string           // %7$s - target_product_ids_condition
		);

		$results = $wpdb->get_results( str_replace( '\"', '"', $prepare ) );

		// phpcs:enable

		return ! ! $results;
	}
}
