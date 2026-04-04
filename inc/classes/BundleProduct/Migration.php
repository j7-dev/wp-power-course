<?php
/**
 * 銷售方案向下相容遷移
 *
 * 將舊版 exclude_main_course 邏輯遷移至 pbp_product_ids 列表
 * 並初始化 pbp_product_quantities（預設所有商品數量為 1）
 */

declare( strict_types=1 );

namespace J7\PowerCourse\BundleProduct;

use J7\WpUtils\Classes\WC\Product as WcProduct;

/**
 * 銷售方案遷移工具
 */
final class Migration {

	/**
	 * 遷移標記 option key
	 * 設定為 '1' 表示已完成遷移
	 *
	 * @var string
	 */
	const MIGRATED_OPTION_KEY = 'pc_bundle_qty_migrated';

	/**
	 * 執行一次性遷移
	 * 將 exclude_main_course meta 邏輯轉換為 pbp_product_ids 列表
	 * 並為所有銷售方案初始化 pbp_product_quantities（預設所有商品 qty=1）
	 *
	 * 使用 update_option 標記，確保只執行一次（可透過清除 option 重新執行）
	 *
	 * @return void
	 */
	public static function migrate_exclude_main_course(): void {
		// 已遷移則跳過
		if ( \get_option( self::MIGRATED_OPTION_KEY ) === '1' ) {
			return;
		}

		// 取得所有 bundle products
		/** @var array<int> $bundle_product_ids */
		$bundle_product_ids = \get_posts(
			[
				'post_type'   => 'product',
				'numberposts' => -1,
				'post_status' => [ 'any' ],
				'meta_key'    => 'bundle_type', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'  => 'bundle', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'fields'      => 'ids',
			]
		);

		foreach ( $bundle_product_ids as $bundle_product_id ) {
			self::migrate_single_bundle( (int) $bundle_product_id );
		}

		// 標記已完成遷移
		\update_option( self::MIGRATED_OPTION_KEY, '1' );
	}

	/**
	 * 遷移單一銷售方案
	 *
	 * @param int $bundle_product_id 銷售方案商品 ID
	 * @return void
	 */
	private static function migrate_single_bundle( int $bundle_product_id ): void {
		$product = \wc_get_product( $bundle_product_id );
		if ( ! $product ) {
			return;
		}

		$helper = Helper::instance( $product );
		if ( ! $helper?->is_bundle_product ) {
			return;
		}

		// 讀取現有 meta
		$exclude_main_course = (string) \get_post_meta( $bundle_product_id, 'exclude_main_course', true );
		$link_course_id      = $helper->link_course_id;
		$current_ids         = $helper->get_product_ids();

		// 1. 處理 pbp_product_ids：
		//    若 exclude_main_course !== 'yes'（即 'no' 或空值），且課程 ID 不在列表中，加入
		if ( $exclude_main_course !== 'yes' && $link_course_id > 0 ) {
			if ( ! in_array( (string) $link_course_id, $current_ids, true ) ) {
				WcProduct::update_meta_array(
					$bundle_product_id,
					Helper::INCLUDE_PRODUCT_IDS_META_KEY,
					array_merge( [ (string) $link_course_id ], $current_ids )
				);
				// 重新讀取更新後的 ids
				$current_ids = $helper->get_product_ids();
			}
		}

		// 2. 初始化 pbp_product_quantities（若尚未設定）
		$existing_quantities = \get_post_meta( $bundle_product_id, Helper::PRODUCT_QUANTITIES_META_KEY, true );
		if ( ! $existing_quantities ) {
			$quantities = [];
			foreach ( $current_ids as $product_id ) {
				$quantities[ (string) $product_id ] = 1;
			}
			$helper->set_product_quantities( $quantities );
		}
	}

	/**
	 * 重置遷移標記（用於測試或強制重新遷移）
	 *
	 * @return void
	 */
	public static function reset_migration(): void {
		\delete_option( self::MIGRATED_OPTION_KEY );
	}
}
