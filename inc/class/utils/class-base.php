<?php
/**
 * Base
 */

declare (strict_types = 1);

namespace J7\PowerCourse\Utils;

/**
 * Class Utils
 */
abstract class Base {
	const BASE_URL      = '/';
	const APP1_SELECTOR = '#power_course';
	const APP2_SELECTOR = '#power_course_metabox';
	const API_TIMEOUT   = '30000';
	const DEFAULT_IMAGE = 'http://1.gravatar.com/avatar/1c39955b5fe5ae1bf51a77642f052848?s=96&d=mm&r=g';

	/**
	 * Is HPOS enabled
	 *
	 * @return bool
	 */
	public static function is_hpos_enabled(): bool {
		return class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class ) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
	}

	/**
	 * Sanitize Array
	 *
	 * @param array|string $value Value to sanitize
	 * @return array|string
	 */
	public static function sanitize_text_field_deep( $value ) {
		if ( is_array( $value ) ) {
			// if array, sanitize each element
			foreach ( $value as $key => $item ) {
				$value[ $key ] = self::sanitize_text_field_deep( $item );
			}
			return $value;
		} else {
			// if not array, sanitize the value
			return \sanitize_text_field( $value );
		}
	}

	/**
	 * Get Top Sales Products
	 *
	 * @see https://github.com/woocommerce/woocommerce/wiki/wc_get_products-and-WC_Product_Query
	 *
	 * @param int $limit Limit.
	 * @return array
	 */
	public static function get_top_sales_products( $limit = 10 ) {
		global $wpdb;
		// 执行查询
		$top_selling_products = $wpdb->get_results(
			$wpdb->prepare(
				"
    SELECT post_id, CAST(meta_value AS UNSIGNED) AS total_sales
    FROM {$wpdb->postmeta}
    WHERE meta_key = 'total_sales'
    ORDER BY total_sales DESC
    LIMIT %d
",
				$limit
			)
		);

		$formatted_top_selling_products = array_map(
			function ( $product ) {
				$product_id   = $product->post_id;
				$product_name = get_the_title( $product_id );
				$total_sales  = $product->total_sales;

				return array(
					'id'          => (string) $product_id,
					'name'        => $product_name,
					'total_sales' => (float) $total_sales,
				);
			},
			$top_selling_products
		);

		return $formatted_top_selling_products;
	}
}
