<?php
/**
 * Base
 */

declare ( strict_types=1 );

namespace J7\PowerCourse\Utils;

use J7\PowerCourse\Admin\Product as AdminProduct;
use WC_Product;

use function get_posts;
use function wc_get_product;

/**
 * Class Utils
 */
abstract class Base {
	public const BASE_URL      = '/';
	public const APP1_SELECTOR = '#power_course';
	public const APP2_SELECTOR = '#power_course_metabox';
	public const API_TIMEOUT   = '30000';
	public const DEFAULT_IMAGE = 'https://placehold.co/480x270/1677ff/white';
	public const PRIMARY_COLOR = '#1677ff';


	/**
	 * Is product course_product
	 *
	 * @param WC_Product|int $product Product.
	 *
	 * @return bool
	 */
	public static function is_course_product( WC_Product|int $product ): bool {
		if ( is_numeric( $product ) ) {
			$product = wc_get_product( $product );
		}

		return $product?->get_meta( '_' . AdminProduct::PRODUCT_OPTION_NAME ) === 'yes';
	}

	/**
	 * 取得 bundle_ids (銷售方案 ids) by product
	 * 用商品反查有哪些銷售方案
	 *
	 * @param int $product_id 商品 id
	 *
	 * @return array
	 */
	public static function get_bundle_ids_by_product( int $product_id ): array {
		return get_posts(
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
	public static function get_limit_label_by_product( \WC_Product $product ) {
		$limit_type       = $product->get_meta( 'limit_type' );
		$limit_type_label = match ( $limit_type ) {
			'fixed' => '固定時間',
			'assigned' => '指定日期',
			default => '無限制',
		};

		$limit_unit       = $product->get_meta( 'limit_unit' );
		$limit_unit_label = match ( $limit_unit ) {
			'second' => '秒',
			'month' => '月',
			'year' => '年',
			default => '天',
		};

		$limit_value = $product->get_meta( 'limit_value' );

		return "{$limit_type_label} {$limit_value} {$limit_unit_label}";
	}
}
