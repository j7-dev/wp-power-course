<?php
/**
 * Compatibility 不同版本間的相容性設定
 */

declare (strict_types = 1);

namespace J7\PowerCourse;

use J7\PowerCourse\Plugin;

/**
 * Class Compatibility
 */
final class Compatibility {
	use \J7\WpUtils\Traits\SingletonTrait;

	const AS_COMPATIBILITY_ACTION = 'pc_compatibility_action_scheduler';

	/**
	 * Constructor
	 */
	public function __construct() {
		// 排程只執行一次的兼容設定
		\add_action( 'init', [ __CLASS__, 'compatibility_action_scheduler' ] );
		\add_action( self::AS_COMPATIBILITY_ACTION, [ __CLASS__, 'compatibility' ]);
	}


	/**
	 * 排程只執行一次的兼容設定
	 *
	 * @return void
	 */
	public static function compatibility_action_scheduler(): void {
		$scheduled_version = \get_option('pc_compatibility_action_scheduled');
		if ($scheduled_version === Plugin::$version) {
			return;
		}
		\as_enqueue_async_action( self::AS_COMPATIBILITY_ACTION, [] );
	}

	/**
	 * 執行排程
	 *
	 * @return void
	 */
	public static function compatibility(): void {

		// START 相容性代碼

		global $wpdb;

		$table_name = $wpdb->prefix . Plugin::COURSE_TABLE_NAME;

		/** @var array<int, object{meta_id: string, meta_value: string}> */
		$results = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT meta_id, meta_value FROM %1$s WHERE meta_key = "%2$s"',
					$table_name,
					'course_granted_at',
				)
			);

		foreach ($results as $item) {
			if (!\is_numeric($item->meta_value)) {
				continue;
			}
			$meta_id           = (int) $item->meta_id;
			$timestamp_to_date = \wp_date('Y-m-d H:i:s', (int) $item->meta_value);
			$wpdb->update(
				$table_name,
				[ 'meta_value' => $timestamp_to_date ],
				[ 'meta_id' => $meta_id ]
			);
		}

		// 判斷是否已經有 wp_pc_avl_chaptermeta 這張 table
		global $wpdb;
		$table_name = $wpdb->prefix . Plugin::CHAPTER_TABLE_NAME;
		$wpdb->query("SHOW TABLES LIKE '$table_name'"); // phpcs:ignore
		if ($wpdb->num_rows === 0) {
			Plugin::create_chapter_table();
		}

		// END 相容性代碼
		// ❗不要刪除此行，註記已經執行過相容設定
		\update_option('pc_compatibility_action_scheduled', Plugin::$version);
	}
}
