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
	 * @var array{endpoint:string, method:string}[]
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
	 * @return \WP_REST_Response
	 * @phpstan-ignore-next-line
	 */
	public function post_upload_callback( $request ): \WP_REST_Response {
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

			if (\is_array($file_params['files']['tmp_name'])) {
				return $this->handle_multiple_upload( $file_params['files'], $upload_only);
			}
			return $this->handle_single_upload( $file_params['files'], $upload_only);

		}

		return new \WP_REST_Response(
			[
				'code'    => 'upload_error',
				'message' => '檔案上傳失敗',
				'data'    => $file_params,
			],
			400
		);
	}

	/**
	 * Handle Single upload
	 *
	 * @param array{name:string,type:string,tmp_name:string,error:int,size:int} $file 檔案資訊
	 * @param string|null                                                       $upload_only 是否只上傳，不新增到媒體庫 '0' or '1'
	 *
	 * @return \WP_REST_Response
	 */
	private function handle_single_upload( array $file, ?string $upload_only = '0' ): \WP_REST_Response {

		$_FILES['0'] = $file;

		if ( $upload_only ) {
			// 直接上傳到 wp-content/uploads 不會新增到媒體庫
			$upload_overrides = [ 'test_form' => false ];
			$upload_result    = \wp_handle_upload( $file, $upload_overrides );
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
				file_id: '0',
				post_id: 0
			);

			if ( \is_wp_error( $attachment_id ) ) {
				// 處理錯誤
				return new \WP_REST_Response(
					[
						'code'    => 'upload_error',
						'message' => $attachment_id->get_error_message(),
						'data'    => $file,
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

		// 返回上傳成功的訊息
		return new \WP_REST_Response(
			[
				'code'    => 'upload_success',
				'message' => '檔案上傳成功',
				'data'    => $upload_result,
			],
		);
	}

	/**
	 * Handle multiple upload
	 *
	 * @param array{name:string[],type:string[],tmp_name:string[],error:int[],size:int[]} $files 檔案資訊
	 * @param string|null                                                                 $upload_only 是否只上傳，不新增到媒體庫 '0' or '1'
	 *
	 * @return \WP_REST_Response
	 */
	private function handle_multiple_upload( array $files, ?string $upload_only = '0' ): \WP_REST_Response {
		$upload_results   = [];
		$upload_overrides = [ 'test_form' => false ];
		$_FILES           = [];

		// 遍歷每個上傳的檔案
		foreach ( $files['tmp_name'] as $key => $tmp_name ) {
			if ( ! empty( $tmp_name ) ) {
				$file = [
					'name'     => $files['name'][ $key ],
					'type'     => $files['type'][ $key ],
					'tmp_name' => $tmp_name,
					'error'    => $files['error'][ $key ],
					'size'     =>$files['size'][ $key ],
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
								'data'    => $file,
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
		return new \WP_REST_Response(
			[
				'code'    => 'upload_success',
				'message' => '檔案上傳成功',
				'data'    => $upload_results,
			],
		);
	}
}

Upload::instance();
