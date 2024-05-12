<?php
/**
 * Product API
 */

declare(strict_types=1);

namespace J7\PowerCourse\Api;

use J7\PowerCourse\Plugin;
use J7\PowerCourse\Utils\Base;
use J7\PowerCourse\Admin\Product as AdminProduct;
use Micropackage\Singleton\Singleton;


/**
 * Class Api
 */
final class Product extends Singleton {

	/**
	 * Constructor.
	 */
	public function __construct() {
		\add_filter( 'woocommerce_product_data_store_cpt_get_products_query', array( $this, 'handle_custom_query_var' ), 10, 2 );
		\add_action( 'rest_api_init', array( $this, 'register_api_products' ) );
	}

	/**
	 * Handle a custom 'customvar' query var to get products with the 'customvar' meta.
	 * 新增篩選 option name 的商品
	 *
	 * @param array $query - Args for WP_Query.
	 * @param array $query_vars - Query vars from WC_Product_Query.
	 * @return array modified $query
	 */
	public function handle_custom_query_var( $query, $query_vars ) {

		$option_name = $query_vars[ AdminProduct::PRODUCT_OPTION_NAME ] ?? '';
		if ( '' !== $option_name ) {
			if ( '1' === (string) $option_name ) {
				$query['meta_query'][] = array(
					'key'   => '_' . AdminProduct::PRODUCT_OPTION_NAME,
					'value' => 'yes',
				);
			} elseif ( '0' === (string) $option_name ) {
				$query['meta_query'] = array(
					'relation'     => 'OR',
					'value_clause' => array(
						'key'   => '_' . AdminProduct::PRODUCT_OPTION_NAME,
						'value' => 'no',
					),
					'field_clause' => array(
						'key'     => '_' . AdminProduct::PRODUCT_OPTION_NAME,
						'compare' => 'NOT EXISTS',
					),
				);

			}
		}

		return $query;
	}

	/**
	 * Register products API
	 *
	 * @return void
	 */
	public function register_api_products(): void {

		$apis = array(
			array(
				'endpoint' => 'products',
				'method'   => 'get',
			),
			array(
				'endpoint' => 'terms',
				'method'   => 'get',
			),
			array(
				'endpoint' => 'options',
				'method'   => 'get',
			),
		);

		foreach ( $apis as $api ) {
			\register_rest_route(
				Plugin::KEBAB,
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
	 * Get products callback
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function get_products_callback( $request ) { // phpcs:ignore

		$params = $request?->get_query_params() ?? array();

		$params = array_map( array( 'J7\PowerCourse\Utils\Base', 'sanitize_text_field_deep' ), $params );

		$default_args = array(
			'status'         => 'publish',
			'paginate'       => true,
			'posts_per_page' => 10,
			'page'           => 1,
			'orderby'        => 'date',
			'order'          => 'DESC',
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

	/**
	 * Get terms callback
	 *
	 * @see https://developer.wordpress.org/reference/functions/get_terms/
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return array
	 */
	public function get_terms_callback( $request ) { // phpcs:ignore

		$params = $request?->get_query_params() ?? array();

		$params = array_map( array( 'J7\PowerCourse\Utils', 'sanitize_text_field_deep' ), $params );

		// it seems no need to add post_per_page, get_terms will return all terms
		$default_args = array(
			'taxonomy'   => 'product_cat',
			'fields'     => 'id=>name',
			'hide_empty' => true,
			'orderby'    => 'name',
			'order'      => 'ASC',
		);

		$args = \wp_parse_args(
			$params,
			$default_args,
		);

		$terms = \get_terms( $args );

		$formatted_terms = array_map( array( $this, 'format_terms' ), array_keys( $terms ), array_values( $terms ) );

		return $formatted_terms;
	}

	/**
	 * Format terms
	 *
	 * @param string $key Key.
	 * @param string $value Value.
	 * @return array
	 */
	public function format_terms( $key, $value ) {
		return array(
			'id'   => (string) $key,
			'name' => $value,
		);
	}


	/**
	 * Get options callback
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return array
	 */
	public function get_options_callback( $request ) { // phpcs:ignore

		// it seems no need to add post_per_page, get_terms will return all terms
		$cat_args = array(
			'taxonomy'   => 'product_cat',
			'fields'     => 'id=>name',
			'hide_empty' => true,
			'orderby'    => 'name',
			'order'      => 'ASC',
		);
		$cats     = \get_terms( $cat_args );

		$formatted_cats = array_map( array( $this, 'format_terms' ), array_keys( $cats ), array_values( $cats ) );

		$tag_args = array(
			'taxonomy'   => 'product_tag',
			'fields'     => 'id=>name',
			'hide_empty' => true,
			'orderby'    => 'name',
			'order'      => 'ASC',
		);

		$tags = \get_terms( $tag_args );

		$formatted_tags = array_map( array( $this, 'format_terms' ), array_keys( $tags ), array_values( $tags ) );

		$top_sales_products = Base::get_top_sales_products( 5 );

		[
			'max_price' => $max_price,
			'min_price' => $min_price,
		] = self::get_max_min_prices();

		return array(
			'product_cats'       => $formatted_cats,
			'product_tags'       => $formatted_tags,
			'top_sales_products' => $top_sales_products,
			'max_price'          => (int) $max_price,
			'min_price'          => (int) $min_price,
		);
	}

	/**
	 * Get Max Min Price
	 *
	 * @return array
	 */
	public static function get_max_min_prices(): array {
		$transient_key = 'max_min_prices';

		$max_min_prices = \get_transient( $transient_key );

		if ( false !== $max_min_prices ) {
			return $max_min_prices;
		}
		// 獲取最高價格的商品
		$max_price_products = \wc_get_products(
			array(
				'order'    => 'DESC', // 遞減排序
				'orderby'  => 'meta_value_num',
				'meta_key' => '_price',
				'limit'    => 1, // 僅獲取一個結果
				'status'   => 'publish', // 僅包含已發佈的商品
			)
		);
		$max_price          = 0;
		if ( ! empty( $max_price_products ) ) {
			$max_price_product = reset( $max_price_products ); // 獲取第一個元素
			$max_price         = $max_price_product?->get_price(); // 獲取最高價格
		}

		// 獲取最低價格的商品
		$min_price_products = \wc_get_products(
			array(
				'order'    => 'ASC', // 遞增排序
				'orderby'  => 'meta_value_num',
				'meta_key' => '_price',
				'limit'    => 1, // 僅獲取一個結果
				'status'   => 'publish', // 僅包含已發佈的商品
			)
		);

		$min_price = 0;
		if ( ! empty( $min_price_products ) ) {
			$min_price_product = reset( $min_price_products ); // 獲取第一個元素
			$min_price         = $min_price_product?->get_price(); // 獲取最低價格
		}

		$max_min_prices = array(
			'max_price' => $max_price,
			'min_price' => $min_price,
		);

		\set_transient( $transient_key, $max_min_prices, 1 * HOUR_IN_SECONDS );

		return $max_min_prices;
	}
}

Product::get();
