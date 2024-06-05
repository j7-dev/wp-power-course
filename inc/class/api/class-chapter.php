<?php
/**
 * Chapter API
 */

declare(strict_types=1);

namespace J7\PowerCourse\Api;

use J7\PowerCourse\Plugin;
use J7\PowerCourse\Admin\CPT;
use J7\WpUtils\Classes\WP;


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

		$apis_with_id = array(
			array(
				'endpoint' => 'chapters',
				'method'   => 'delete',
			),
		);

		foreach ( $apis_with_id as $api ) {
			\register_rest_route(
				Plugin::$kebab . '/' . $api['endpoint'],
				'/(?P<id>\d+)',
				array(
					'methods'             => $api['method'],
					'callback'            => array( $this, $api['method'] . '_' . $api['endpoint'] . '_with_id_callback' ),
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
	 * 創建章節
	 *
	 * @see https://rudrastyh.com/woocommerce/create-product-programmatically.html
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function post_chapters_callback( $request ) {

		$body_params = $request->get_json_params() ?? array();

		$body_params = array_map( array( 'J7\WpUtils\Classes\WP', 'sanitize_text_field_deep' ), $body_params );

		$include_required_params = WP::include_required_params(
			$body_params,
			array(
				'post_parent',
			),
			true
		);

		if ( true !== $include_required_params ) {
			return $include_required_params;
		}

		$args = array(
			'post_title'  => $body_params['post_title'] ?? '新章節',
			'post_status' => 'draft',
			'post_author' => \get_current_user_id(),
			'post_parent' => $body_params['post_parent'] ?? 0,
			'post_type'   => CPT::POST_TYPE,
		);

		$new_post_id = \wp_insert_post( $args );

		return new \WP_REST_Response(
			[
				'id'      => $new_post_id,
				'message' => '新增成功',
			]
		);
	}

	/**
	 * Delete Chapter callback
	 * 刪除章節
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function delete_chapters_with_id_callback( $request ) {
		$id = $request['id'];
		if ( empty( $id ) ) {
			return new \WP_REST_Response(
				[
					'id'      => $id,
					'message' => '刪除失敗，請提供ID',
				],
				400
			);
		}
		$delete_result = \wp_delete_post( $id );

		if ( ! $delete_result ) {
			return new \WP_REST_Response(
				[
					'id'      => $id,
					'message' => '刪除失敗',
				],
				400
			);
		}
		return new \WP_REST_Response(
			[
				'id'      => $id,
				'message' => '刪除成功',
			]
		);
	}
}

Chapter::instance();
