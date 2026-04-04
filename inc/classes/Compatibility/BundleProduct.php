<?php

declare (strict_types = 1);

namespace J7\PowerCourse\Compatibility;

/**
 * 銷售方案相容性
 */
final class BundleProduct {
	/**
	 * 取得所有銷售方案的 id
	 *
	 * @return array<string> 所有銷售方案的 id
	 */
	public static function get_all_bundle_products(): array {
		global $wpdb;

		$bundle_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT DISTINCT p.ID FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE p.post_type = 'product' AND pm.meta_key = 'bundle_type'"
		)
		);

		return $bundle_ids;
	}

	/**
	 * 將所有銷售方案 set_catalog_visibility 為 hidden
	 *
	 * @since 0.8.19
	 * @return void
	 */
	public static function set_catalog_visibility_to_hidden(): void {
		$bundle_ids = self::get_all_bundle_products();
		foreach ( $bundle_ids as $bundle_id ) {
			$bundle_product = \wc_get_product( $bundle_id );
			if ( ! $bundle_product ) {
				continue;
			}
			$bundle_product->set_catalog_visibility( 'hidden' );
			$bundle_product->save();
		}
	}

	/**
	 * 遷移 exclude_main_course 功能
	 *
	 * - exclude_main_course = 'yes'：pbp_product_ids 不變，直接刪除 exclude_main_course meta
	 * - exclude_main_course = 'no' 或不存在：將 link_course_id 加入 pbp_product_ids（若尚未包含），
	 *   並在 pbp_product_quantities 中設定 qty = 1，然後刪除 exclude_main_course meta
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public static function migrate_exclude_main_course(): void {
		$bundle_ids = self::get_all_bundle_products();

		foreach ( $bundle_ids as $bundle_id ) {
			$product = \wc_get_product( $bundle_id );
			if ( ! $product ) {
				continue;
			}

			$helper = \J7\PowerCourse\BundleProduct\Helper::instance( $product );
			if ( ! $helper || ! $helper->is_bundle_product ) {
				continue;
			}

			$exclude_main_course = (string) \get_post_meta( (int) $bundle_id, 'exclude_main_course', true );
			$link_course_id      = $helper->link_course_id;

			// 如果沒有連結課程，跳過
			if ( $link_course_id <= 0 ) {
				// 清理 exclude_main_course meta
				\delete_post_meta( (int) $bundle_id, 'exclude_main_course' );
				continue;
			}

			// exclude = 'yes'：不動 pbp_product_ids
			if ( 'yes' !== $exclude_main_course ) {
				// exclude = 'no' 或不存在：補入目前課程
				$current_ids = $helper->get_product_ids();
				if ( ! in_array( (string) $link_course_id, $current_ids, true ) ) {
					// 將 link_course_id 加到最前面
					array_unshift( $current_ids, (string) $link_course_id );
					$helper->set_bundled_ids( array_map( 'intval', $current_ids ) );
				}

				// 設定 quantities 中該課程的數量為 1（若不存在）
				$quantities = $helper->get_product_quantities();
				if ( ! isset( $quantities[ (string) $link_course_id ] ) ) {
					$quantities[ (string) $link_course_id ] = 1;
					$helper->set_product_quantities( $quantities );
				}
			}

			// 清理 exclude_main_course meta
			\delete_post_meta( (int) $bundle_id, 'exclude_main_course' );
		}
	}
}
