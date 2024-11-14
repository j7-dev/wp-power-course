<?php
/**
 * Email API
 */

declare(strict_types=1);

namespace J7\PowerCourse\PowerEmail\Resources\Email;

use J7\WpUtils\Classes\WP;
use J7\WpUtils\Classes\General;
use J7\PowerCourse\PowerEmail\Resources\Email\CPT as EmailCPT;
use J7\PowerCourse\PowerEmail\Resources\Email\Email as EmailResource;


/**
 * Class Api
 */
final class Api {
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
	];

	const SEND_SCHEDULE_HOOK = 'power_email_send_schedule';

	/**
	 * Constructor.
	 */
	public function __construct() {
		\add_action( 'rest_api_init', [ $this, 'register_api_emails' ] );
		\add_action( self::SEND_SCHEDULE_HOOK, [ $this, 'send_schedule_callback' ], 10, 2 );
	}

	/**
	 * Register Email API
	 *
	 * @return void
	 */
	public function register_api_emails(): void {
		$this->register_apis(
			apis: $this->apis,
			namespace: 'power-email',
			default_permission_callback: fn() => \current_user_can( 'manage_options' ),
		);
	}

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

		$emails = array_values(array_map( fn( $post_id ) => new EmailResource( (int) $post_id, false ), $post_ids ));

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

		$email = new EmailResource( (int) $id );

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

		foreach ( $email_ids as $email_id ) {
			$email = new EmailResource( (int) $email_id );
			$email->send_now( $user_ids );
		}

		return new \WP_REST_Response(
			[
				'code'    => 'send_success',
				'message' => '發送成功',
				'data'    => [
					'email_ids' => $email_ids,
					'user_ids'  => $user_ids,
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
			self::SEND_SCHEDULE_HOOK,
			[
				'email_ids' => $email_ids,
				'user_ids'  => $user_ids,
			]
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
	 * Send schedule callback
	 * 排程發信，時間到後的觸發動作
	 *
	 * @param array $email_ids 電子郵件 ID 陣列
	 * @param array $user_ids 使用者 ID 陣列
	 */
	public function send_schedule_callback( $email_ids, $user_ids ) {
		foreach ( $email_ids as $email_id ) {
			$email = new EmailResource( (int) $email_id );
			$email->send_now( $user_ids );
		}
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
}
