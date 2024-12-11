<?php
/**
 * Chapter API
 */

declare(strict_types=1);

namespace J7\PowerCourse\Api;

use J7\PowerCourse\Resources\Chapter\Utils as ChapterUtils;
use J7\PowerCourse\Resources\Chapter\CPT as ChapterCPT;
use J7\WpUtils\Classes\WP;
use J7\WpUtils\Classes\General;
use J7\WpUtils\Classes\ApiBase;
use J7\PowerCourse\Utils\Course as CourseUtils;
use J7\PowerCourse\Resources\Chapter\AVLChapter;
use J7\PowerCourse\Resources\Chapter\MetaCRUD as AVLChapterMeta;
use J7\PowerCourse\Resources\Chapter\LifeCycle as ChapterLifeCycle;



/**
 * Class Chapter
 */
final class Chapter extends ApiBase {
	use \J7\WpUtils\Traits\SingletonTrait;

	/**
	 * Namespace
	 *
	 * @var string
	 */
	protected $namespace = 'power-course';

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
			'method'              => 'get',
			'permission_callback' => null,
		],
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
	 * Get chapters callback
	 *
	 * @param \WP_REST_Request $request Request.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 * @phpstan-ignore-next-line
	 */
	public function get_chapters_callback( $request ) { // phpcs:ignore

		$params = $request->get_query_params();

		$params = WP::sanitize_text_field_deep( $params, false );

		$default_args = [
			'post_type'      => ChapterCPT::POST_TYPE,
			'posts_per_page' => - 1,
			'post_status'    => 'any',
			'orderby'        => [
				'menu_order' => 'ASC',
				'date'       => 'DESC',
				'ID'         => 'DESC',
			],

		];

		$args = \wp_parse_args(
			$params,
			$default_args,
		);

		$chapters = \get_posts($args);
		$chapters = array_values(array_map( [ ChapterUtils::class, 'format_chapter_details' ], $chapters ));

		$response = new \WP_REST_Response( $chapters );

		return $response;
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

		$body_params = ChapterUtils::converter( $body_params );

		$skip_keys   = [
			'chapter_video',
			'post_content',
		];
		$body_params = WP::sanitize_text_field_deep($body_params, true, $skip_keys);

		// 將 '[]' 轉為 []
		$body_params = General::format_empty_array( $body_params );

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

		$qty = (int) ( $meta_data['qty'] ?? 1 );
		unset($meta_data['qty']);

		$post_parents = ( $meta_data['post_parents'] ?? [] );
		unset($meta_data['post_parents']);

		// 不需要紀錄 depth，深度是由 post_parent 決定的
		unset($meta_data['depth']);
		// action 用來區分是 create 還是 update ，目前只有 create ，所以不用判斷
		unset($meta_data['action']);

		$data['meta_input'] = $meta_data;

		$success_ids = [];
		$failed_ids  = [];
		foreach ($post_parents as $post_parent) {
			$data['post_parent'] = $post_parent;
			for ($i = 0; $i < $qty; $i++) {
				$post_id = ChapterUtils::create_chapter( $data );
				if (is_numeric($post_id)) {
					$success_ids[] = $post_id;
				} else {
					$failed_ids[] = $post_id;
				}
			}
		}

		return new \WP_REST_Response(
			$success_ids
		);

		// return new \WP_REST_Response(
		// [
		// 'code'    => 'create_success',
		// 'message' => '新增成功',
		// 'data'    => [
		// 'success_ids' => $success_ids,
		// 'failed_ids'  => $failed_ids,
		// ],
		// ]
		// );
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

		$sort_result = ChapterUtils::sort_chapters( (array) $body_params );

		if ( $sort_result !== true ) {
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

		$update_result = ChapterUtils::update_chapter( $id, $data );

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

		$include_required_params = WP::include_required_params( $body_params, [ 'course_id' ] );
		if ( $include_required_params !== true ) {
			return $include_required_params;
		}

		$course_id = (int) $body_params['course_id'];
		$user_id   = \get_current_user_id();

		$avl_chapter              = new AVLChapter( (int) $chapter_id, (int) $user_id );
		$is_this_chapter_finished = !!$avl_chapter->finished_at;
		$title                    = \get_the_title( $chapter_id);
		$product                  = \wc_get_product( $course_id );

		if ($is_this_chapter_finished) {
			$success  = AVLChapterMeta::delete(
				(int) $chapter_id,
				$user_id,
				'finished_at'
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

		$success  = AVLChapterMeta::add(
			(int) $chapter_id,
			$user_id,
			'finished_at',
			\wp_date('Y-m-d H:i:s')
			);
		$progress = CourseUtils::get_course_progress( $product );

		\do_action(ChapterLifeCycle::CHAPTER_FINISHED_ACTION, $chapter_id, $course_id, $user_id);

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
