<?php
/**
 * Product API
 */

declare(strict_types=1);

namespace J7\PowerCourse\Api;

use J7\PowerCourse\Plugin;
use J7\PowerCourse\Admin\Product as AdminProduct;
use J7\WpUtils\Classes\WP;
use J7\PowerBundleProduct\BundleProduct;


/**
 * Class Api
 */
final class Product {
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
			'endpoint' => 'products',
			'method'   => 'get',
		],
		[
			'endpoint' => 'bundle_products',
			'method'   => 'post',
		],
		[
			'endpoint' => 'bundle_products/(?P<id>\d+)',
			'method'   => 'post',
		],
	];

	/**
	 * Constructor.
	 */
	public function __construct() {
		\add_action( 'rest_api_init', [ $this, 'register_api_products' ] );
	}

	/**
	 * Register products API
	 *
	 * @return void
	 */
	public function register_api_products(): void {
		$this->register_apis(
			apis: $this->apis,
			namespace: Plugin::$kebab,
			default_permission_callback: fn() => \current_user_can( 'manage_options' ),
		);
	}


	/**
	 * Get products callback
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function get_products_callback( $request ) { // phpcs:ignore

		$params = $request->get_query_params() ?? [];

		$params = WP::sanitize_text_field_deep( $params, false );

		$default_args = [
			'status'         => [ 'publish', 'draft' ],
			'paginate'       => true,
			'posts_per_page' => 10,
			'paged'          => 1,
			'orderby'        => 'date',
			'order'          => 'DESC',
		];

		$args = \wp_parse_args(
			$params,
			$default_args,
		);

		if ( isset( $args['price_range'] ) ) {
			$args['meta_query'] = [
				'relation' => 'AND',
				[
					'key'     => '_price', // 價格自定義欄位
					'value'   => $args['price_range'] ?? [ 0, 10000000 ], // 設定價格範圍
					'compare' => 'BETWEEN', // 在此範圍之間
					'type'    => 'DECIMAL', // 處理為數值
				],
			];
			unset( $args['price_range'] );
		}

		$results = \wc_get_products( $args );

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
	 * @return array
	 */
	public function format_product_details( $product , $with_description = true) { // phpcs:ignore

		if ( ! ( $product instanceof \WC_Product ) ) {
			return [];
		}

		$date_created  = $product?->get_date_created();
		$date_modified = $product?->get_date_modified();

		$image_id          = $product->get_image_id();
		$gallery_image_ids = $product->get_gallery_image_ids();

		$image_ids = [ $image_id, ...$gallery_image_ids ];
		$images    = array_map( [ WP::class, 'get_image_info' ], $image_ids );

		$description_array = $with_description ? [
			'description'       => $product?->get_description(),
			'short_description' => $product?->get_short_description(),
		] : [];

		$low_stock_amount = ( '' === $product?->get_low_stock_amount() ) ? null : $product?->get_low_stock_amount();

		$variation_ids = $product?->get_children(); // get variations
		$children      = [];
		if ( ! empty( $variation_ids ) ) {
			$variation_products = array_map( 'wc_get_product', $variation_ids );
			$children_details   = array_map( [ $this, 'format_product_details' ], $variation_products );
			$children           = [
				'children'  => $children_details,
				'parent_id' => (string) $product?->get_id(),
			];
		}

		$attributes = $product?->get_attributes(); // get attributes object

		$attributes_arr = [];

		foreach ( $attributes as $key => $attribute ) {
			if ( $attribute instanceof \WC_Product_Attribute ) {
				$attributes_arr[] = [
					'name'     => $attribute?->get_name(),
					'options'  => $attribute?->get_options(),
					'position' => $attribute?->get_position(),
				];
			}

			if ( is_string( $key ) && is_string( $attribute ) ) {
				$attributes_arr[ urldecode( $key ) ] = $attribute;
			}
		}
		$include_product_ids        = (array) \get_post_meta( $product?->get_id(), BundleProduct::INCLUDE_PRODUCT_IDS_META_KEY );
		$unique_include_product_ids = array_values(array_unique( $include_product_ids )); // 確保不會因為重複的 meta_value，使得meta_key 不連續，導致在前端應該顯示為 array 的資料變成 object

		$base_array = [
			// Get Product General Info
			'id'                                        => (string) $product?->get_id(),
			'type'                                      => $product?->get_type(),
			'name'                                      => $product?->get_name(),
			'slug'                                      => $product?->get_slug(),
			'date_created'                              => $date_created->date( 'Y-m-d H:i:s' ),
			'date_modified'                             => $date_modified->date( 'Y-m-d H:i:s' ),
			'status'                                    => $product?->get_status(),
			'featured'                                  => $product?->get_featured(),
			'catalog_visibility'                        => $product?->get_catalog_visibility(),
			'sku'                                       => $product?->get_sku(),
			// 'menu_order'         => $product?->get_menu_order(),
			'virtual'                                   => $product?->get_virtual(),
			'downloadable'                              => $product?->get_downloadable(),
			'permalink'                                 => get_permalink( $product?->get_id() ),

			// Get Product Prices
			'price_html'                                => $product?->get_price_html(),
			'regular_price'                             => $product?->get_regular_price(),
			'sale_price'                                => $product?->get_sale_price(),
			'on_sale'                                   => $product?->is_on_sale(),
			'date_on_sale_from'                         => $product?->get_date_on_sale_from(),
			'date_on_sale_to'                           => $product?->get_date_on_sale_to(),
			'total_sales'                               => $product?->get_total_sales(),

			// Get Product Stock
			'stock'                                     => $product?->get_stock_quantity(),
			'stock_status'                              => $product?->get_stock_status(),
			'manage_stock'                              => $product?->get_manage_stock(),
			'stock_quantity'                            => $product?->get_stock_quantity(),
			'backorders'                                => $product?->get_backorders(),
			'backorders_allowed'                        => $product?->backorders_allowed(),
			'backordered'                               => $product?->is_on_backorder(),
			'low_stock_amount'                          => $low_stock_amount,

			// Get Linked Products
			'upsell_ids'                                => array_map( 'strval', $product?->get_upsell_ids() ),
			'cross_sell_ids'                            => array_map( 'strval', $product?->get_cross_sell_ids() ),

			// Get Product Variations and Attributes
			'attributes'                                => $attributes_arr,

			// Get Product Taxonomies
			'category_ids'                              => array_map( 'strval', $product->get_category_ids() ),
			'tag_ids'                                   => array_map( 'strval', $product->get_tag_ids() ),

			// Get Product Images
			'images'                                    => $images,

			// PENDING meta data
			// 'meta_data'          => WC::get_formatted_meta_data( $product ),

			'is_course'                                 => $product->get_meta( '_' . AdminProduct::PRODUCT_OPTION_NAME ),
			'parent_id'                                 => (string) $product->get_parent_id(),

			// Bundle 商品包含的商品 ids
			BundleProduct::INCLUDE_PRODUCT_IDS_META_KEY => (array) $unique_include_product_ids,

			'sale_date_range'                           => [ (int) $product->get_meta( 'sale_from' ), (int) $product->get_meta( 'sale_to' ) ],
			'is_free'                                   => (string) $product->get_meta( 'is_free' ),
			'qa_list'                                   => [],

		] + $children;

		return array_merge(
			$description_array,
			$base_array
		);
	}

	/**
	 * Post bundle product callback (bundle product)
	 * 新增 bundle product
	 * 用 form-data 方式送出
	 *
	 * @see https://rudrastyh.com/woocommerce/create-product-programmatically.html
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function post_bundle_products_callback( $request ) {

		$body_params = $request->get_body_params() ?? [];
		$file_params = $request->get_file_params();

		$body_params = WP::sanitize_text_field_deep( $body_params );

		$product = new BundleProduct();

		[
			'data' => $data,
			'meta_data' => $meta_data,
			] = WP::separator( args: $body_params, obj: 'product', files: $file_params['files'] ?? [] );

		foreach ( $data as $key => $value ) {
			$method_name = 'set_' . $key;
			$product->$method_name( $value );
		}

		$product->save();

		$meta_data = self::handle_special_fields( $meta_data, $product );

		foreach ( $meta_data as $key => $value ) {
			$product->update_meta_data( $key, $value );
		}

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
	 * Patch bundle product callback (bundle product)
	 * 修改 bundle product
	 * 用 form-data 方式送出
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function post_bundle_products_with_id_callback( $request ) {
		$id          = $request['id'];
		$body_params = $request->get_body_params() ?? [];
		$file_params = $request->get_file_params();

		$body_params = WP::sanitize_text_field_deep( $body_params );

		$product = \wc_get_product( $id );
		if ( ! $product ) {
			return new \WP_REST_Response(
				[
					'code'    => 'patch_failed',
					'message' => '修改失敗，找不到商品',
					'data'    => [
						'id' => (string) $id,
					],
				],
				400
			);
		}
		$product = new BundleProduct( $product );

		[
			'data' => $data,
			'meta_data' => $meta_data,
			] = WP::separator( args: $body_params, obj: 'product', files: $file_params['files'] ?? [] );

		foreach ( $data as $key => $value ) {
			$method_name = 'set_' . $key;
			$product->$method_name( $value );
		}

		$product->save();

		$meta_data = self::handle_special_fields( $meta_data, $product );

		foreach ( $meta_data as $key => $value ) {
			$product->update_meta_data( $key, $value );
		}

		$product->save_meta_data();

		return new \WP_REST_Response(
			[
				'code'    => 'patch_success',
				'message' => '修改成功',
				'data'    => [
					'id' => (string) $id,
				],
			]
		);
	}


	/**
	 * 針對特殊欄位處理
	 * 'pbp_product_ids'
	 * TODO 'product_type'
	 * 前端送出資料後，要存入 WP 前處理
	 *
	 * @param array $meta_data Meta data.
	 * @param mixed $product Product.
	 * @return array
	 */
	public static function handle_special_fields( $meta_data, $product ) {

		$meta_key = BundleProduct::INCLUDE_PRODUCT_IDS_META_KEY;

		if ( isset( $meta_data[ $meta_key ] ) && is_array( $meta_data[ $meta_key ] ) ) {
			// 先刪除原本的 meta data
			$product->delete_meta_data( $meta_key );
			foreach ( $meta_data[ $meta_key ] as $pbp_product_id ) {
				$product->add_meta_data( BundleProduct::INCLUDE_PRODUCT_IDS_META_KEY, $pbp_product_id, unique:false );
			}
			$product->save_meta_data();
			unset( $meta_data[ $meta_key ] );
		}

		if ( isset( $meta_data['product_type'] ) ) {
			unset( $meta_data['product_type'] );
		}

		return $meta_data;
	}
}

Product::instance();
