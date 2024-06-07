<?php
/**
 * Chapter API
 */

declare(strict_types=1);

namespace J7\PowerCourse\Api;

use J7\PowerCourse\Plugin;
use J7\PowerCourse\Resources\Chapter\ChapterFactory;


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
				'method'   => 'post',
			),
			array(
				'endpoint' => 'chapters/sort',
				'method'   => 'post',
			),
		);

		foreach ( $apis as $api ) {
			$strip_endpoint = str_replace( '/', '_', $api['endpoint'] );

			\register_rest_route(
				Plugin::$kebab,
				$api['endpoint'],
				array(
					'methods'             => $api['method'],
					'callback'            => array( $this, $api['method'] . '_' . $strip_endpoint . '_callback' ),
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
			array(
				'endpoint' => 'chapters',
				'method'   => 'patch',
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
	 * Post Chapter callback
	 * 創建章節
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function post_chapters_callback( $request ) {

		$body_params = $request->get_json_params() ?? array();

		$body_params = array_map( array( 'J7\WpUtils\Classes\WP', 'sanitize_text_field_deep' ), $body_params );

		$create_result = ChapterFactory::create_chapter( $body_params );

		if ( \is_wp_error( $create_result ) ) {
			return new \WP_REST_Response(
				array(
					'id'      => 0,
					'message' => $create_result->get_error_message(),
				),
				400
			);
		}

		return new \WP_REST_Response(
			array(
				'id'      => $create_result,
				'message' => '新增成功',
			)
		);
	}


	/**
	 * Post Chapter Sort callback
	 * 處理排序
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function post_chapters_sort_callback( $request ) {

		$body_params = $request->get_json_params() ?? array();

		$body_params = array_map( array( 'J7\WpUtils\Classes\WP', 'sanitize_text_field_deep' ), $body_params );

		$sort_result = ChapterFactory::sort_chapters( $body_params );

		if ( \is_wp_error( $sort_result ) ) {
			return new \WP_REST_Response(
				array(
					'id'      => 0,
					'message' => $sort_result->get_error_message(),
				),
				400
			);
		}

		return new \WP_REST_Response(
			array(
				'id'      => $sort_result,
				'message' => '新增成功',
			)
		);
	}

	/**
	 * Patch Chapter callback
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function patch_chapters_with_id_callback( $request ) {

		$id          = $request['id'];
		$body_params = $request->get_json_params() ?? array();
		$body_params = array_map( array( 'J7\WpUtils\Classes\WP', 'sanitize_text_field_deep' ), $body_params );

		$formatted_params = ChapterFactory::converter( $body_params );

		$update_result = ChapterFactory::update_chapter( $id, $formatted_params );

		if ( \is_wp_error( $update_result ) ) {
			return new \WP_REST_Response(
				array(
					'id'      => $id,
					'message' => '更新失敗',
				),
				400
			);
		}

		return new \WP_REST_Response(
			array(
				'id'      => $id,
				'message' => '更新成功',
			)
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
		$id            = $request['id'];
		$delete_result = ChapterFactory::delete_chapter( $id );

		if ( ! $delete_result ) {
			return new \WP_REST_Response(
				array(
					'id'      => $id,
					'message' => '刪除失敗',
				),
				400
			);
		}
		return new \WP_REST_Response(
			array(
				'id'      => $id,
				'message' => '刪除成功',
			)
		);
	}
}

Chapter::instance();
