<?php
/**
 * Chapter API
 */

declare(strict_types=1);

namespace J7\PowerCourse\Api;

use J7\PowerCourse\Plugin;
use J7\PowerCourse\Resources\Chapter\ChapterFactory;
use J7\WpUtils\Classes\WP;
use J7\PowerCourse\Utils\AVLCourseMeta;
use J7\PowerCourse\Utils\Course as CourseUtils;
use J7\PowerCourse\Utils\Base;
use J7\PowerCourse\Resources\Course;

/**
 * Class Course
 */
final class Chapter {
	use \J7\WpUtils\Traits\SingletonTrait;
	use \J7\WpUtils\Traits\ApiRegisterTrait;

	/**
	 * APIs
	 *
	 * @var array{endpoint:string,method:string,permission_callback: callable|null }[]
	 * - endpoint: string
	 * - method: 'get' | 'post' | 'patch' | 'delete'
	 * - permission_callback : callable
	 */
	protected $apis = [
		[
			'endpoint'            => 'chapters',
			'method'              => 'post',
			'permission_callback' => null,
		],
		[
			'endpoint'            => 'chapters/sort',
			'method'              => 'post',
			'permission_callback' => null,
		],
		[
			'endpoint'            => 'chapters/(?P<id>\d+)',
			'method'              => 'post',
			'permission_callback' => null,
		],
		[
			'endpoint'            => 'toggle-finish-chapters/(?P<id>\d+)',
			'method'              => 'post',
			'permission_callback' => 'is_user_logged_in',
		],
	];

	/**
	 * Constructor.
	 */
	public function __construct() {
		\add_action( 'rest_api_init', [ $this, 'register_api_chapters' ] );
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
		$body_params = $request->get_body_params();
		$file_params = $request->get_file_params();

		$body_params = ChapterFactory::converter( $body_params );

		$skip_keys   = [
			'chapter_video',
			'post_content',
		];
		$body_params = WP::sanitize_text_field_deep($body_params, true, $skip_keys);

		// 將 '[]' 轉為 []
		$body_params = Base::format_empty_array( $body_params );

		[
			'data'      => $data,
			'meta_data' => $meta_data,
		] = WP::separator( args: $body_params, obj: 'post', files: $file_params['files'] ?? [] );

		return [
			'data'      => $data,
			'meta_data' => $meta_data,
		];
	}

	/**
	 * Post Chapter callback
	 * 創建章節
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 * @phpstan-ignore-next-line
	 */
	public function post_chapters_callback( $request ): \WP_REST_Response|\WP_Error {

		[
			'data'      => $data,
			'meta_data' => $meta_data,
		] = $this->separator( $request );

		$data['meta_input'] = $meta_data;

		$post_id = ChapterFactory::create_chapter( $data );

		if ( \is_wp_error( $post_id ) ) {
			return $post_id;
		}

		return new \WP_REST_Response(
			[
				'code'    => 'create_success',
				'message' => '新增成功',
				'data'    => [
					'id' => $post_id,
				],
			]
		);
	}


	/**
	 * Post Chapter Sort callback
	 * 處理排序
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 * @phpstan-ignore-next-line
	 */
	public function post_chapters_sort_callback( $request ): \WP_REST_Response|\WP_Error {

		$body_params = $request->get_json_params();

		$body_params = WP::sanitize_text_field_deep( $body_params, false );

		$sort_result = ChapterFactory::sort_chapters( (array) $body_params );

		if ( \is_wp_error( $sort_result ) ) {
			return $sort_result;
		}

		return new \WP_REST_Response(
			[
				'code'    => 'sort_success',
				'message' => '修改排序成功',
				'data'    => null,
			]
		);
	}

	/**
	 * Patch Chapter callback
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 * @phpstan-ignore-next-line
	 */
	public function post_chapters_with_id_callback( $request ): \WP_REST_Response|\WP_Error {

		$id = $request['id'];

		[
			'data'      => $data,
			'meta_data' => $meta_data,
		] = $this->separator( $request );

		$data['meta_input'] = $meta_data;

		$update_result = ChapterFactory::update_chapter( $id, $data );

		if ( \is_wp_error( $update_result ) ) {
			return $update_result;
		}

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
	 * Delete Chapter callback
	 * 刪除章節
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 * @phpstan-ignore-next-line
	 */
	public function delete_chapters_with_id_callback( $request ): \WP_REST_Response {
		$id = $request['id'];
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
	 * Patch Chapter callback
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 *
	 * @phpstan-ignore-next-line
	 */
	public function post_toggle_finish_chapters_with_id_callback( $request ): \WP_REST_Response|\WP_Error {

		$chapter_id = $request['id'];
		// @phpstan-ignore-next-line
		$body_params = $request->get_body_params() ?? [];
		$body_params = WP::sanitize_text_field_deep( $body_params, false );

		WP::include_required_params( $body_params, [ 'course_id' ]);

		$course_id = (int) $body_params['course_id'];
		$user_id   = \get_current_user_id();

		$finished_chapters = AVLCourseMeta::get(
			$course_id,
			$user_id,
			'finished_chapter_ids'
		);

		$is_this_chapter_finished = in_array( (string) $chapter_id, $finished_chapters, true );
		$title                    = \get_the_title( $chapter_id);
		$product                  = \wc_get_product( $course_id );

		if ($is_this_chapter_finished) {
			$success  = AVLCourseMeta::delete(
				$course_id,
				$user_id,
				'finished_chapter_ids',
				$chapter_id
			);
			$progress = CourseUtils::get_course_progress( $product );

			return new \WP_REST_Response(
				[
					'code'    => $success ? '200' : '400',
					'message' => $success ? "單元 {$title} 已標示為未完成！" : "單元 {$title} 標示為未完成時出錯了！",
					'data'    => [
						'chapter_id'               => $chapter_id,
						'course_id'                => $course_id,
						'is_this_chapter_finished' => $success ? false : true,
						'progress'                 => $progress,
					],
				],
				$success ? 200 : 400
			);
		}

		$success  = AVLCourseMeta::add(
				$course_id,
				$user_id,
				'finished_chapter_ids',
				$chapter_id
			);
		$progress = CourseUtils::get_course_progress( $product );
		return new \WP_REST_Response(
				[
					'code'    => $success ? '200' : '400',
					'message' => $success ? "單元 {$title} 已標示為完成！" : "單元 {$title} 標示為未完成時出錯了！",
					'data'    => [
						'chapter_id'               => $chapter_id,
						'course_id'                => $course_id,
						'is_this_chapter_finished' => $success ? true : false,
						'progress'                 => $progress,
					],
				],
				$success ? 200 : 400
			);
	}
}

Chapter::instance();
