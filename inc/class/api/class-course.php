<?php
/**
 * Course API
 */

declare(strict_types=1);

namespace J7\PowerCourse\Api;

use J7\PowerCourse\Plugin;
use J7\PowerCourse\Utils\Base;
use J7\PowerCourse\Admin\Product as AdminProduct;


/**
 * Class Course
 */
final class Course {
	use \J7\WpUtils\Traits\SingletonTrait;

	/**
	 * Constructor.
	 */
	public function __construct() {
		\add_action( 'rest_api_init', array( $this, 'register_api_products' ) );
	}

	/**
	 * Register Course API
	 *
	 * @return void
	 */
	public function register_api_products(): void {

		$apis = array(
			array(
				'endpoint' => 'courses',
				'method'   => 'get',
			),
		);

		foreach ( $apis as $api ) {
			\register_rest_route(
				Plugin::$kebab,
				$api['endpoint'],
				array(
					'methods'             => $api['method'],
					'callback'            => array( $this, $api['method'] . '_' . $api['endpoint'] . '_callback' ),
					'permission_callback' => '__return_true',
				)
			);
		}
	}


	/**
	 * Get courses callback
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function get_courses_callback( $request ) { // phpcs:ignore

		$params = $request?->get_query_params() ?? array();

		$params = array_map( array( 'J7\WpUtils\Classes\WP', 'sanitize_text_field_deep' ), $params );

		$default_args = array(
			'status'         => 'publish',
			'paginate'       => true,
			'posts_per_page' => 10,
			'page'           => 1,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'is_course'      => true,
		);

		$args = \wp_parse_args(
			$params,
			$default_args,
		);

		if ( isset( $args['price_range'] ) ) {
			$args['meta_query'] = array(
				'relation' => 'AND',
				array(
					'key'     => '_price', // 價格自定義欄位
					'value'   => $args['price_range'] ?? array( 0, 10000000 ), // 設定價格範圍
					'compare' => 'BETWEEN', // 在此範圍之間
					'type'    => 'DECIMAL', // 處理為數值
				),
			);
			unset( $args['price_range'] );
		}

		$results     = \wc_get_products( $args );
		$total       = $results->total;
		$total_pages = $results->max_num_pages;

		$products = $results->products;

		$formatted_products = array_map( array( $this, 'format_product_details' ), $products );

		$response = new \WP_REST_Response( $formatted_products );

		// set pagination in header
		$response->header( 'X-WP-Total', $total );
		$response->header( 'X-WP-TotalPages', $total_pages );

		return $response;
	}

	/**
	 * Format product details
	 * TODO
	 *
	 * @see https://www.businessbloomer.com/woocommerce-easily-get-product-info-title-sku-desc-product-object/
	 *
	 * @param \WC_Product $product Product.
	 * @param bool        $with_description With description.
	 * @return array
	 */
	public function format_product_details( $product , $with_description = true) { // phpcs:ignore

		if ( ! ( $product instanceof \WC_Product ) ) {
			return array();
		}

		$date_created  = $product?->get_date_created();
		$date_modified = $product?->get_date_modified();

		$image_id  = $product?->get_image_id();
		$image_url = \wp_get_attachment_url( $image_id );

		$gallery_image_ids  = $product?->get_gallery_image_ids();
		$gallery_image_urls = array_map( 'wp_get_attachment_url', $gallery_image_ids );

		$description_array = $with_description ? array(
			'description'       => $product?->get_description(),
			'short_description' => $product?->get_short_description(),
		) : array();

		$low_stock_amount = ( '' === $product?->get_low_stock_amount() ) ? null : $product?->get_low_stock_amount();

		$variation_ids = $product?->get_children(); // get variations
		$children      = array();
		if ( ! empty( $variation_ids ) ) {
			$variation_products = array_map( 'wc_get_product', $variation_ids );
			$children_details   = array_map( array( $this, 'format_product_details' ), $variation_products );
			$children           = array(
				'children'  => $children_details,
				'parent_id' => (string) $product?->get_id(),
			);
		}

		$attributes = $product?->get_attributes(); // get attributes object

		$attributes_arr = array();

		foreach ( $attributes as $key => $attribute ) {
			if ( $attribute instanceof \WC_Product_Attribute ) {
				$attributes_arr[] = array(
					'name'     => $attribute?->get_name(),
					'options'  => $attribute?->get_options(),
					'position' => $attribute?->get_position(),
				);
			}

			if ( is_string( $key ) && is_string( $attribute ) ) {
				$attributes_arr[ urldecode( $key ) ] = $attribute;
			}
		}

		$base_array = array(
			// Get Product General Info
			'id'                 => (string) $product?->get_id(),
			'type'               => $product?->get_type(),
			'name'               => $product?->get_name(),
			'slug'               => $product?->get_slug(),
			'date_created'       => $date_created->date( 'Y-m-d H:i:s' ),
			'date_modified'      => $date_modified->date( 'Y-m-d H:i:s' ),
			'status'             => $product?->get_status(),
			'featured'           => $product?->get_featured(),
			'catalog_visibility' => $product?->get_catalog_visibility(),
			'sku'                => $product?->get_sku(),
			// 'menu_order'         => $product?->get_menu_order(),
			'virtual'            => $product?->get_virtual(),
			'downloadable'       => $product?->get_downloadable(),
			'permalink'          => get_permalink( $product?->get_id() ),

			// Get Product Prices
			'price_html'         => $product?->get_price_html(),
			'regular_price'      => $product?->get_regular_price(),
			'sale_price'         => $product?->get_sale_price(),
			'on_sale'            => $product?->is_on_sale(),
			'date_on_sale_from'  => $product?->get_date_on_sale_from(),
			'date_on_sale_to'    => $product?->get_date_on_sale_to(),
			'total_sales'        => $product?->get_total_sales(),

			// Get Product Stock
			'stock'              => $product?->get_stock_quantity(),
			'stock_status'       => $product?->get_stock_status(),
			'manage_stock'       => $product?->get_manage_stock(),
			'stock_quantity'     => $product?->get_stock_quantity(),
			'backorders'         => $product?->get_backorders(),
			'backorders_allowed' => $product?->backorders_allowed(),
			'backordered'        => $product?->is_on_backorder(),
			'low_stock_amount'   => $low_stock_amount,

			// Get Linked Products
			'upsell_ids'         => array_map( 'strval', $product?->get_upsell_ids() ),
			'cross_sell_ids'     => array_map( 'strval', $product?->get_cross_sell_ids() ),

			// Get Product Variations and Attributes
			'attributes'         => $attributes_arr,

			// Get Product Taxonomies
			'category_ids'       => array_map( 'strval', $product?->get_category_ids() ),
			'tag_ids'            => array_map( 'strval', $product?->get_tag_ids() ),

			// Get Product Images
			'image_url'          => $image_url,
			'gallery_image_urls' => $gallery_image_urls,

			'is_course'          => $product?->get_meta( '_' . AdminProduct::PRODUCT_OPTION_NAME ),
		) + $children;

		return array_merge(
			$description_array,
			$base_array
		);
	}
}

Course::instance();
