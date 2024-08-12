<?php
/**
 * Option API
 */

declare(strict_types=1);

namespace J7\PowerCourse\Api;

use J7\WpUtils\Classes\WP;
use J7\PowerCourse\Plugin;

/**
 * Option Api
 */
final class Option {
	use \J7\WpUtils\Traits\SingletonTrait;
	use \J7\WpUtils\Traits\ApiRegisterTrait;

	/**
	 * APIs
	 *
	 * @var array{endpoint: string, method: string, permission_callback: ?callable}[]
	 * - endpoint: string
	 * - method: 'get' | 'post' | 'patch' | 'delete'
	 * - permission_callback : callable
	 */
	private $apis = [
		[
			'endpoint'            => 'options',
			'method'              => 'get',
			'permission_callback' => null,
		],
		[
			'endpoint'            => 'options',
			'method'              => 'post',
			'permission_callback' => null,
		],
	];

	/**
	 * Fields
	 *
	 * @var array<string, string> $fields 允許獲取 & 儲存的選項
	 */
	private $fields = [
		'bunny_library_id'                  => '',
		'bunny_cdn_hostname'                => '',
		'bunny_stream_api_key'              => '',
		'override_course_product_permalink' => 'yes',
		'course_permalink_structure'        => 'courses',
	];

	/**
	 * Constructor.
	 */
	public function __construct() {
		\add_action( 'rest_api_init', [ $this, 'register_api_products' ] );
	}

	/**
	 * Register products API
	 *
	 * @return void
	 */
	public function register_api_products(): void {
		$this->register_apis(
		apis: $this->apis,
		namespace: Plugin::$kebab,
		default_permission_callback: fn() => \current_user_can( 'manage_options' ),
		);
	}

	/**
	 * 獲取選項
	 *
	 * @param \WP_REST_Request $request REST請求對象。
	 * @return \WP_REST_Response 返回包含選項資料的REST響應對象。
	 * @phpstan-ignore-next-line
	 */
	public function get_options_callback( \WP_REST_Request $request ): \WP_REST_Response {
		$options = [];
		$fields  = $this->fields;

		foreach ( $fields as $option_name => $default ) {
			$options[ $option_name ] = \get_option( $option_name, $default );
		}

		return new \WP_REST_Response(
			[
				'code'    => 'get_options_success',
				'message' => '獲取選項成功',
				'data'    => $options,
			],
			200
		);
	}

	/**
	 * 更新選項
	 *
	 * @param \WP_REST_Request $request 包含更新選項所需資料的REST請求對象。
	 * @return \WP_REST_Response 返回包含操作結果的REST響應對象。成功時返回選項資料，失敗時返回錯誤訊息。
	 * @phpstan-ignore-next-line
	 */
	public function post_options_callback( \WP_REST_Request $request ): \WP_REST_Response {
		$body_params = $request->get_json_params();

		$body_params = WP::sanitize_text_field_deep( $body_params, false );

		$allowed_fields = array_keys( $this->fields );

		foreach ( $body_params as $key => $value ) {
			if ( in_array( $key, $allowed_fields, true ) ) {
				\update_option( $key, $value );
			}
		}

		return new \WP_REST_Response(
			[
				'code'    => 'post_user_success',
				'message' => '修改成功',
				'data'    => $body_params,
			],
			200
			);
	}
}

Option::instance();
