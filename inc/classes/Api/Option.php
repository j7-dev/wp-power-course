<?php
/**
 * Option API
 */

declare(strict_types=1);

namespace J7\PowerCourse\Api;

use J7\WpUtils\Classes\WP;
use J7\WpUtils\Classes\ApiBase;
use J7\PowerCourse\Utils\Duplicate;
/**
 * Option Api
 */
final class Option extends ApiBase {
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
	 * @var array{endpoint: string, method: string, permission_callback: ?callable}[]
	 * - endpoint: string
	 * - method: 'get' | 'post' | 'patch' | 'delete'
	 * - permission_callback : callable
	 */
	protected $apis = [
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
		[
			'endpoint'            => 'duplicate/(?P<id>\d+)',
			'method'              => 'post',
			'permission_callback' => null,
		],
	];

	/**
	 * Fields
	 *
	 * @var array<string, string|int> $fields 允許獲取 & 預設值
	 */
	private $fields = [
		'course_permalink_structure'    => '',
		'hide_myaccount_courses'        => 'no', // 是否隱藏我的帳戶中的課程
		'fix_video_and_tabs_mobile'     => 'no', // 手機板時，影片以及 tabs 黏性(sticky)置頂
		'pc_header_offset'              => '0', // 黏性的偏移距離
		'hide_courses_in_main_query'    => 'no', // 是否在主查詢中隱藏課程
		'hide_courses_in_search_result' => 'no', // 是否在搜尋結果中隱藏課程
		'pc_watermark_qty'              => 0, // 浮水印數量
		'pc_watermark_color'            => 'rgba(255, 255, 255, 0.5)', // 浮水印顏色
		'pc_watermark_interval'         => 10, // 浮水印間隔
		'pc_watermark_text'             => '用戶 {display_name} 正在觀看 {post_title} IP:{ip} <br /> Email:{email}', // 浮水印文字
		'pc_pdf_watermark_qty'          => 0, // PDF 浮水印數量
		'pc_pdf_watermark_color'        => 'rgba(255, 255, 255, 0.5)', // PDF 浮水印顏色
		'pc_pdf_watermark_text'         => '用戶 {display_name} 正在觀看 {post_title} IP:{ip} \n Email:{email}', // PDF 浮水印文字
	];

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

		/** @var array<string, mixed> $body_params */
		$body_params = WP::sanitize_text_field_deep( $body_params, false, [ 'pc_watermark_text' ] );

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


	/**
	 * 複製
	 *
	 * @param \WP_REST_Request $request 包含更新選項所需資料的REST請求對象。
	 * @return \WP_REST_Response 返回包含操作結果的REST響應對象。成功時返回選項資料，失敗時返回錯誤訊息。
	 * @phpstan-ignore-next-line
	 */
	public function post_duplicate_with_id_callback( \WP_REST_Request $request ): \WP_REST_Response {
		$id = $request['id'] ?? null;

		if (!$id || !is_numeric( $id ) ) {
			return new \WP_REST_Response(
				[
					'message' => 'id is required',
				],
				400
			);
		}

		$duplicate = new Duplicate();
		$new_id    = $duplicate->process( (int) $id, true, true );

		return new \WP_REST_Response(
			[
				'code'    => 'post_duplicate_success',
				'message' => '複製成功',
				'data'    => $new_id,
			],
			200
			);
	}
}
