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
}
