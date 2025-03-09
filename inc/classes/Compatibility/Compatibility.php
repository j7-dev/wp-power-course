<?php
/**
 * Compatibility 不同版本間的相容性設定
 * from v0.5.0
 */

declare (strict_types = 1);

namespace J7\PowerCourse\Compatibility;

use J7\PowerCourse\Plugin;
use J7\PowerCourse\Resources\Chapter\Utils\MetaCRUD as AVLChapterMeta;
use J7\PowerCourse\AbstractTable;


/**
 * Class Compatibility
 */
final class Compatibility {
	use \J7\WpUtils\Traits\SingletonTrait;

	/** Constructor */
	public function __construct() {
		\delete_option('pc_compatibility_action_scheduled');

		ApiOptimize::instance();
		// 升級成功後執行
		\add_action( 'upgrader_process_complete', [ __CLASS__, 'compatibility' ]);
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
		AbstractTable::create_chapter_table();

		// 判斷是否已經有 wp_pc_email_records 這張 table，沒有就建立
		AbstractTable::create_email_records_table();

		// 判斷是否已經有 wp_pc_student_logs 這張 table，沒有就建立
		AbstractTable::create_student_logs_table();

		// 將 table course_id 重新命名為 post_id
		self::alter_course_table_column();

		// 將 avl_coursemeta 的 finished_chapter_ids 改為 avl_chaptermeta 的 finished_at
		self::convert_fields();

		// 將 bundle_type 統一為 'bundle'
		self::bundle_type();

		// 0.8.0 之後使用新的章節結構
		Chapter::migrate_chapter_to_new_structure();
		// 儲存章節使用的編輯器
		Chapter::set_editor_meta_to_chapter();
		Course::set_editor_meta_to_course();

		/**
		 * ============== END 相容性代碼 ==============
		 */

		\J7\WpUtils\Classes\WC::log(Plugin::$version, '已執行兼容性設定');
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

		$table_name = $wpdb->prefix . Plugin::COURSE_TABLE_NAME;

		// 先檢查 post_id 欄位是否已存在
		$post_id_exists = $wpdb->get_row("SHOW COLUMNS FROM {$table_name} WHERE Field = 'post_id'"); //phpcs:ignore
		if ($post_id_exists) {
			// 如果 post_id 已存在，則不需要進行轉換
			return;
		}

		// 檢查 course_id 欄位是否存在
		$column_info = $wpdb->get_row("SHOW COLUMNS FROM {$table_name} WHERE Field = 'course_id'");//phpcs:ignore
		if (!$column_info) {
			return;
		}

		$column_type = $column_info->Type; //phpcs:ignore

		// SQL 查詢來重新命名欄位
		$sql = "ALTER TABLE {$table_name} CHANGE COLUMN course_id post_id {$column_type}";

		// 執行查詢
		$result = $wpdb->query($sql); // phpcs:ignore

		if ($result === false) {
			error_log('無法重新命名欄位: ' . $wpdb->last_error);
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

	/**
	 * 將 bundle_type 統一為 'bundle'
	 *
	 * @since 2024-12-26
	 * @return void
	 */
	private static function bundle_type(): void {

		global $wpdb;

		try {
			// 把非 bundle 的 meta_value 改為 bundle
			$wpdb->get_results(
			$wpdb->prepare(
			'UPDATE %1$s SET meta_value = "bundle" WHERE meta_key = "bundle_type" AND meta_value != "bundle"',
			$wpdb->postmeta,
			)
			);
		} catch (\Throwable $th) {
			// TEST 印出 ErrorLog 記得移除
			\J7\WpUtils\Classes\ErrorLog::info( $th, 'bundle_type' );
		}
	}
}
