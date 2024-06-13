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
	const DEFAULT_IMAGE = 'http://1.gravatar.com/avatar/1c39955b5fe5ae1bf51a77642f052848?s=96&d=mm&r=g';
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
