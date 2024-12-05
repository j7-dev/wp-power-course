<?php
/**
 * Override Automattic\WooCommerce\Admin\API\Reports\Revenue\Query
 */

namespace J7\PowerCourse\Api\OverrideWCReports\Revenue;

use Automattic\WooCommerce\Admin\API\Reports\Revenue\Query as WCQuery;
use J7\PowerCourse\Api\OverrideWCReports\Orders\Stats\DataStore;

defined( 'ABSPATH' ) || exit;

/**
 * Query
 */
final class Query extends WCQuery {

	/**
	 * Get revenue data based on the current query vars.
	 *
	 * @return array
	 */
	public function get_data() {

		$args = \apply_filters( 'woocommerce_analytics_revenue_query_args', $this->get_query_vars() );

		$data_store = DataStore::instance();
		$results    = $data_store->get_data( $args );

		return \apply_filters( 'woocommerce_analytics_revenue_select_query', $results, $args );
	}
}
