<?php

/**
 * Chapter API
 */

declare(strict_types=1);

namespace J7\PowerCourse\Api;

use J7\PowerCourse\Plugin;
use J7\PowerCourse\Utils\Base;
use J7\PowerCourse\Admin\Product as AdminProduct;


/**
 * Class Course
 */
final class Chapter {


	use \J7\WpUtils\Traits\SingletonTrait;

	/**
	 * Constructor.
	 */
	public function __construct() {
		\add_action( 'rest_api_init', array( $this, 'register_api_chapters' ) );
	}

	/**
	 * Register Course API
	 *
	 * @return void
	 */
	public function register_api_chapters(): void {

		$apis = array(
			array(
				'endpoint' => 'chapters',
				'method'   => 'get',
			),
			array(
				'endpoint' => 'chapters',
				'method'   => 'post',
			),
			array(
				'endpoint' => 'upload',
				'method'   => 'post',
			),
		);

		foreach ( $apis as $api ) {
			\register_rest_route(
				Plugin::$kebab,
				$api['endpoint'],
				array(
					'methods'             => $api['method'],
					'callback'            => array( $this, $api['method'] . '_' . $api['endpoint'] . '_callback' ),
					'permission_callback' => function () {
						return \current_user_can( 'manage_options' );
					},
				)
			);
		}
	}


	/**
	 * Get chapters callback
	 * TODO 需要這支API嗎?
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function get_courses_callback( $request )
	{ // phpcs:ignore

		$response = new \WP_REST_Response( [] );

		// set pagination in header
		$response->header( 'X-WP-Total', 0 );
		$response->header( 'X-WP-TotalPages', 0 );

		return $response;
	}

	/**
	 * Format Chapter details
	 * TODO
	 *
	 * @param \WP_Post $post Chapter.
	 * @param bool     $with_description With description.
	 * @return array
	 */
	public function format_chapter_details( $post, $with_description = true )
	{ // phpcs:ignore

		if ( ! ( $post instanceof \WP_Post ) ) {
			return array();
		}

		return [];
	}

	/**
	 * Post Chapter callback
	 * 創建課程
	 *
	 * @see https://rudrastyh.com/woocommerce/create-product-programmatically.html
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function post_courses_callback( $request ) {

		$body_params = $request->get_json_params() ?? array();

		$body_params = array_map( array( 'J7\WpUtils\Classes\WP', 'sanitize_text_field_deep' ), $body_params );

		$product = new \WC_Product_Simple();

		$keys = array(
			'name',
			'slug',
			'regular_price',
			'sale_price',
			'short_description',
			'description',
			'image_id',
			'gallery_image_ids',
			'status',
			'catalog_visibility',
			'category_ids',
		);

		// TODO
		$meta_keys = array(
			'sub_title',
		);

		foreach ( $keys as $key ) {
			if ( isset( $body_params[ $key ] ) ) {
				$$key        = $body_params[ $key ];
				$method_name = 'set_' . $key;
				$product->$method_name( $$key );
			}
		}

		$product->save();

		$product->update_meta_data( '_' . AdminProduct::PRODUCT_OPTION_NAME, 'yes' );

		$product->save_meta_data();

		return new \WP_REST_Response( $this->format_product_details( $product ) );
	}



	public function post_upload_callback( $request ) {
		$files = $request->get_file_params();
		ob_start();
		var_dump( $files );
		\J7\WpUtils\Classes\Log::info( '' . ob_get_clean() );

		if ( empty( $files ) ) {
			return new \WP_Error( 'no_file', 'No file provided', array( 'status' => 400 ) );
		}

		$uploaded_file = $files['file'];

		$upload_overrides = array( 'test_form' => false );

		$movefile = wp_handle_upload( $uploaded_file, $upload_overrides );

		if ( $movefile && ! isset( $movefile['error'] ) ) {
			return rest_ensure_response( $movefile );
		} else {
			return new \WP_Error( 'upload_error', $movefile['error'], array( 'status' => 500 ) );
		}
	}
}

Chapter::instance();
