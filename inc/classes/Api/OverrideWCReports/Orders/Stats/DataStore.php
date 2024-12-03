<?php
/**
 * Override Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore
 */

declare(strict_types=1);

namespace J7\PowerCourse\Api\OverrideWCReports\Orders\Stats;

use Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore as WCDataStore;
use Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\Segmenter;
use Automattic\WooCommerce\Admin\API\Reports\TimeInterval;
use Automattic\WooCommerce\Admin\API\Reports\Cache;
use J7\PowerCourse\Plugin;
use Automattic\WooCommerce\Admin\API\Reports\SqlQuery;


/**
 * DataStore
 */
final class DataStore extends WCDataStore {
	use \J7\WpUtils\Traits\SingletonTrait;

	/**
	 * Union query
	 *
	 * @var SqlQuery
	 */
	protected $total_union_query, $interval_union_query; // phpcs:ignore

	/**
	 * Base select
	 *
	 * @var string
	 */
	protected $base_select = '
						0 AS net_revenue,
						0 AS avg_order_value,
						0 as orders_count,
						0 AS avg_items_per_order,
						0 as num_items_sold,
						0 AS coupons,
						0 as coupons_count,
						0 as total_customers,
						0 AS total_sales,
						0 AS refunds,
						0 AS shipping,
						0 as gross_sales,
						0 as refunded_orders_count,
						0 as non_refunded_orders_count,
						course_meta.student_count as student_count
						';

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
		\add_action('power_course_before_totals_query', [ $this, 'modify_query' ], 10, 3);
		// \add_action('power_course_before_intervals_query', [ $this, 'modify_query' ], 10, 3);
	}

	/**
	 * Modify the query
	 *
	 * @param self                              $datastore Datastore instance
	 * @param array                             $query_args Query parameters.
	 * @param array{offset: int, per_page: int} $params                  Query limit parameters.
	 * @return void
	 */
	public function modify_query( self $datastore, $query_args, $params ): void {
		/** @var \DateTime $before */
		$before = $query_args['before'];
		/** @var \DateTime $after */
		$after = $query_args['after'];
		global $wpdb;
		$table_name = $wpdb->prefix . Plugin::COURSE_TABLE_NAME;
		$meta_key   = 'course_granted_at';

		// $student_join = "LEFT JOIN {$table_name} as avl ON avl.meta_key = '{$meta_key}'
		// AND avl.meta_value BETWEEN '{$after->format('Y-m-d H:i:s')}' AND '{$before->format('Y-m-d H:i:s')}'";

		$student_join = "LEFT JOIN (
					SELECT
							DATE_FORMAT(meta_value, '%Y-%m-%d') AS granted_date,
							COUNT(DISTINCT user_id) AS student_count
					FROM
							{$table_name}
					WHERE
							meta_key = '{$meta_key}'
							AND meta_value BETWEEN '{$after->format('Y-m-d H:i:s')}' AND '{$before->format('Y-m-d H:i:s')}'
							GROUP BY DATE_FORMAT(meta_value, '%Y-%m-%d')
			) course_meta ON DATE_FORMAT(wp_wc_order_stats.date_paid, '%Y-%m-%d') = course_meta.granted_date";

		$this->total_query->add_sql_clause( 'left_join', $student_join );
		$this->interval_query->add_sql_clause( 'left_join', $student_join );

		$this->compose_union_query( $datastore, $query_args, $params );
	}

	/**
	 * Modify the query
	 *
	 * @param self                              $datastore Datastore instance
	 * @param array                             $query_args Query parameters.
	 * @param array{offset: int, per_page: int} $params                  Query limit parameters.
	 * @return void
	 */
	private function compose_union_query( self $datastore, $query_args, $params ): void {
		/** @var \DateTime $before */
		$before = $query_args['before'];
		/** @var \DateTime $after */
		$after = $query_args['after'];
		global $wpdb;
		$table_name = $wpdb->prefix . Plugin::COURSE_TABLE_NAME;
		$meta_key   = 'course_granted_at';

		$this->total_union_query = new SqlQuery( $this->context . '_total_union' );
		$this->total_union_query->add_sql_clause(
			'select',
			$this->base_select
			);

		$this->total_union_query->add_sql_clause(
		'from',
		"
						(
						SELECT
						DATE_FORMAT(meta_value, '%Y-%m-%d') AS granted_date,
						COUNT(DISTINCT user_id) AS student_count
						FROM {$table_name}
						WHERE meta_key = '{$meta_key}'
						AND meta_value BETWEEN '{$after->format('Y-m-d H:i:s')}' AND '{$before->format('Y-m-d H:i:s')}'
						GROUP BY DATE_FORMAT(meta_value, '%Y-%m-%d')
						) course_meta
		"
		);

		$this->total_union_query->add_sql_clause(
		'left_join',
		"LEFT JOIN (
		SELECT DISTINCT DATE_FORMAT(date_paid, '%Y-%m-%d') AS order_date
		FROM {$wpdb->prefix}wc_order_stats
		WHERE date_paid BETWEEN '{$after->format('Y-m-d H:i:s')}' AND '{$before->format('Y-m-d H:i:s')}'
		) orders ON course_meta.granted_date = orders.order_date
		"
		);

		$this->total_union_query->add_sql_clause(
		'where',
		'AND orders.order_date IS NULL'
		);

		$total_union_query_statement = $this->total_union_query->get_query_statement();

		$this->total_query->add_sql_clause( 'union', $total_union_query_statement );

		// Interval query

		$this->interval_union_query = new SqlQuery( $this->context . '_interval_union' );

		$this->interval_union_query->add_sql_clause(
			'select',
			'
						course_meta.granted_date AS time_interval,
						NULL AS datetime_anchor,
						' . $this->base_select
			);

		$this->interval_union_query->add_sql_clause(
			'from',
			$this->total_union_query->get_sql_clause( 'from' )
		);

		$this->interval_union_query->add_sql_clause(
			'left_join',
			$this->total_union_query->get_sql_clause( 'left_join' )
		);

		$this->interval_union_query->add_sql_clause(
			'where',
			$this->total_union_query->get_sql_clause( 'where' )
		);

		$interval_union_query_statement = $this->interval_union_query->get_query_statement();

		$this->interval_query->add_sql_clause( 'union', $interval_union_query_statement );
	}


	/**
	 * Returns the report data based on normalized parameters.
	 * Will be called by `get_data` if there is no data in cache.
	 *
	 * @override ReportsDataStore::get_noncached_stats_data()
	 *
	 * @see get_data
	 * @see get_noncached_stats_data
	 * @param array    $query_args Query parameters.
	 * @param array    $params                  Query limit parameters.
	 * @param stdClass $data                    Reference to the data object to fill.
	 * @param int      $expected_interval_count Number of expected intervals.
	 * @return stdClass|WP_Error Data object `{ totals: *, intervals: array, total: int, pages: int, page_no: int }`, or error.
	 */
	public function get_noncached_stats_data( $query_args, $params, &$data, $expected_interval_count ) {
		global $wpdb;

		$table_name = self::get_db_table_name();

		if ( isset( $query_args['date_type'] ) ) {
			$this->date_column_name = $query_args['date_type'];
		}

		$this->initialize_queries();

		$selections = $this->selected_columns( $query_args );
		$this->add_time_period_sql_params( $query_args, $table_name );
		$this->add_intervals_sql_params( $query_args, $table_name );
		$this->add_order_by_sql_params( $query_args );
		$where_time  = $this->get_sql_clause( 'where_time' );
		$params      = $this->get_limit_sql_params( $query_args );
		$coupon_join = "LEFT JOIN (
					SELECT
						order_id,
						SUM(discount_amount) AS discount_amount,
						COUNT(DISTINCT coupon_id) AS coupons_count
					FROM
						{$wpdb->prefix}wc_order_coupon_lookup
					GROUP BY
						order_id
					) order_coupon_lookup
					ON order_coupon_lookup.order_id = {$wpdb->prefix}wc_order_stats.order_id";
		// Additional filtering for Orders report.
		$this->orders_stats_sql_filter( $query_args );
		$this->total_query->add_sql_clause( 'select', $selections );
		$this->total_query->add_sql_clause( 'left_join', $coupon_join );
		$this->total_query->add_sql_clause( 'where_time', $where_time );

		\do_action('power_course_before_totals_query', $this, $query_args, $params);

		$totals = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- cache ok, DB call ok, unprepared SQL ok.
			$this->total_query->get_query_statement(),
			ARRAY_A
		);

		if ( null === $totals ) {
			return new \WP_Error( 'woocommerce_analytics_revenue_result_failed', __( 'Sorry, fetching revenue data failed.', 'woocommerce' ) );
		}

		// phpcs:ignore Generic.Commenting.Todo.TaskFound
		// @todo Remove these assignements when refactoring segmenter classes to use query objects.
		$totals_query    = [
			'from_clause'       => $this->total_query->get_sql_clause( 'join' ),
			'where_time_clause' => $where_time,
			'where_clause'      => $this->total_query->get_sql_clause( 'where' ),
		];
		$intervals_query = [
			'select_clause'     => $this->get_sql_clause( 'select' ),
			'from_clause'       => $this->interval_query->get_sql_clause( 'join' ),
			'where_time_clause' => $where_time,
			'where_clause'      => $this->interval_query->get_sql_clause( 'where' ),
			'limit'             => $this->get_sql_clause( 'limit' ),
		];

		$unique_products            = $this->get_unique_product_count( $totals_query['from_clause'], $totals_query['where_time_clause'], $totals_query['where_clause'] );
		$totals[0]['products']      = $unique_products;
		$segmenter                  = new Segmenter( $query_args, $this->report_columns );
		$unique_coupons             = $this->get_unique_coupon_count( $totals_query['from_clause'], $totals_query['where_time_clause'], $totals_query['where_clause'] );
		$totals[0]['coupons_count'] = $unique_coupons;
		$totals[0]['segments']      = $segmenter->get_totals_segments( $totals_query, $table_name );
		$totals                     = (object) $this->cast_numbers( $totals[0] );

		$this->interval_query->add_sql_clause( 'select', $this->get_sql_clause( 'select' ) . ' AS time_interval' );
		$this->interval_query->add_sql_clause( 'left_join', $coupon_join );
		$this->interval_query->add_sql_clause( 'where_time', $where_time );

		\do_action('power_course_before_intervals_query', $this, $query_args, $params);

		$db_intervals = $wpdb->get_col(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- cache ok, DB call ok, , unprepared SQL ok.
			$this->interval_query->get_query_statement()
		);

		$db_interval_count = count( $db_intervals );

		$this->update_intervals_sql_params( $query_args, $db_interval_count, $expected_interval_count, $table_name );
		$this->interval_query->add_sql_clause( 'order_by', $this->get_sql_clause( 'order_by' ) );
		$this->interval_query->add_sql_clause( 'limit', $this->get_sql_clause( 'limit' ) );
		$this->interval_query->add_sql_clause( 'select', ", MAX({$table_name}.date_created) AS datetime_anchor" );

		if ( '' !== $selections ) {
			$this->interval_query->add_sql_clause( 'select', ', ' . $selections );
		}

		$intervals = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- cache ok, DB call ok, , unprepared SQL ok.
			$this->interval_query->get_query_statement(),
			ARRAY_A
		);

		if ( null === $intervals ) {
			return new \WP_Error( 'woocommerce_analytics_revenue_result_failed', __( 'Sorry, fetching revenue data failed.', 'woocommerce' ) );
		}

		if ( isset( $intervals[0] ) ) {
			$unique_coupons                = $this->get_unique_coupon_count( $intervals_query['from_clause'], $intervals_query['where_time_clause'], $intervals_query['where_clause'], true );
			$intervals[0]['coupons_count'] = $unique_coupons;
		}

		$data->totals    = $totals;
		$data->intervals = $intervals;

		if ( TimeInterval::intervals_missing( $expected_interval_count, $db_interval_count, $params['per_page'], $query_args['page'], $query_args['order'], $query_args['orderby'], count( $intervals ) ) ) {
			$this->fill_in_missing_intervals( $db_intervals, $query_args['adj_after'], $query_args['adj_before'], $query_args['interval'], $data );
			$this->sort_intervals( $data, $query_args['orderby'], $query_args['order'] );
			$this->remove_extra_records( $data, $query_args['page'], $params['per_page'], $db_interval_count, $expected_interval_count, $query_args['orderby'], $query_args['order'] );

		} else {
			$this->update_interval_boundary_dates( $query_args['after'], $query_args['before'], $query_args['interval'], $data->intervals );
		}

		$segmenter->add_intervals_segments( $data, $intervals_query, $table_name );

		return $data;
	}


	/**
	 * Wrapper around Cache::set().
	 *
	 * @param string $cache_key Cache key.
	 * @param mixed  $value     New value.
	 * @return bool
	 */
	protected function set_cached_data( $cache_key, $value ) {
		if ( $this->should_use_cache() ) {
			$transient_version = Cache::get_version();
			$transient_value   = [
				'version' => $transient_version,
				'value'   => $value,
			];

			$result = \set_transient( $cache_key, $transient_value, $this->cache_timeout );

			return $result;
		}

		return true;
	}
}
