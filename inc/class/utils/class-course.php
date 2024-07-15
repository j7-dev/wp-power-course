<?php
/**
 * Base
 */

declare ( strict_types=1 );

namespace J7\PowerCourse\Utils;

use J7\PowerCourse\Admin\Product as AdminProduct;
use J7\PowerCourse\Resources\Chapter\RegisterCPT;
use J7\PowerCourse\Utils\AVLCourseMeta;


/**
 * Class Utils
 */
abstract class Course {

	/**
	 * 檢查商品是否為課程商品
	 *
	 * @param \WC_Product|int $product Product.
	 *
	 * @return bool
	 */
	public static function is_course_product( \WC_Product|int $product ): bool {
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
	 * @param \WC_Product|int $product 商品
	 * @param bool|null       $return_ids 是否只回傳 id
	 *
	 * @return array<int|\WP_Post>
	 */
	public static function get_sub_chapters( \WC_Product|int $product, ?bool $return_ids = false ): array {
		if (!is_numeric($product)) {
			$product = $product->get_id();
		}

		$args = [
			'posts_per_page' => - 1,
			'order'          => 'ASC',
			'orderby'        => 'menu_order',
			'post_parent'    => $product,
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
	 * 取得課程長度
	 *
	 * @param \WC_Product $product 商品
	 * @param string|null $type 類型 'second' | 'minute' | 'hour' | 'video_length'
	 *
	 * @return string
	 */
	public static function get_course_length( \WC_Product $product, ?string $type = 'second' ): string {
		$chapter_ids = self::get_sub_chapters( $product, true );

		$length = 0;
		foreach ( $chapter_ids as $chapter_id ) {
			$video_length = (int) \get_post_meta( $chapter_id, 'video_length', true );
			$length      += $video_length;
		}

		if ( 'minute' === $type ) {
			return (string) floor( $length / 60 );
		}

		if ( 'hour' === $type ) {
			return (string) floor( $length / 3600 );
		}

		if ('video_length' === $type) {
			return Base::get_video_length_by_seconds( $length);
		}
		return (string) $length;
	}

	/**
	 * 取得課程進度
	 *
	 * @param \WC_Product $product 課程商品
	 * @param int|null    $user_id 用户 ID
	 *
	 * @return float
	 */
	public static function get_course_progress( \WC_Product $product, ?int $user_id = 0 ): float {
		if (!$user_id) {
			$user_id = get_current_user_id();
		}
		$product_id              = $product->get_id();
		$sub_chapters_count      = count(self::get_sub_chapters($product, true));
		$finished_chapters_count = count(self::get_finished_chapters($product_id, $user_id, return_ids: true));

		return $sub_chapters_count ? round(( $finished_chapters_count / $sub_chapters_count * 100 ), 1) : 0;
	}
	/**
	 * 取得已完成章節
	 *
	 * @param int       $course_id 課程 ID
	 * @param int|null  $user_id 用户 ID
	 * @param bool|null $return_ids 是否只回傳 id
	 *
	 * @return array<\WP_Post|string>
	 */
	public static function get_finished_chapters( int $course_id, ?int $user_id = 0, ?bool $return_ids = false ): array {
		if (!$user_id) {
			$user_id = get_current_user_id();
		}
		$finished_chapter_ids = AVLCourseMeta::get($course_id, $user_id, 'finished_chapter_ids');
		$finished_chapter_ids = \is_array($finished_chapter_ids) ? $finished_chapter_ids : [];
		if ($return_ids) {
			/**
			 * @var array<string> $finished_chapter_ids
			 */
			return $finished_chapter_ids;
		}

		$chapters = [];
		foreach ($finished_chapter_ids as $chapter_id) {
			$chapters[] = \get_post($chapter_id);
		}

		return $chapters;
	}

	/**
	 * 取得 bundle_ids (銷售方案) by product
	 * 用商品反查有哪些銷售方案
	 *
	 * @param int       $product_id 商品 id
	 * @param bool|null $return_ids 是否只回傳 id
	 *
	 * @return array<\WP_Post|int> bundle_ids (銷售方案)
	 */
	public static function get_bundles_by_product( int $product_id, ?bool $return_ids = false ): array {

		$args = [
			'post_type'   => 'product',
			'numberposts' => - 1,
			'post_status' => [ 'publish', 'draft' ],
			'meta_key'    => 'pbp_product_ids',
			'meta_value'  => (string) $product_id,
		];

		if ($return_ids) {
			$args['fields'] = 'ids';// 只取 id
		}

		return \get_posts($args);
	}

	/**
	 * 取得課程限制條件名稱
	 *
	 * @param \WC_Product $product 商品
	 *
	 * @return array{type:string, value:string}
	 */
	public static function get_limit_label_by_product( \WC_Product $product ): array {
		$limit_type       = $product->get_meta( 'limit_type' );
		$limit_type_label = match ( $limit_type ) {
			'fixed'    => '固定時間',
			'assigned' => '指定日期',
			default    => '無限制',
		};

		$limit_unit  = $product->get_meta( 'limit_unit' );
		$limit_value = $product->get_meta( 'limit_value' );

		$limit_value_label = match ( $limit_unit ) {
			'timestamp' => \wp_date( 'Y-m-d H:i:s', $limit_value ),
			'month'  => "{$limit_value} 月",
			'year'   => "{$limit_value} 年",
			default  => "{$limit_value} 天",
		};

		return [
			'type'  => $limit_type_label,
			'value' => $limit_value_label,
		];
	}

	/**
	 * 查詢用戶可以上那些課程 ids
	 *
	 * @param int|null $user_id 用户 ID
	 *
	 * @return array<\WC_Product|string> 課程 ids
	 */
	public static function get_avl_courses_by_user( ?int $user_id = null, ?bool $return_ids = false ): array {

		$user_id        = $user_id ?? get_current_user_id();
		$avl_course_ids = \get_user_meta($user_id, 'avl_course_ids', false);

		/**
		 * @var array<string> $avl_course_ids
		 */
		$avl_course_ids = \is_array($avl_course_ids) ? $avl_course_ids : [];
		if ($return_ids) {
			return $avl_course_ids;
		}

		$avl_courses = [];
		foreach ($avl_course_ids as $avl_course_id) {
			$course = \wc_get_product($avl_course_id);
			if (!!$course) {
				$avl_courses[] = $course;
			}
		}

		return $avl_courses;
	}

	/**
	 * 從用戶訂單中取得用戶已購買的課程商品 \WC_Product[]
	 * 也會查找用戶買的 Bundle Products 裡面有沒有包含課程商品
	 * 如果你要取得用戶能上的課程，請使用 get_avl_courses_by_user
	 *
	 * @param array|null $args 參數
	 *                         - numberposts int 每頁数量
	 *                         - offset int 跳過的数量
	 *                         - order string 排序
	 *                         - user_id int 用户 ID 查询
	 *
	 * @return array<\WC_Product>
	 */
	public static function get_courses_by_user_orders( ?array $args = [] ): array {
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
	 * 如果你要取得用戶能上的課程，請使用 get_avl_courses_by_user
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

		if ( \is_array( $statuses ) ) {
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
	 * Checks if a course is available for a given product and user.
	 *
	 * @param \WC_Product|null $the_product The product to check availability for.
	 * @param int|null         $user_id The user ID to check availability for.
	 * @return bool Returns true if the course is available, false otherwise.
	 */
	public static function is_avl( ?\WC_Product $the_product = null, ?int $user_id = null ): bool {
		global $product;
		$the_product    = $the_product ?? $product;
		$the_product_id = (string) $the_product->get_id();
		$user_id        = $user_id ?? \get_current_user_id();
		$avl_course_ids = self::get_avl_courses_by_user($user_id, return_ids: true);
		return in_array($the_product_id, $avl_course_ids, true);
	}

	/**
	 * 檢查課程是否已過期。
	 *
	 * 根據產品ID和用戶ID，從AVLCourseMeta中獲取課程的過期日期，
	 * 然後判斷當前時間是否超過該過期日期。
	 *
	 * @param \WC_Product|null $the_product 產品實例，預設為null。
	 * @param int|null         $user_id 用戶ID，預設為null。
	 * @return bool 如果課程已過期，返回true；否則返回false。
	 */
	public static function is_expired( ?\WC_Product $the_product = null, ?int $user_id = null ): bool {
		global $product;
		$the_product    = $the_product ?? $product;
		$the_product_id = $the_product->get_id();
		$user_id        = $user_id ?? \get_current_user_id();
		$expire_date    = AVLCourseMeta::get($the_product_id, $user_id, 'expire_date', true);
		return empty($expire_date) ? false : $expire_date < time();
	}

	/**
	 * 獲取課程可用狀態。
	 *
	 * 根據產品和用戶ID判斷課程的可用狀態，返回狀態標籤和顏色。
	 *
	 * @param \WC_Product|null $the_product 產品實例，預設為當前產品。
	 * @param int|null         $user_id 用戶ID，預設為當前登入用戶。
	 * @return array{label:string,badge_color:string } 包含'label'和'badge_color'的狀態數組。
	 */
	public static function get_avl_status( ?\WC_Product $the_product = null, ?int $user_id = null ): array {
		global $product;
		$the_product = $the_product ?? $product;
		$user_id     = $user_id ?? \get_current_user_id();

		if ( ! self::is_avl($the_product, $user_id) ) {
			return [
				'label'       => '未購買',
				'badge_color' => 'ghost',
			];
		}

		if ( ! self::is_course_ready( $the_product ) ) {
			return [
				'label'       => '未開課',
				'badge_color' => 'neutral',
			];
		}

		if ( self::is_expired( $the_product, $user_id ) ) {
			return [
				'label'       => '已到期',
				'badge_color' => 'accent',
			];
		}

		return [
			'label'       => '可觀看',
			'badge_color' => 'primary',
		];
	}

	/**
	 * 檢查用戶是否購買過指定商品
	 *
	 * @param int|array<int>                                       $target_product_ids 目標商品 id
	 * @param array{'user_id':int, 'status':string[]|string }|null $args 參數
	 * - user_id int 使用者 ID，預設 current_user_id
	 * - status string[]|string 訂單狀態 'any' | 'wc-completed' | 'wc-processing' | 'wc-on-hold' | 'wc-pending' | 'wc-cancelled' | 'wc-refunded' | 'wc-failed' , 預設 [ 'wc-completed' ]
	 *
	 * @return bool
	 */
	public static function has_bought( int|array $target_product_ids, ?array $args = null ): bool {
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
		if ( \is_array( $statuses ) ) {
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
		if ( \is_array( $target_product_ids ) ) {
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
