<?php
/**
 * Revenue API
 */

declare(strict_types=1);

namespace J7\PowerCourse\Api\Reports;

use J7\WpUtils\Classes\ApiBase;
use J7\PowerCourse\Plugin;

/**
 * Revenue Api
 */
final class Revenue extends ApiBase {
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
			'endpoint'            => 'reports/revenue/stats',
			'method'              => 'get',
			'permission_callback' => null,
		],
	];


	public function get_reports_revenue_stats_callback( $request ) { // phpcs:ignore
		// 從請求中取得查詢參數
		$params = $request->get_query_params();

		// 設定預設的分頁參數
		$params['page']     = 1;
		$params['per_page'] = 10000; // 設定一個大數值以一次性取得所有記錄

		// 準備查詢參數，模仿 WooCommerce 的收入統計控制器
		$query_args = [
			'before'              => $params['before'] ?? null,
			'after'               => $params['after'] ?? null,
			'interval'            => $params['interval'] ?? '週',
			'page'                => $params['page'],
			'per_page'            => $params['per_page'],
			'orderby'             => $params['orderby'] ?? null,
			'order'               => $params['order'] ?? null,
			'segmentby'           => $params['segmentby'] ?? null,
			'fields'              => $params['fields'] ?? null,
			'force_cache_refresh' => $params['force_cache_refresh'] ?? false,
			'date_type'           => $params['date_type'] ?? null,
		];

		// 移除空值
		$query_args = array_filter($query_args);

		// 使用 WooCommerce 的收入查詢來獲取數據
		$query = new \Automattic\WooCommerce\Admin\API\Reports\Revenue\Query( $query_args );

		/**
		 * @var array{
		 *     totals: array{
		 *         orders_count: int,
		 *         num_items_sold: int,
		 *         total_sales: float,
		 *         coupons: float,
		 *         coupons_count: int,
		 *         refunds: float,
		 *         taxes: float,
		 *         shipping: float,
		 *         net_revenue: float,
		 *         gross_sales: float,
		 *         products: int,
		 *         segments: array<mixed>
		 *     },
		 *     intervals: array<array{
		 *         interval: string,
		 *         date_start: string,
		 *         date_start_gmt: string,
		 *         date_end: string,
		 *         date_end_gmt: string,
		 *         subtotals: array{
		 *             orders_count: int,
		 *             num_items_sold: int,
		 *             total_sales: float,
		 *             coupons: float,
		 *             coupons_count: int,
		 *             refunds: float,
		 *             taxes: float,
		 *             shipping: float,
		 *             net_revenue: float,
		 *             gross_sales: float,
		 *             products: int,
		 *             segments: array<mixed>
		 *         }
		 *     }>,
		 *     total: int,
		 *     pages: int
		 * } $data
		 */
		$data = (array) $query->get_data();

		// 如果沒有找到數據，返回空響應
		if (empty($data)) {
			return new \WP_REST_Response(
				[
					'code'    => 200,
					'message' => '未找到數據',
					'data'    => null,
				],
				200
			);
		}

		// 準備響應數據，格式與 WooCommerce 收入統計 API 相同
		$response_data = [
			'totals'    => $data['totals'] ?? [],
			'intervals' => $data['intervals'] ?? [],
		];

		return new \WP_REST_Response(
			$response_data,
			200
		);
	}
}
