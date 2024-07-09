<?php
/**
 * Course API
 */

declare( strict_types=1 );

namespace J7\PowerCourse\Api;

use J7\PowerCourse\Admin\Product as AdminProduct;
use J7\PowerCourse\Plugin;
use J7\PowerCourse\Resources\Chapter\ChapterFactory;
use J7\PowerCourse\Resources\Chapter\RegisterCPT;
use J7\PowerCourse\Utils\Course as CourseUtils;
use J7\WpUtils\Classes\WC;
use J7\WpUtils\Classes\WP;


/**
 * Class Course
 */
final class Course {
	use \J7\WpUtils\Traits\SingletonTrait;
	use \J7\WpUtils\Traits\ApiRegisterTrait;

	/**
	 * APIs
	 *
	 * @var array
	 * - endpoint: string
	 * - method: 'get' | 'post' | 'patch' | 'delete'
	 * - permission_callback : callable
	 */
	protected $apis = [
		[
			'endpoint' => 'courses',
			'method'   => 'get',
		],
		[
			'endpoint' => 'courses',
			'method'   => 'post',
		],
		[
			'endpoint' => 'courses/(?P<id>\d+)',
			'method'   => 'post',
		],
		[
			'endpoint' => 'courses/(?P<id>\d+)',
			'method'   => 'delete',
		],
		[
			'endpoint' => 'terms',
			'method'   => 'get',
		],
		[
			'endpoint' => 'options',
			'method'   => 'get',
		],
	];

	/**
	 * Constructor.
	 */
	public function __construct() {
		\add_action( 'rest_api_init', [ $this, 'register_api_course' ] );
	}

	/**
	 * Register Course API
	 *
	 * @return void
	 */
	public function register_api_course(): void {
		$this->register_apis(
			apis: $this->apis,
			namespace: Plugin::$kebab,
			default_permission_callback: fn() => \current_user_can( 'manage_options' ),
		);
	}


	/**
	 * Get courses callback
	 * 當商品是 "課程" 時，才會被抓出來
	 *
	 * @param \WP_REST_Request $request Request.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_courses_callback( $request ) { // phpcs:ignore

		$params = $request->get_query_params() ?? [];

		$params = array_map( [ WP::class, 'sanitize_text_field_deep' ], $params );

		$default_args = [
			'status'         => [ 'publish', 'draft' ],
			'paginate'       => true,
			'posts_per_page' => 10,
			'page'           => 1,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'is_course'      => true,
		];

		$args = \wp_parse_args(
			$params,
			$default_args,
		);

		if ( isset( $args['price_range'] ) ) {
			$args['meta_query'] = [
				'relation' => 'AND',
				[
					'key'     => '_price',                                // 價格自定義欄位
					'value'   => $args['price_range'] ?? [ 0, 10000000 ], // 設定價格範圍
					'compare' => 'BETWEEN',                               // 在此範圍之間
					'type'    => 'DECIMAL',                               // 處理為數值
				],
			];
			unset( $args['price_range'] );
		}

		$results     = \wc_get_products( $args );
		$total       = $results->total;
		$total_pages = $results->max_num_pages;

		$products = $results->products;

		$formatted_products = array_map( [ $this, 'format_product_details' ], $products );

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
	 *
	 * @return array
	 */
	public function format_product_details( $product, $with_description = true ) { // phpcs:ignore

		if ( ! ( $product instanceof \WC_Product ) ) {
			return [];
		}

		$date_created  = $product->get_date_created();
		$date_modified = $product->get_date_modified();

		$image_id          = $product->get_image_id();
		$gallery_image_ids = $product->get_gallery_image_ids();

		$image_ids = [ $image_id, ...$gallery_image_ids ];
		$images    = array_map( [ WP::class, 'get_image_info' ], $image_ids );

		$description_array = $with_description ? [
			'description'       => $product->get_description(),
			'short_description' => $product->get_short_description(),
		] : [];

		$low_stock_amount = ( '' === $product->get_low_stock_amount() ) ? null : $product->get_low_stock_amount();

		$chapters = array_values(
			\get_children(
				[
					'post_parent' => $product->get_id(),
					'post_type'   => RegisterCPT::POST_TYPE,
					'numberposts' => - 1,
					'post_status' => 'any', // TODO
					'orderby'     => 'menu_order',
					'order'       => 'ASC',
				]
			)
		);
		$chapters = array_map( [ ChapterFactory::class, 'format_chapter_details' ], $chapters );

		$children = ! ! $chapters ? [
			'chapters' => $chapters,
		] : [];

		$attributes = $product->get_attributes(); // get attributes object

		$attributes_arr = [];

		foreach ( $attributes as $key => $attribute ) {
			if ( $attribute instanceof \WC_Product_Attribute ) {
				$attributes_arr[] = [
					'name'     => $attribute->get_name(),
					'options'  => $attribute->get_options(),
					'position' => $attribute->get_position(),
				];
			}

			if ( is_string( $key ) && is_string( $attribute ) ) {
				$attributes_arr[ urldecode( $key ) ] = $attribute;
			}
		}

		$bundle_ids = CourseUtils::get_bundles_by_product( $product->get_id(), return_ids: true);

		$base_array = [
			// Get Product General Info
			'id'                  => (string) $product->get_id(),
			'type'                => $product->get_type(),
			'name'                => $product->get_name(),
			'depth'               => 0,
			'slug'                => $product->get_slug(),
			'date_created'        => $date_created?->date( 'Y-m-d H:i:s' ),
			'date_modified'       => $date_modified?->date( 'Y-m-d H:i:s' ),
			'status'              => $product->get_status(),
			'featured'            => $product->get_featured(),
			'catalog_visibility'  => $product->get_catalog_visibility(),
			'sku'                 => $product->get_sku(),
			'menu_order'          => (int) $product->get_menu_order(),
			'virtual'             => $product->get_virtual(),
			'downloadable'        => $product->get_downloadable(),
			'permalink'           => \get_permalink( $product->get_id() ),
			'average_rating'      => (float) $product->get_average_rating(),
			'review_count'        => (int) $product->get_review_count(),

			// Get Product Prices
			'price_html'          => $product->get_price_html(),
			'regular_price'       => (int) $product->get_regular_price(),
			'sale_price'          => (int) $product->get_sale_price(),
			'on_sale'             => $product->is_on_sale(),
			'date_on_sale_from'   => $product->get_date_on_sale_from(),
			'date_on_sale_to'     => $product->get_date_on_sale_to(),
			'total_sales'         => $product->get_total_sales(),

			// Get Product Stock
			'stock'               => $product->get_stock_quantity(),
			'stock_status'        => $product->get_stock_status(),
			'manage_stock'        => $product->get_manage_stock(),
			'stock_quantity'      => $product->get_stock_quantity(),
			'backorders'          => $product->get_backorders(),
			'backorders_allowed'  => $product->backorders_allowed(),
			'backordered'         => $product->is_on_backorder(),
			'low_stock_amount'    => $low_stock_amount,

			// Get Linked Products
			'upsell_ids'          => array_map( 'strval', $product->get_upsell_ids() ),
			'cross_sell_ids'      => array_map( 'strval', $product->get_cross_sell_ids() ),

			// Get Product Variations and Attributes
			'attributes'          => $attributes_arr,

			// Get Product Taxonomies
			'category_ids'        => array_map( 'strval', $product->get_category_ids() ),
			'tag_ids'             => array_map( 'strval', $product->get_tag_ids() ),

			// Get Product Images
			'images'              => $images,

			// PENDING meta data
			// 'meta_data'          => WC::get_formatted_meta_data( $product ),

			'is_course'           => (string) $product->get_meta( '_' . AdminProduct::PRODUCT_OPTION_NAME ),
			'parent_id'           => (string) $product->get_parent_id(),
			'is_free'             => (string) $product->get_meta( 'is_free' ),
			'qa_list'             => (array) $product->get_meta( 'qa_list' ),
			'sub_title'           => (string) $product->get_meta( 'sub_title' ),
			'course_schedule'     => (int) $product->get_meta( 'course_schedule' ),
			'course_hour'         => (int) $product->get_meta( 'course_hour' ),
			'course_minute'       => (int) $product->get_meta( 'course_minute' ),
			'limit_type'          => (string) $product->get_meta( 'limit_type' ),
			'is_popular'          => (string) $product->get_meta( 'is_popular' ),
			'extra_student_count' => (int) $product->get_meta( 'extra_student_count' ),
			'enable_review'       => (string) $product->get_meta( 'enable_review' ),
			'enable_comment'      => (string) $product->get_meta( 'enable_comment' ),
			'limit_value'         => (int) $product->get_meta( 'limit_value' ),
			'limit_unit'          => (string) $product->get_meta( 'limit_unit' ),
			'feature_video'       => (string) $product->get_meta( 'feature_video' ),
			'trial_video'         => (string) $product->get_meta( 'trial_video' ),
			// bundle product
			'bundle_ids'          => $bundle_ids,
		] + $children;

		return array_merge(
			$description_array,
			$base_array
		);
	}

	/**
	 * Post courses callback
	 *
	 * @see https://rudrastyh.com/woocommerce/create-product-programmatically.html
	 *
	 * @param \WP_REST_Request $request Request.
	 *
	 * @return \WP_REST_Response
	 */
	public function post_courses_callback( $request ) {
		$body_params = $request->get_body_params() ?? [];
		$file_params = $request->get_file_params();

		$body_params = array_map( [ WP::class, 'sanitize_text_field_deep' ], $body_params );

		$product = new \WC_Product_Simple();

		[
			'data'      => $data,
			'meta_data' => $meta_data,
		] = WP::separator( args: $body_params, obj: 'product', files: $file_params['files'] ?? [] );

		foreach ( $data as $key => $value ) {
			$method_name = 'set_' . $key;
			$product->$method_name( $value );
		}

		$product->save();

		foreach ( $meta_data as $key => $value ) {
			$product->update_meta_data( $key, $value );
		}

		$product->update_meta_data( '_' . AdminProduct::PRODUCT_OPTION_NAME, 'yes' );

		$product->save_meta_data();

		return new \WP_REST_Response(
			[
				'code'    => 'create_success',
				'message' => '新增成功',
				'data'    => [
					'id' => (string) $product->get_id(),
				],
			]
		);
	}

	/**
	 * Post courses with id callback
	 * 更新課程
	 *
	 * @param \WP_REST_Request<array{'id': string}> $request Request.
	 *
	 * @return \WP_REST_Response
	 */
	public function post_courses_with_id_callback( \WP_REST_Request $request ):\WP_REST_Response { // phpcs:ignore
		$id = $request['id'];
		if ( !$id ) {
			return new \WP_REST_Response(
				[
					'code'    => 'id_not_provided',
					'message' => '更新失敗，請提供ID',
					'data'    => null,
				],
				400
			);
		}
		$body_params = $request->get_body_params();
		$file_params = $request->get_file_params();

		$body_params = array_map( [ WP::class, 'sanitize_text_field_deep' ], $body_params );

		$product = \wc_get_product( $id );

		if ( ! $product ) {
			return new \WP_REST_Response(
				[
					'code'    => 'product_not_found',
					'message' => '更新失敗，找不到商品',
					'data'    => [
						'id' => $id,
					],
				],
				400
			);
		}

		[
			'data'      => $data,
			'meta_data' => $meta_data,
		] = WP::separator( args: $body_params, obj: 'product', files: $file_params['files'] ?? [] );

		foreach ( $data as $key => $value ) {
			$method_name = 'set_' . $key;
			$product->$method_name( $value );
		}

		$product->save();

		foreach ( $meta_data as $key => $value ) {
			$product->update_meta_data( $key, $value );
		}

		$product->update_meta_data( '_' . AdminProduct::PRODUCT_OPTION_NAME, 'yes' );

		$product->save_meta_data();

		return new \WP_REST_Response(
			[
				'code'    => 'update_success',
				'message' => '更新成功',
				'data'    => [
					'id' => $id,
				],
			]
		);
	}


	/**
	 * Delete courses with id callback
	 *
	 * @param \WP_REST_Request $request Request.
	 *
	 * @return \WP_REST_Response
	 */
	public function delete_courses_with_id_callback( $request ) {
		$id = $request['id'];
		if ( empty( $id ) ) {
			return new \WP_REST_Response(
				[
					'code'    => 'id_not_provided',
					'message' => '刪除失敗，請提供ID',
					'data'    => null,
				],
				400
			);
		}

		$delete_result = \wp_delete_post( $id );

		if ( ! $delete_result ) {
			return new \WP_REST_Response(
				[
					'code'    => 'delete_failed',
					'message' => '刪除失敗',
					'data'    => $delete_result,
				],
				400
			);
		}

		return new \WP_REST_Response(
			[
				'code'    => 'delete_success',
				'message' => '刪除成功',
				'data'    => [
					'id' => $id,
				],
			]
		);
	}

	/**
	 * Get terms callback
	 *
	 * @see https://developer.wordpress.org/reference/functions/get_terms/
	 *
	 * @param \WP_REST_Request $request Request.
	 *
	 * @return array
	 */
	public function get_terms_callback( $request ) { // phpcs:ignore

		$params = $request?->get_query_params() ?? [];

		$params = array_map( [ WP::class, 'sanitize_text_field_deep' ], $params );

		// it seems no need to add post_per_page, get_terms will return all terms
		$default_args = [
			'taxonomy'   => 'product_cat',
			'fields'     => 'id=>name',
			'hide_empty' => true,
			'orderby'    => 'name',
			'order'      => 'ASC',
		];

		$args = \wp_parse_args(
			$params,
			$default_args,
		);

		$terms = \get_terms( $args );

		$formatted_terms = array_map( [ $this, 'format_terms' ], array_keys( $terms ), array_values( $terms ) );

		return $formatted_terms;
	}

	/**
	 * Format terms
	 *
	 * @param string $key Key.
	 * @param string $value Value.
	 *
	 * @return array
	 */
	public function format_terms( $key, $value ) {
		return [
			'id'   => (string) $key,
			'name' => $value,
		];
	}


	/**
	 * Get options callback
	 *
	 * @param \WP_REST_Request $request Request.
	 *
	 * @return array
	 */
	public function get_options_callback( $request ) { // phpcs:ignore

		// it seems no need to add post_per_page, get_terms will return all terms
		$cat_args = [
			'taxonomy'   => 'product_cat',
			'fields'     => 'id=>name',
			'hide_empty' => true,
			'orderby'    => 'name',
			'order'      => 'ASC',
		];
		$cats     = \get_terms( $cat_args );

		$formatted_cats = array_map( [ $this, 'format_terms' ], array_keys( $cats ), array_values( $cats ) );

		$tag_args = [
			'taxonomy'   => 'product_tag',
			'fields'     => 'id=>name',
			'hide_empty' => true,
			'orderby'    => 'name',
			'order'      => 'ASC',
		];

		$tags = \get_terms( $tag_args );

		$formatted_tags = array_map( [ $this, 'format_terms' ], array_keys( $tags ), array_values( $tags ) );

		$top_sales_products = WC::get_top_sales_products( 5 );

		[
			'max_price' => $max_price,
			'min_price' => $min_price,
		] = self::get_max_min_prices();

		return [
			'product_cats'       => $formatted_cats,
			'product_tags'       => $formatted_tags,
			'top_sales_products' => $top_sales_products,
			'max_price'          => (int) $max_price,
			'min_price'          => (int) $min_price,
		];
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
			[
				'order'    => 'DESC', // 遞減排序
				'orderby'  => 'meta_value_num',
				'meta_key' => '_price',
				'limit'    => 1,         // 僅獲取一個結果
				'status'   => 'publish', // 僅包含已發佈的商品
			]
		);
		$max_price          = 0;
		if ( ! empty( $max_price_products ) ) {
			$max_price_product = reset( $max_price_products );     // 獲取第一個元素
			$max_price         = $max_price_product?->get_price(); // 獲取最高價格
		}

		// 獲取最低價格的商品
		$min_price_products = \wc_get_products(
			[
				'order'    => 'ASC', // 遞增排序
				'orderby'  => 'meta_value_num',
				'meta_key' => '_price',
				'limit'    => 1,         // 僅獲取一個結果
				'status'   => 'publish', // 僅包含已發佈的商品
			]
		);

		$min_price = 0;
		if ( ! empty( $min_price_products ) ) {
			$min_price_product = reset( $min_price_products );     // 獲取第一個元素
			$min_price         = $min_price_product?->get_price(); // 獲取最低價格
		}

		$max_min_prices = [
			'max_price' => $max_price,
			'min_price' => $min_price,
		];

		\set_transient( $transient_key, $max_min_prices, 1 * HOUR_IN_SECONDS );

		return $max_min_prices;
	}
}

Course::instance();
