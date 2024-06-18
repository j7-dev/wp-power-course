<?php
/**
 * Chapter API
 */

declare(strict_types=1);

namespace J7\PowerCourse\Api;

use J7\PowerCourse\Plugin;
use J7\PowerCourse\Resources\Chapter\ChapterFactory;
use J7\WpUtils\Classes\WP;

/**
 * Class Course
 */
final class Chapter {
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
	protected $apis = array(
		array(
			'endpoint' => 'chapters',
			'method'   => 'post',
		),
		array(
			'endpoint' => 'chapters/sort',
			'method'   => 'post',
		),
		array(
			'endpoint' => 'chapters/(?P<id>\d+)',
			'method'   => 'post',
		),
	);

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
		$this->register_apis(
			apis: $this->apis,
			namespace: Plugin::$kebab,
			default_permission_callback: fn() => \current_user_can( 'manage_options' ),
		);
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

		$body_params = array_map( array( WP::class, 'sanitize_text_field_deep' ), $body_params );

		$create_result = ChapterFactory::create_chapter( $body_params );

		if ( \is_wp_error( $create_result ) ) {
			return $create_result;
		}

		return new \WP_REST_Response(
			array(
				'code'    => 'create_success',
				'message' => '新增成功',
				'data'    => array(
					'id' => $create_result,
				),
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

		$body_params = array_map( array( WP::class, 'sanitize_text_field_deep' ), $body_params );

		$sort_result = ChapterFactory::sort_chapters( $body_params );

		if ( \is_wp_error( $sort_result ) ) {
			return $sort_result;
		}

		return new \WP_REST_Response(
			array(
				'code'    => 'sort_success',
				'message' => '修改排序成功',
				'data'    => null,
			)
		);
	}

	/**
	 * Patch Chapter callback
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function post_chapters_with_id_callback( $request ) {

		$id          = $request['id'];
		$body_params = $request->get_body_params() ?? array();
		$body_params = array_map( array( WP::class, 'sanitize_text_field_deep' ), $body_params );

		$formatted_params = ChapterFactory::converter( $body_params );

		$update_result = ChapterFactory::update_chapter( $id, $formatted_params );

		if ( \is_wp_error( $update_result ) ) {
			return $update_result;
		}

		return new \WP_REST_Response(
			array(
				'code'    => 'update_success',
				'message' => '更新成功',
				'data'    => array(
					'id' => $id,
				),
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
					'code'    => 'delete_failed',
					'message' => '刪除失敗',
					'data'    => array(
						'id' => $id,
					),
				),
				400
			);
		}
		return new \WP_REST_Response(
			array(
				'code'    => 'delete_success',
				'message' => '刪除成功',
				'data'    => array(
					'id' => $id,
				),
			)
		);
	}
}

Chapter::instance();
