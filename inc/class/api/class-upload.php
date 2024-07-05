<?php
/**
 * Product API
 */

declare(strict_types=1);

namespace J7\PowerCourse\Api;

use J7\PowerCourse\Plugin;

/**
 * Class Api
 */
final class Upload {
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
			'endpoint' => 'upload',
			'method'   => 'post',
		],
	];

	/**
	 * Constructor.
	 */
	public function __construct() {
		\add_action( 'rest_api_init', [ $this, 'register_api_upload' ] );
	}

	/**
	 * Register products API
	 *
	 * @return void
	 */
	public function register_api_upload(): void {
		$this->register_apis(
			apis: $this->apis,
			namespace: Plugin::$kebab,
			default_permission_callback: fn() => \current_user_can( 'manage_options' ),
		);
	}

	/**
	 * Post upload callback
	 * 上傳檔案
	 * post 走 form-data
	 *  - files: binary[]
	 * -  upload_only: '0' or '1 // 是否只上傳，不新增到媒體庫
	 *
	 * @param  \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function post_upload_callback( $request ) {
		// get data from form-data
		$file_params = $request->get_file_params();
		$body_params = $request->get_body_params();
		$upload_only = $body_params['upload_only'] ?? '0';

		if ( ! ! $file_params['files']['tmp_name'] ) {

			if ( ! function_exists( 'media_handle_upload' ) ) {
				require_once 'wp-admin/includes/image.php';
				require_once 'wp-admin/includes/file.php';
				require_once 'wp-admin/includes/media.php';
			}

			$upload_results   = [];
			$upload_overrides = [ 'test_form' => false ];
			$_FILES           = [];

			// 遍歷每個上傳的檔案
			foreach ( $file_params['files']['tmp_name'] as $key => $tmp_name ) {
				if ( ! empty( $tmp_name ) ) {
					$file = [
						'name'     => $file_params['files']['name'][ $key ],
						'type'     => $file_params['files']['type'][ $key ],
						'tmp_name' => $tmp_name,
						'error'    => $file_params['files']['error'][ $key ],
						'size'     => $file_params['files']['size'][ $key ],
					];

					$_FILES[ $key ] = $file;

					if ( $upload_only ) {
						// 直接上傳到 wp-content/uploads 不會新增到媒體庫
						$upload_result = \wp_handle_upload( $file, $upload_overrides );
						unset( $upload_result['file'] );
						$upload_result['id']   = null;
						$upload_result['type'] = $file['type'];
						$upload_result['name'] = $file['name'];
						$upload_result['size'] = $file['size'];
						if ( isset( $upload_result['error'] ) ) {
							return new \WP_REST_Response(
								[
									'code'    => 'upload_error',
									'message' => $upload_result['error'],
									'data'    => $upload_result,
								],
								400
							);
						}
					} else {
						// 將檔案上傳到媒體庫
						$attachment_id = \media_handle_upload(
							file_id: $key,
							post_id: 0
						);

						if ( \is_wp_error( $attachment_id ) ) {
							// 處理錯誤
							return new \WP_REST_Response(
								[
									'code'    => 'upload_error',
									'message' => $attachment_id->get_error_message(),
									'data'    => $upload_result,
								],
								400
							);
						}

						$upload_result = [
							'id'   => (string) $attachment_id,
							'url'  => \wp_get_attachment_url( $attachment_id ),
							'type' => $file['type'],
							'name' => $file['name'],
							'size' => $file['size'],
						];
					}

					$upload_results[] = $upload_result;
				}
			}

			// 返回上傳成功的訊息
			return \rest_ensure_response(
				[
					'code'    => 'upload_success',
					'message' => '檔案上傳成功',
					'data'    => $upload_results,
				]
			);

		}
	}

	/**
	 * Post upload callback
	 * 上傳檔案
	 * post 走 form-data
	 *  - files: binary[]
	 * -  upload_only: '0' or '1 // 是否只上傳，不新增到媒體庫
	 *
	 * @param  \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function post_upload_array_callback( $request ) {
		// get data from form-data
		$file_params = $request->get_file_params();
		$body_params = $request->get_body_params();
		$upload_only = $body_params['upload_only'] ?? '0';

		if ( ! ! $file_params['files']['tmp_name'] ) {

			if ( ! function_exists( 'media_handle_upload' ) ) {
				require_once 'wp-admin/includes/image.php';
				require_once 'wp-admin/includes/file.php';
				require_once 'wp-admin/includes/media.php';
			}

			$upload_results   = [];
			$upload_overrides = [ 'test_form' => false ];
			$_FILES           = [];

			// 遍歷每個上傳的檔案
			foreach ( $file_params['files']['tmp_name'] as $key => $tmp_name ) {
				if ( ! empty( $tmp_name ) ) {
					$file = [
						'name'     => $file_params['files']['name'][ $key ],
						'type'     => $file_params['files']['type'][ $key ],
						'tmp_name' => $tmp_name,
						'error'    => $file_params['files']['error'][ $key ],
						'size'     => $file_params['files']['size'][ $key ],
					];

					$_FILES[ $key ] = $file;

					if ( $upload_only ) {
						// 直接上傳到 wp-content/uploads 不會新增到媒體庫
						$upload_result = \wp_handle_upload( $file, $upload_overrides );
						unset( $upload_result['file'] );
						$upload_result['id']   = null;
						$upload_result['type'] = $file['type'];
						$upload_result['name'] = $file['name'];
						$upload_result['size'] = $file['size'];
						if ( isset( $upload_result['error'] ) ) {
							return new \WP_REST_Response(
								[
									'code'    => 'upload_error',
									'message' => $upload_result['error'],
									'data'    => $upload_result,
								],
								400
							);
						}
					} else {
						// 將檔案上傳到媒體庫
						$attachment_id = \media_handle_upload(
							file_id: $key,
							post_id: 0
						);

						if ( \is_wp_error( $attachment_id ) ) {
							// 處理錯誤
							return new \WP_REST_Response(
								[
									'code'    => 'upload_error',
									'message' => $attachment_id->get_error_message(),
									'data'    => $upload_result,
								],
								400
							);
						}

						$upload_result = [
							'id'   => (string) $attachment_id,
							'url'  => \wp_get_attachment_url( $attachment_id ),
							'type' => $file['type'],
							'name' => $file['name'],
							'size' => $file['size'],
						];
					}

					$upload_results[] = $upload_result;
				}
			}

			// 返回上傳成功的訊息
			return \rest_ensure_response(
				[
					'code'    => 'upload_success',
					'message' => '檔案上傳成功',
					'data'    => $upload_results,
				]
			);

		}
	}
}

Upload::instance();
