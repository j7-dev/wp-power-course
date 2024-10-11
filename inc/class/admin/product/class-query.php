<?php
/**
 * Product Query
 */

declare(strict_types=1);

namespace J7\PowerCourse\Admin\Product;

use J7\PowerBundleProduct\BundleProduct;
use J7\WpUtils\Classes\General;

/**
 * Class Query
 */
final class Query {
	use \J7\WpUtils\Traits\SingletonTrait;

	/**
	 * Constructor
	 */
	public function __construct() {
		\add_action( 'init', [ __CLASS__, 'add_post_meta_to_course_product' ] );

		\add_action( 'pre_get_posts', [ __CLASS__, 'exclude_course_product' ], 10 );
		\add_action( 'pre_get_posts', [ __CLASS__, 'exclude_bundle_product' ], 20);
	}

	/**
	 * Add Post Meta To Course Product
	 * 把每個商品都標示，是否為課程商品
	 *
	 * @return void
	 */
	public static function add_post_meta_to_course_product(): void {
		$args = [
			'post_type'      => 'product',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => [
				[
					'key'     => '_is_course',
					'compare' => 'NOT EXISTS',
				],
			],
		];

		$ids = \get_posts($args);

		foreach ($ids as $id) {
			\add_post_meta($id, '_is_course', 'no');
		}
	}

	/**
	 * Exclude Course Product
	 * [危險] 會影響全站的查詢
	 * 如果是後台，讓管理員知道 課程就是商品
	 * 如果是前台主查詢 (外掛, 短碼不算主查詢)，排除課程商品
	 * 搜尋 - 可以找到課程商品
	 *
	 * @param \WP_Query $query 查詢
	 * @return void
	 */
	public static function exclude_course_product( $query ): void {

		if ( \is_admin() || !$query->is_main_query() || $query->is_single()) {
			return;
		}

		// 搜尋時候 $post_type 有時是 "" 有時候是 array
		$post_type = $query->get('post_type');

		if (is_array($post_type)) {
			if (!in_array('product', $post_type, true) && !$query->is_search()) {
				return;
			}
		} elseif ('product' !== $post_type && !$query->is_search()) {
			return;
		}

		$meta_query = $query->get('meta_query');

		if (!$meta_query) {
			$meta_query             = [];
			$meta_query['relation'] = 'AND';
		}

		$meta_query[] = [
			'key'     => '_is_course',
			'value'   => 'no',
			'compare' => '=',
		];

		$query->set('meta_query', $meta_query);
	}

	/**
	 * Exclude Bundle Product
	 * [危險] 會影響全站的查詢
	 * 只有在 power course api 取得時，還有課程銷售頁可見
	 * 搜尋 - 排除 bundle product
	 *
	 * @param \WP_Query $query 查詢
	 * @return void
	 */
	public static function exclude_bundle_product( $query ): void {
		$meta_key = $query->get('meta_key');
		// 只有在 power course api 取得時，還有課程銷售頁可見
		if (General::in_url([ '/wp-json/power-course' ]) || BundleProduct::INCLUDE_PRODUCT_IDS_META_KEY === $meta_key) {
			return;
		}

		$post_type = $query->get('post_type');
		if (is_array($post_type)) {
			if (!in_array('product', $post_type, true)) {
				return;
			}
		} elseif ('product' !== $post_type) {
			return;
		}

		$meta_query = $query->get('meta_query');

		if (!$meta_query) {
			$meta_query             = [];
			$meta_query['relation'] = 'AND';
		}

		$meta_query[] = [
			'key'     => BundleProduct::INCLUDE_PRODUCT_IDS_META_KEY,
			'compare' => 'NOT EXISTS',
		];

		$query->set('meta_query', $meta_query);
	}
}

Query::instance();
