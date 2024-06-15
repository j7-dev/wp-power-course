<?php
/**
 * Base
 */

declare (strict_types = 1);

namespace J7\PowerCourse\Utils;

use J7\PowerCourse\Admin\Product as AdminProduct;

/**
 * Class Utils
 */
abstract class Base {
	const BASE_URL      = '/';
	const APP1_SELECTOR = '#power_course';
	const APP2_SELECTOR = '#power_course_metabox';
	const API_TIMEOUT   = '30000';
	const DEFAULT_IMAGE = 'https://placehold.co/480x270/1677ff/white';
	const PRIMARY_COLOR = '#1677ff';



	/**
	 * Is product course_product
	 *
	 * @param \WC_Product|int $product Product.
	 * @return bool
	 */
	public static function is_course_product( \WC_Product|int $product ): bool {
		if ( is_numeric( $product ) ) {
			$product = \wc_get_product( $product );
		}

		$is_course_product = $product?->get_meta( '_' . AdminProduct::PRODUCT_OPTION_NAME ) === 'yes';
		return $is_course_product;
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
		$ids = \get_posts(
			array(
				'post_type'   => 'product',
				'numberposts' => -1,
				'post_status' => array( 'publish', 'draft' ),
				'fields'      => 'ids', // 只取 id
				'meta_key'    => 'pbp_product_ids',
				'meta_value'  => $product_id,
			)
		);
		return $ids;
	}
}
