<?php
/**
 * Compatibility 不同版本間的相容性設定
 * from v0.5.0
 */

declare (strict_types = 1);

namespace J7\PowerCourse;

use J7\PowerCourse\Plugin;
use J7\PowerCourse\Resources\Chapter\MetaCRUD as AVLChapterMeta;

/**
 * Class Compatibility
 */
final class Compatibility {
	use \J7\WpUtils\Traits\SingletonTrait;
	use \J7\PowerCourse\TableTrait;

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

		/**
		 * ============== START 相容性代碼 ==============
		 */

		// 將 course_granted_at 從 timestamp 轉為 Y-m-d H:i:s
		self::convert_timestamp_to_date();

		// 判斷是否已經有 wp_pc_avl_chaptermeta 這張 table，沒有就建立
		self::create_chapter_table();

		// 判斷是否已經有 wp_pc_email_records 這張 table，沒有就建立
		self::create_email_records_table();

		// 將 table course_id 重新命名為 post_id
		self::alter_course_table_column();

		// 將 avl_coursemeta 的 finished_chapter_ids 改為 avl_chaptermeta 的 finished_at
		self::convert_fields();

		/**
		 * ============== END 相容性代碼 ==============
		 */

		// ❗不要刪除此行，註記已經執行過相容設定
		\update_option('pc_compatibility_action_scheduled', Plugin::$version);
	}

	/**
	 * 將 course_granted_at 從 timestamp 轉為 Y-m-d H:i:s
	 *
	 * @deprecated
	 * @return void
	 */
	private static function convert_timestamp_to_date(): void {
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
	}

	/**
	 * 重新命名 course_id 欄位為 post_id
	 *
	 * @deprecated
	 *
	 * @return void
	 */
	private static function alter_course_table_column(): void {
		global $wpdb;

		// 取得表格名稱前綴
		$table_name = $wpdb->prefix . Plugin::COURSE_TABLE_NAME;

		// 取得欄位的資料類型
		$column_info = $wpdb->get_row("SHOW COLUMNS FROM {$table_name} WHERE Field = 'course_id'"); //phpcs:ignore
		if (!$column_info) {
			// 檢查如果 course_id 欄位不存在，則不執行
			return;
		}

		$column_type = $column_info->Type; //phpcs:ignore

		// SQL 查詢來重新命名欄位
		$sql = "ALTER TABLE {$table_name} CHANGE COLUMN course_id post_id {$column_type}";

		// 執行查詢
		$result = $wpdb->query($sql); // phpcs:ignore

		if ($result === false) {
			error_log('無法重新命名欄位: ' . $wpdb->last_error);
		} else {
			error_log('欄位重新命名成功');
		}
	}


	/**
	 * 將 avl_coursemeta 的 finished_chapter_ids 改為 avl_chaptermeta 的 finished_at
	 *
	 * @deprecated
	 *
	 * @return void
	 */
	private static function convert_fields(): void {
		global $wpdb;

		// 取得表格名稱前綴
		$table_name = $wpdb->prefix . Plugin::COURSE_TABLE_NAME;

		// 取得所有 meta_key = 'finished_chapter_ids' 的資料
		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %1$s WHERE meta_key = "%2$s"',
				$table_name,
				'finished_chapter_ids',
			)
		);

		foreach ($results as $item) {
			$user_id    = (int) $item->user_id;
			$course_id  = (int) $item->post_id;
			$chapter_id = (int) $item->meta_value;

			AVLChapterMeta::update(
				$chapter_id,
				$user_id,
				'finished_at',
				\wp_date('Y-m-d H:i:s'),
			);
		}

		// 刪除 avl_coursemeta 的 finished_chapter_ids
		$wpdb->delete(
			$table_name,
			[ 'meta_key' => 'finished_chapter_ids' ],
		);
	}
}
