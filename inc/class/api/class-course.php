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
use J7\PowerCourse\Utils\Base;
use J7\PowerCourse\Utils\Course as CourseUtils;
use J7\PowerCourse\Utils\AVLCourseMeta;
use J7\WpUtils\Classes\WC;
use J7\WpUtils\Classes\WP;
use J7\PowerCourse\Resources\Course as CourseResource;



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
	 * @phpstan-ignore-next-line
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
			'endpoint' => 'courses/add-students',
			'method'   => 'post',
		],
		[
			'endpoint' => 'courses/remove-students',
			'method'   => 'post',
		],
		[
			'endpoint' => 'courses/update-students',
			'method'   => 'post',
		],
		[
			'endpoint' => 'courses/(?P<id>\d+)',
			'method'   => 'delete',
		],
		[
			'endpoint' => 'courses/terms',
			'method'   => 'get',
		],
		[
			'endpoint' => 'courses/options',
			'method'   => 'get',
		],
	];

	/**
	 * Constructor.
	 */
	public function __construct() {
		\add_action( 'rest_api_init', [ $this, 'register_api_course' ] );
		\add_filter( 'wp_kses_allowed_html', [ $this, 'extend_wpkses_post_tags' ], 10, 2 );
	}

	/**
	 * 擴展 WordPress 內容安全政策，允許在特定上下文中使用 iframe 標籤。
	 *
	 * 此方法修改了 WordPress 允許的 HTML 標籤列表，專門針對 'product' 上下文，允許在產品描述等地方使用 iframe 標籤。
	 * 這使得用戶可以嵌入例如視頻等外部內容。此方法應該與 'wp_kses_allowed_html' 過濾器一起使用。
	 *
	 * @param array  $tags    原始允許的 HTML 標籤和屬性的列表。
	 * @param string $context 使用場景的上下文標識符。
	 *
	 * @return array 修改後的標籤和屬性列表。
	 * @phpstan-ignore-next-line
	 */
	public function extend_wpkses_post_tags( $tags, $context ) {

		if ( 'post' === $context ) {
			$tags['iframe'] = [
				'src'             => true,
				'height'          => true,
				'width'           => true,
				'frameborder'     => true,
				'allowfullscreen' => true,
			];
		}
		return $tags;
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
	 * @phpstan-ignore-next-line
	 */
	public function get_courses_callback( $request ) { // phpcs:ignore

		$params = $request->get_query_params();

		$params = WP::sanitize_text_field_deep( $params, false );

		$default_args = [
			'status'         => [ 'publish', 'draft' ],
			'paginate'       => true,
			'posts_per_page' => 10,
			'paged'          => 1,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'meta_key'       => '_is_course',
			'meta_value'     => 'yes',
		];

		$args = \wp_parse_args(
			$params,
			$default_args,
		);

		// if ( isset( $args['price_range'] ) ) {
		// $args['meta_query'] = [
		// 'relation' => 'AND',
		// [
		// 'key'     => '_price',                                // 價格自定義欄位
		// 'value'   => $args['price_range'], // 設定價格範圍
		// 'compare' => 'BETWEEN',                               // 在此範圍之間
		// 'type'    => 'DECIMAL',                               // 處理為數值
		// ],
		// ];
		// unset( $args['price_range'] );
		// }

		$results     = \wc_get_products( $args );
		$total       = $results->total;
		$total_pages = $results->max_num_pages;

		$products = $results->products;

		$formatted_products = array_values(array_map( [ $this, 'format_course_details' ], $products ));

		$response = new \WP_REST_Response( $formatted_products );

		// set pagination in header
		$response->header( 'X-WP-Total', $total );
		$response->header( 'X-WP-TotalPages', $total_pages );

		return $response;
	}

	/**
	 * Format course details
	 *
	 * @see https://www.businessbloomer.com/woocommerce-easily-get-product-info-title-sku-desc-product-object/
	 *
	 * @param \WC_Product $product Product.
	 *
	 * @return array
	 * @phpstan-ignore-next-line
	 */
	public function format_course_details( $product ) { // phpcs:ignore

		if ( ! ( $product instanceof \WC_Product ) ) {
			return [];
		}

		$date_created  = $product->get_date_created();
		$date_modified = $product->get_date_modified();

		$image_id          = $product->get_image_id();
		$gallery_image_ids = $product->get_gallery_image_ids();

		$image_ids = $image_id ? [ $image_id, ...$gallery_image_ids ] : [];
		$images    = $image_ids ? array_map( [ WP::class, 'get_image_info' ], $image_ids ) : [];

		$description_array = [
			'description'       => $product->get_description(),
			'short_description' => $product->get_short_description(),
		];

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
		$chapters = array_values(array_map( [ ChapterFactory::class, 'format_chapter_details' ], $chapters ));
		// 把子章節的時間加總
		$course_length = array_reduce(
			$chapters,
			function ( $acc, $chapter ) {
				$sub_chapters       = $chapter['chapters'] ?? [];
				$sub_chapter_length = array_reduce(
					$sub_chapters,
					function ( $acc, $sub_chapter ) {
						return $acc + $sub_chapter['chapter_length'];
					},
					0
					);
				return $acc + $sub_chapter_length;
			},
			0
			);

		$children = ! ! $chapters ? [
			'chapters' => $chapters,
		] : [];

		$bundle_ids = CourseUtils::get_bundles_by_product( $product->get_id(), return_ids: true);

		$regular_price = $product->get_regular_price();
		$sale_price    = $product->get_sale_price();
		$price_html    = Base::get_price_html( $product );

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
			'custom_rating'       => (float) $product->get_meta('custom_rating') ?: 2.5,
			'extra_review_count'  => (int) $product->get_meta('extra_review_count'),
			'purchase_note'       => $product->get_purchase_note(),

			// Get Product Prices
			'price_html'          => $price_html,
			'regular_price'       => $regular_price,
			'sale_price'          => $sale_price,
			'on_sale'             => $product->is_on_sale(),
			'date_on_sale_from'   => $product->get_date_on_sale_from()?->getTimestamp(),
			'date_on_sale_to'     => $product->get_date_on_sale_to()?->getTimestamp(),
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
			'attributes'          => WC::get_product_attribute_array( $product ),

			// Get Product Taxonomies
			'category_ids'        => array_map( 'strval', $product->get_category_ids() ),
			'tag_ids'             => array_map( 'strval', $product->get_tag_ids() ),

			// Get Product Images
			'images'              => $images,

			'is_course'           => (string) $product->get_meta( '_' . AdminProduct::PRODUCT_OPTION_NAME ),
			'parent_id'           => (string) $product->get_parent_id(),
			'is_free'             => (string) $product->get_meta( 'is_free' ),
			'qa_list'             => (array) $product->get_meta( 'qa_list' ),
			'course_schedule'     => (int) $product->get_meta( 'course_schedule' ),
			'course_hour'         => (int) $product->get_meta( 'course_hour' ),
			'course_minute'       => (int) $product->get_meta( 'course_minute' ),
			'limit_type'          => (string) $product->get_meta( 'limit_type' ) ?: 'unlimited',
			'limit_value'         => (int) $product->get_meta( 'limit_value' ) ?: 1,
			'limit_unit'          => (string) $product->get_meta( 'limit_unit' ) ?: 'day',
			'is_popular'          => (string) $product->get_meta( 'is_popular' ),
			'is_featured'         => (string) $product->get_meta( 'is_featured' ),
			'show_review'         => (string) $product->get_meta( 'show_review' ),
			'reviews_allowed'     => (bool) $product->get_reviews_allowed(),
			'show_review_tab'     => (string) $product->get_meta( 'show_review_tab' ),
			'show_review_list'    => (string) $product->get_meta( 'show_review_list' ),
			'show_total_student'  => (string) $product->get_meta( 'show_total_student' ) ?: 'yes',
			'enable_comment'      => (string) $product->get_meta( 'enable_comment' ),
			'extra_student_count' => (int) $product->get_meta( 'extra_student_count' ),
			'feature_video'       => $product->get_meta( 'feature_video' ) ?: [
				'type' => 'youtube',
				'id'   => '',
				'meta' => [],
			],
			'trial_video'         => $product->get_meta( 'trial_video' ) ?: [
				'type' => 'youtube',
				'id'   => '',
				'meta' => [],
			],
			'teacher_ids'         => (array) \get_post_meta( $product->get_id(), 'teacher_ids', false ),
			'course_length'       => $course_length,

			// bundle product
			'bundle_ids'          => $bundle_ids,

		] + $children;

		return array_merge(
			$description_array,
			$base_array
		);
	}

	/**
	 * 處理並分離產品資訊
	 *
	 * 根據請求分離產品資訊，並處理描述欄位。
	 *
	 * @param \WP_REST_Request $request 包含產品資訊的請求對象。
	 * @throws \Exception 當找不到商品時拋出異常。.
	 * @return array{product:\WC_Product, data: array<string, mixed>, meta_data: array<string, mixed>} 包含產品對象、資料和元數據的陣列。
	 * @phpstan-ignore-next-line
	 */
	private function separator( $request ): array {
		$id          = $request['id'] ?? '';
		$body_params = $request->get_body_params();
		$file_params = $request->get_file_params();

		$skip_keys   = [
			'feature_video',
			'trial_video',
			'description',
		];
		$body_params = WP::sanitize_text_field_deep($body_params, true, $skip_keys);

		// 將 '[]' 轉為 []
		$body_params = Base::format_empty_array( $body_params );

		$product = !!$id ? \wc_get_product( $id ) : new \WC_Product_Simple();

		[
			'data'      => $data,
			'meta_data' => $meta_data,
		] = WP::separator( args: $body_params, obj: 'product', files: $file_params['files'] ?? [] );

		if (!$meta_data['files']) {
			unset($meta_data['files']);
		}

		if ( ! $product ) {
			throw new \Exception( '找不到商品' );
		}

		return [
			'product'   => $product,
			'data'      => $data,
			'meta_data' => $meta_data,
		];
	}

	/**
	 * 處理儲存課程資料
	 *
	 * @param \WC_Product          $product 產品物件
	 * @param array<string, mixed> $data 要更新的資料
	 * @return void
	 */
	private function handle_save_course_data( \WC_Product $product, array $data ): void {
		$data['virtual'] = true; // 課程固定為虛擬商品
		foreach ( $data as $key => $value ) {
			$method_name = 'set_' . $key;
			$product->$method_name( $value );
		}

		$product->save();
	}

	/**
	 * 儲存課程的元資料
	 *
	 * @param \WC_Product          $product 代表 WooCommerce 產品的物件
	 * @param array<string, mixed> $meta_data 需要更新的元資料陣列
	 * @return void
	 */
	private function handle_save_course_meta_data( \WC_Product $product, array $meta_data ): void {

		unset( $meta_data['images'] ); // 圖片只做顯示用，不用存
		unset( $meta_data['files'] ); // files 會上傳，不用存

		// 將 teacher_ids, bundle_ids 分離出來，因為要單獨處理，不是直接存 serialized array 進 db
		$array_keys = [ 'teacher_ids', 'bundle_ids' ];
		foreach ($array_keys as $meta_key) {
			$array_value = [];
			if (isset($meta_data[ $meta_key ])) {
				$array_value = $meta_data[ $meta_key ];
				unset($meta_data[ $meta_key ]);
			}

			if (\is_array($array_value)) {
				// 先刪除現有的 teacher_ids
				$product->delete_meta_data($meta_key);

				foreach ($array_value as $meta_value) {
					$product->add_meta_data($meta_key, $meta_value);
				}
			}
		}

		// 最後再來處理剩餘的 meta_data
		foreach ( $meta_data as $key => $value ) {
			$product->update_meta_data( $key, $value );
		}

		$product->update_meta_data( '_' . AdminProduct::PRODUCT_OPTION_NAME, 'yes' );

		$product->save_meta_data();
	}

	/**
	 * Post courses callback
	 *
	 * @see https://rudrastyh.com/woocommerce/create-product-programmatically.html
	 *
	 * @param \WP_REST_Request $request Request.
	 *
	 * @return \WP_REST_Response
	 * @phpstan-ignore-next-line
	 */
	public function post_courses_callback( $request ) {
		[
			'product' => $product,
			'data'      => $data,
			'meta_data' => $meta_data,
		] = $this->separator($request);

		$this->handle_save_course_data($product, $data );
		$this->handle_save_course_meta_data($product, $meta_data );

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
		[
			'product' => $product,
			'data'      => $data,
			'meta_data' => $meta_data,
		] = $this->separator($request);
		$this->handle_save_course_data($product, $data );
		$this->handle_save_course_meta_data($product, $meta_data );

		return new \WP_REST_Response(
			[
				'code'    => 'update_success',
				'message' => '更新成功',
				'data'    => [
					'id' => $product->get_id(),
				],
			]
		);
	}

	/**
	 * 新增學員
	 *
	 * @param \WP_REST_Request<array{'id': string}> $request Request.
	 *
	 * @return \WP_REST_Response
	 */
	public function post_courses_add_students_callback( \WP_REST_Request $request ):\WP_REST_Response { // phpcs:ignore
		$body_params = $request->get_body_params();
		$body_params =WP::sanitize_text_field_deep($body_params, false );

		$user_ids    = $body_params['user_ids'] ?? [];
		$course_ids  = $body_params['course_ids'] ?? [];
		$expire_date = $body_params['expire_date'] ?? 0;

		if (empty($user_ids) || empty($course_ids)) {
			return new \WP_REST_Response(
				[
					'code'    => 'add_students_failed',
					'message' => '新增學員失敗，缺少 user_ids 或 course_ids',
				],
				400
			);
		}

		$success = true;
		foreach ($course_ids as $course_id) {
			foreach ($user_ids as  $user_id) {
				$current_avl_course_ids = (array) \get_user_meta( $user_id, 'avl_course_ids', true );
				if (\in_array($course_id, $current_avl_course_ids)) {
					continue;
				}
				$mid            = \add_user_meta( $user_id, 'avl_course_ids', $course_id, false );
				$update_success = AVLCourseMeta::update( (int) $course_id, (int) $user_id, 'expire_date', $expire_date );
				if (false === $mid || false === $update_success) {
					$success = false;
					break;
				}
			}
		}

		return new \WP_REST_Response(
			[
				'code'    => $success ? 'add_students_success' : 'add_students_failed',
				'message' => $success ? '新增學員成功' : '新增學員失敗',
				'data'    => [
					'user_ids'   => \implode(',', $user_ids),
					'course_ids' => \implode(',', $course_ids),
				],
			],
			$success ? 200 : 400
		);
	}

	/**
	 * 更新學員觀看時間
	 *
	 * @param \WP_REST_Request<array{'id': string}> $request Request.
	 *
	 * @return \WP_REST_Response
	 */
	public function post_courses_update_students_callback( \WP_REST_Request $request ):\WP_REST_Response { // phpcs:ignore
		$body_params = $request->get_body_params();
		$body_params = WP::sanitize_text_field_deep( $body_params, false );
		$user_ids    = $body_params['user_ids'] ?? [];
		$timestamp   = $body_params['timestamp'] ?? 0; // 一般為 10 位數字，如果是0就是無期限
		$course_ids  = $body_params['course_ids'] ?? [];

		$success = true;
		foreach ($course_ids as $course_id) {
			foreach ($user_ids as  $user_id) {
				$success = AVLCourseMeta::update( (int) $course_id, (int) $user_id, 'expire_date', $timestamp );
				if (false === $success ) {
					break;
				}
			}
		}

		return new \WP_REST_Response(
			[
				'code'    => $success ? 'update_students_success' : 'update_students_failed',
				'message' => $success ? '批量調整觀看期限成功' : '批量調整觀看期限失敗',
				'data'    => [
					'user_ids'   => \implode(',', $user_ids),
					'course_ids' => \implode(',', $course_ids),
					'timestamp'  => $timestamp,
				],
			],
			$success ? 200 : 400
		);
	}

	/**
	 * 移除學員
	 *
	 * @param \WP_REST_Request<array{'id': string}> $request Request.
	 *
	 * @return \WP_REST_Response
	 */
	public function post_courses_remove_students_callback( \WP_REST_Request $request ):\WP_REST_Response { // phpcs:ignore
		$body_params = $request->get_body_params();
		$body_params = WP::sanitize_text_field_deep( $body_params, false );
		$user_ids    = $body_params['user_ids'] ?? [];
		$course_ids  = $body_params['course_ids'] ?? [];

		$success = true;
		foreach ($course_ids as $course_id) {
			foreach ($user_ids as $user_id) {
				$success1 = \delete_user_meta( $user_id, 'avl_course_ids', $course_id );
				$success2 = AVLCourseMeta::delete( (int) $course_id, (int) $user_id, 'expire_date' );
				if (false === $success1 || false === $success2) {
					$success = false;
					break;
				}
			}
		}

		return new \WP_REST_Response(
			[
				'code'    => $success ? 'remove_students_success' : 'remove_students_failed',
				'message' => $success ? '移除學員成功' : '移除學員失敗',
				'data'    => [
					'user_ids'   => \implode(',', $user_ids),
					'course_ids' => \implode(',', $course_ids),
				],
			],
			$success ? 200 : 400
		);
	}


	/**
	 * Delete courses with id callback
	 *
	 * @param \WP_REST_Request $request Request.
	 *
	 * @return \WP_REST_Response
	 * @phpstan-ignore-next-line
	 */
	public function delete_courses_with_id_callback( $request ) {
		$id = (int) $request['id'];
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

		\wp_trash_post( $id );

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
	 * @phpstan-ignore-next-line
	 */
	public function get_courses_terms_callback( $request ) { // phpcs:ignore

		$params = $request->get_query_params();

		$params = WP::sanitize_text_field_deep( $params, false );

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

		$formatted_terms = array_values(array_map( [ $this, 'format_terms' ], array_keys( $terms ), array_values( $terms ) ));

		return $formatted_terms;
	}

	/**
	 * Format terms
	 *
	 * @param string $key Key.
	 * @param string $value Value.
	 *
	 * @return array{id:string, name:string}
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
	 * @phpstan-ignore-next-line
	 */
	public function get_courses_options_callback( $request ) { // phpcs:ignore

		// it seems no need to add post_per_page, get_terms will return all terms
		$cat_args = [
			'taxonomy'   => 'product_cat',
			'fields'     => 'id=>name',
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		];
		$cats     = \get_terms( $cat_args );

		$formatted_cats = array_values(array_map( [ $this, 'format_terms' ], array_keys( $cats ), array_values( $cats ) ));

		$tag_args = [
			'taxonomy'   => 'product_tag',
			'fields'     => 'id=>name',
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		];

		$tags = \get_terms( $tag_args );

		$formatted_tags = array_values(array_map( [ $this, 'format_terms' ], array_keys( $tags ), array_values( $tags ) ));

		$top_sales_products = CourseUtils::get_top_sales_courses( 5 );

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
	 * @return array{max_price:int, min_price:int}
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

		// @phpstan-ignore-next-line
		\set_transient( $transient_key, $max_min_prices, 1 * HOUR_IN_SECONDS );

		return $max_min_prices;
	}
}

Course::instance();
