<?php
/**
 * Revenue API
 */

declare(strict_types=1);

namespace J7\PowerCourse\Api\OverrideWCReports\Revenue;

use J7\WpUtils\Classes\ApiBase;

/**
 * Revenue Api
 */
final class Api extends ApiBase {
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


	/**
	 * 報表欄位
	 *
	 * @var array<string, string>
	 */
	protected $extra_report_columns = [
		// 取得退款訂單數量
		'refunded_orders_count'     => 'SUM( CASE WHEN wp_wc_order_stats.parent_id = 0 AND wp_wc_order_stats.status = "wc-refunded" THEN 1 ELSE 0 END ) as refunded_orders_count',
		// 取得不包含退款的訂單數量
		'non_refunded_orders_count' => 'SUM( CASE WHEN wp_wc_order_stats.parent_id = 0 AND wp_wc_order_stats.status != "wc-refunded" THEN 1 ELSE 0 END ) as non_refunded_orders_count',
		// 取得學生數量
		'student_count'             => 'COALESCE(SUM(student_count), 0) as student_count',
		// 完成的章節數量
		'finished_chapters_count'   => 'COALESCE(SUM(finished_chapters_count), 0) as finished_chapters_count',
	];


	/**
	 * 報表欄位型別 intval|floatval
	 *
	 * @var array<string, string>
	 */
	protected $extra_report_column_types = [
		// 取得退款訂單數量
		'refunded_orders_count'     => 'intval',
		// 取得不包含退款的訂單數量
		'non_refunded_orders_count' => 'intval',
		// 取得學生數量
		'student_count'             => 'intval',
		// 完成的章節數量
		'finished_chapters_count'   => 'intval',
	];

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();

		\add_filter( 'woocommerce_admin_report_columns', [ $this, 'add_report_columns' ], 100, 3 );
		\add_filter( 'woocommerce_rest_reports_column_types', [ $this, 'add_report_column_types' ], 100, 2 );
	}



	/**
	 * 取得報表收入統計資料 API
	 *
	 * @param \WP_REST_Request $request 請求
	 * @return \WP_REST_Response
	 */
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
			'force_cache_refresh' => $params['force_cache_refresh'] ?? false,
			'date_type'           => $params['date_type'] ?? null,
			'fields'              => [
				'net_revenue',
				'avg_order_value',
				'orders_count',
				'avg_items_per_order',
				'num_items_sold',
				'coupons',
				'coupons_count',
				'total_customers',
				'total_sales',
				'refunds',
				// 'taxes',
				'shipping',
				'gross_sales',
			],

		];

		$extra_report_keys = array_keys($this->extra_report_columns);
		foreach ($extra_report_keys as $extra_report_key) {
			$query_args['fields'][] = $extra_report_key;
		}

		// 移除空值
		$query_args = array_filter($query_args);

		// 使用 WooCommerce 的收入查詢來獲取數據
		$query = new Query( $query_args );

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
		$data = $query->get_data(); // 是物件

		$filtered_data = \apply_filters('power_course_reports_revenue_stats', $data, $query_args);

		// 如果沒有找到數據，返回空響應
		if (empty($filtered_data)) {
			return new \WP_REST_Response(
				[
					'code'    => 200,
					'message' => '未找到數據',
					'data'    => null,
				],
				200
			);
		}

		return new \WP_REST_Response(
			$filtered_data,
			200
		);
	}


	/**
	 * 添加報表欄位
	 *
	 * @param array<string, string> $columns 欄位 + 查詢語句
	 * @param string                $context 資料表名稱 orders_stats
	 * @param string                $table_name 資料表名稱 wp_wc_order_stats
	 * @return array<string, string>
	 */
	public function add_report_columns( $columns, $context, $table_name ) {
		return array_merge($columns, $this->extra_report_columns);
	}


	/**
	 * 添加報表欄位類型
	 *
	 * @param array<string, string> $column_types 欄位類型
	 * @param array<string, mixed>  $array 數據
	 * @return array<string, string>
	 */
	public function add_report_column_types( $column_types, $array ) {
		return array_merge($column_types, $this->extra_report_column_types);
	}
}
