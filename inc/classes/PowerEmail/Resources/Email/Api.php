<?php
/**
 * Email API
 */

declare(strict_types=1);

namespace J7\PowerCourse\PowerEmail\Resources\Email;

use J7\WpUtils\Classes\WP;
use J7\WpUtils\Classes\ApiBase;
use J7\WpUtils\Classes\General;
use J7\PowerCourse\PowerEmail\Resources\Email\CPT as EmailCPT;
use J7\PowerCourse\PowerEmail\Resources\Email\Email as EmailResource;
use J7\PowerCourse\PowerEmail\Resources\Email\Trigger\At;
use J7\PowerCourse\PowerEmail\Resources\Email\Replace;


/**
 * Class Api
 */
final class Api extends ApiBase {
	use \J7\WpUtils\Traits\SingletonTrait;

	/**
	 * Namespace
	 *
	 * @var string
	 */
	protected $namespace = 'power-email';

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
			'endpoint'            => 'emails',
			'method'              => 'get',
			'permission_callback' => null,
		],
		[
			'endpoint'            => 'emails/(?P<id>\d+)',
			'method'              => 'get',
			'permission_callback' => null,
		],
		[
			'endpoint'            => 'emails',
			'method'              => 'post',
			'permission_callback' => null,
		],
		[
			'endpoint'            => 'emails/send-now',
			'method'              => 'post',
			'permission_callback' => null,
		],
		[
			'endpoint'            => 'emails/send-schedule',
			'method'              => 'post',
			'permission_callback' => null,
		],
		[
			'endpoint'            => 'emails/(?P<id>\d+)',
			'method'              => 'post',
			'permission_callback' => null,
		],
		[
			'endpoint'            => 'emails/(?P<id>\d+)',
			'method'              => 'delete',
			'permission_callback' => null,
		],
		[
			'endpoint'            => 'emails/options',
			'method'              => 'get',
			'permission_callback' => null,
		],
		[
			'endpoint'            => 'emails/scheduled-actions',
			'method'              => 'get',
			'permission_callback' => null,
		],
	];

	/**
	 * Get emails callback
	 *
	 * @param \WP_REST_Request $request Request.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 * @phpstan-ignore-next-line
	 */
	public function get_emails_callback( $request ) { // phpcs:ignore

		// 未來有做篩選器材需要開啟
		// $params = $request->get_query_params();
		// $params = WP::sanitize_text_field_deep( $params, false );

		$default_args = [
			'post_type'      => EmailCPT::POST_TYPE,
			'posts_per_page' => '20',
			'post_status'    => 'any',
			'fields'         => 'ids',
			'orderby'        => [
				'date' => 'DESC',
				'ID'   => 'DESC',
			],
		];

		// 未來有做篩選器材需要開啟
		// $args = \wp_parse_args(
		// $params,
		// $default_args,
		// );

		$results = new \WP_Query( $default_args );

		$total       = $results->total;
		$total_pages = $results->max_num_pages;

		$post_ids = $results->posts;

		$emails = array_values(array_map( fn( $post_id ) => new EmailResource( (int) $post_id, false, true ), $post_ids ));

		$response = new \WP_REST_Response( $emails );

		// set pagination in header
		$response->header( 'X-WP-Total', $total );
		$response->header( 'X-WP-TotalPages', $total_pages );

		return $response;
	}


	/**
	 * Get email callback
	 *
	 * @param \WP_REST_Request $request Request.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 * @phpstan-ignore-next-line
	 */
	public function get_emails_with_id_callback( $request ) { // phpcs:ignore
		$id = $request['id'];

		$email = new EmailResource( (int) $id, true, true );

		$response = new \WP_REST_Response( $email );

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

		$body_params = WP::converter( $body_params );

		$skip_keys   = [
			'post_content', // email html 內容
			'post_excerpt', // email json 內容
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
	 * Post Email callback
	 * 創建電子郵件
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 * @phpstan-ignore-next-line
	 */
	public function post_emails_callback( $request ): \WP_REST_Response|\WP_Error {

		$post_id = \wp_insert_post(
			[
				'post_type'    => EmailCPT::POST_TYPE,
				'post_title'   => __('New Email', 'power-email'),
				'post_content' => '',
				'post_status'  => 'draft',
				'post_author'  => \get_current_user_id(),
			]
			);

		if (\is_wp_error($post_id)) {
			return $post_id;
		}

		return new \WP_REST_Response(
			[
				'code'    => 'create_success',
				'message' => '創建成功',
				'data'    => [
					'id' => $post_id,
				],
			]
			);
	}

	/**
	 * Patch Email callback
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 * @phpstan-ignore-next-line
	 */
	public function post_emails_with_id_callback( $request ): \WP_REST_Response|\WP_Error {

		[
			'data'      => $data,
			'meta_data' => $meta_data,
		] = $this->separator( $request );

		$data['meta_input'] = $meta_data;
		$data['ID']         = $request['id'];

		$update_result = \wp_update_post($data);

		if ( \is_wp_error( $update_result ) ) {
			return $update_result;
		}

		return new \WP_REST_Response(
			[
				'code'    => 'update_success',
				'message' => '更新成功',
				'data'    => [
					'id' => $update_result,
				],
			]
		);
	}

	/**
	 * Post Email Send Now callback
	 * 立即發送電子郵件
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 * @phpstan-ignore-next-line
	 */
	public function post_emails_send_now_callback( $request ): \WP_REST_Response|\WP_Error {
		$body_params = $request->get_json_params();

		$include_required_params = WP::include_required_params( $body_params, [ 'email_ids', 'user_ids' ] );
		if ( $include_required_params !== true ) {
			return $include_required_params;
		}

		$email_ids = $body_params['email_ids'];
		$user_ids  = $body_params['user_ids'];

		$action_id = \as_enqueue_async_action(
			At::SEND_USERS_HOOK,
			[
				'email_ids' => $email_ids,
				'user_ids'  => $user_ids,
			],
			At::AS_GROUP
			);

		return new \WP_REST_Response(
			[
				'code'    => 'send_success',
				'message' => '發送成功',
				'data'    => [
					'action_id' => $action_id,
				],
			]
			);
	}



	/**
	 * Post Email Send Schedule callback
	 * 排程發送電子郵件
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 * @phpstan-ignore-next-line
	 */
	public function post_emails_send_schedule_callback( $request ): \WP_REST_Response|\WP_Error {
		$body_params = $request->get_json_params();

		$include_required_params = WP::include_required_params( $body_params, [ 'email_ids', 'user_ids', 'timestamp' ] );
		if ( $include_required_params !== true ) {
			return $include_required_params;
		}

		$email_ids = $body_params['email_ids'];
		$user_ids  = $body_params['user_ids'];
		$timestamp = $body_params['timestamp'];

		$action_id = \as_schedule_single_action(
			$timestamp,
			At::SEND_USERS_HOOK,
			[
				'email_ids' => $email_ids,
				'user_ids'  => $user_ids,
			],
			At::AS_GROUP
			);

		return new \WP_REST_Response(
			[
				'code'    => 'schedule_success',
				'message' => '排程成功',
				'data'    => [
					'action_id' => $action_id,
				],
			]
			);
	}



	/**
	 * Delete Email callback
	 * 刪除電子郵件
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 * @phpstan-ignore-next-line
	 */
	public function delete_emails_with_id_callback( $request ): \WP_REST_Response {
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
	 * Get Email Options callback
	 * 取得電子郵件選項
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 * @phpstan-ignore-next-line
	 */
	public function get_emails_options_callback( $request ): \WP_REST_Response {
		return new \WP_REST_Response(
			[
				'course_schema' => Replace\Course::$schema,
				'user_schema'   => Replace\User::$schema,
			]
		);
	}

	/**
	 * Get Email Scheduled Actions callback
	 * 取得排程動作
	 *
	 * @see as_get_scheduled_actions in woocommerce
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 * @phpstan-ignore-next-line
	 */
	public function get_emails_scheduled_actions_callback( $request ): \WP_REST_Response {
		$args = [
			'group'    => At::AS_GROUP, // EmailCPT::AS_HOOK,
			'status'   => '', // ActionScheduler_Store::STATUS_COMPLETE or ActionScheduler_Store::STATUS_PENDING.
			'per_page' => 20,                  // -1 表示取得所有
			'order'    => 'DESC',
			// 'group' => 'your_group',           // 指定 action group
		];

		[
			'count' => $total,
			'actions' => $actions,
		] = $this->as_get_scheduled_actions( $args );

		$formatted_scheduled_actions = [];
		foreach ($actions as $action_id => $action) {
			/**
			 * @var \ActionScheduler_SimpleSchedule $schedule
			 */
			$schedule = $action->get_schedule();

			$action_arr                    = [
				'id'          => (string) $action_id,
				'name'        => $action->get_hook(),
				'args'        => $action->get_args(),
				'group'       => $action->get_group(),
				'priority'    => $action->get_priority(),
				'schedule'    => $schedule->get_date()?->format('Y-m-d H:i:s'),
				'is_finished' => $action->is_finished(),
			];
			$formatted_scheduled_actions[] = $action_arr;
		}

		$response    = new \WP_REST_Response( $formatted_scheduled_actions );
		$total_pages = ceil( $total / (int) ( $args['per_page'] ?? 20 ) );

		// set pagination in header
		$response->header( 'X-WP-Total', $total );
		$response->header( 'X-WP-TotalPages', $total_pages );

		return $response;
	}




	/**
	 * 改寫原本 woocommerce 的 as_get_scheduled_actions
	 * 因為原本只有 return 結果，沒有 return count，這樣無法做分頁
	 *
	 * @see as_get_scheduled_actions in woocommerce
	 *
	 * @param array<string, mixed> $args 查詢參數
	 * @param string               $return_format OBJECT | ARRAY_A
	 * @return array{count: int, actions: array<int, \ActionScheduler_Action>}
	 */
	private function as_get_scheduled_actions( $args = [], $return_format = OBJECT ) {
		if ( ! \ActionScheduler::is_initialized( __FUNCTION__ ) ) {
			return [];
		}
		/**
		 * @var \ActionScheduler_DBStore $store
		 * 預設是 ActionScheduler_wpPostStore 但測試結果是 return ActionScheduler_DBStore
		 */
		$store = \ActionScheduler::store();

		foreach ( [ 'date', 'modified' ] as $key ) {
			if ( isset( $args[ $key ] ) ) {
				$args[ $key ] = as_get_datetime_object( $args[ $key ] );
			}
		}
		$count_args             = $args;
		$count_args['per_page'] = -1;
		$count                  = $store->query_actions( $count_args, 'count' );
		$ids                    = $store->query_actions( $args );

		if ( 'ids' === $return_format || 'int' === $return_format ) {
			return $ids;
		}

		$actions = [];
		foreach ( $ids as $action_id ) {
			$actions[ $action_id ] = $store->fetch_action( $action_id );
		}

		if ( ARRAY_A == $return_format ) {
			foreach ( $actions as $action_id => $action_object ) {
				$actions[ $action_id ] = get_object_vars( $action_object );
			}
		}

		return [
			'count'   => (int) $count,
			'actions' => $actions,
		];
	}
}
