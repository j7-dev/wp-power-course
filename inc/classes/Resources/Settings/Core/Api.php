<?php

declare(strict_types=1);

namespace J7\PowerCourse\Resources\Settings\Core;

use J7\WpUtils\Classes\WP;
use J7\WpUtils\Classes\ApiBase;
use J7\PowerCourse\Resources\Settings\Model\Settings;

/** Settings Api */
final class Api extends ApiBase {
	use \J7\WpUtils\Traits\SingletonTrait;

	/** @var string Namespace */
	protected $namespace = 'power-course';

	/** @var array{endpoint: string, method: string, permission_callback: ?callable}[] APIs */
	protected $apis = [
		[
			'endpoint'            => 'settings',
			'method'              => 'get',
			'permission_callback' => null,
		],
		[
			'endpoint'            => 'settings',
			'method'              => 'post',
			'permission_callback' => null,
		],
	];

	/**
	 * 獲取選項
	 *
	 * @param \WP_REST_Request $request REST請求對象。
	 * @return \WP_REST_Response 返回包含選項資料的REST響應對象。
	 */
	public function get_settings_callback( \WP_REST_Request $request ): \WP_REST_Response {
		$settings = Settings::instance();
		return new \WP_REST_Response(
			[
				'code'    => 'get_options_success',
				'message' => '獲取選項成功',
				'data'    => $settings->to_array(),
			],
			200
		);
	}

	/**
	 * 更新選項
	 *
	 * @param \WP_REST_Request $request 包含更新選項所需資料的REST請求對象。
	 * @return \WP_REST_Response 返回包含操作結果的REST響應對象。成功時返回選項資料，失敗時返回錯誤訊息。
	 */
	public function post_settings_callback( \WP_REST_Request $request ): \WP_REST_Response {
		$body_params = $request->get_json_params();

		/** @var array<string, mixed> $body_params */
		$body_params = WP::sanitize_text_field_deep( $body_params, false, [ 'pc_watermark_text', 'auto_grant_courses' ] );

		// 自動授權課程清單不可包含外部課程，靜默過濾
		if ( isset( $body_params['auto_grant_courses'] ) && is_array( $body_params['auto_grant_courses'] ) ) {
			$body_params['auto_grant_courses'] = array_values(
				array_filter(
					$body_params['auto_grant_courses'],
					static function ( $item ) {
						$course_id = (int) ( $item['course_id'] ?? 0 );
						if ( $course_id <= 0 ) {
							return true; // 讓後續邏輯處理無效 ID
						}
						$product = \wc_get_product( $course_id );
						return ! ( $product instanceof \WC_Product_External );
					}
				)
			);
		}

		$settings = Settings::instance();
		$settings->set_properties( $body_params );
		$settings->save();

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
