<?php

declare (strict_types = 1);

namespace J7\PowerCourse\Compatibility;

use J7\PowerCourse\Plugin;
use J7\PowerCourse\AbstractTable;
use J7\Powerhouse\Settings\Model\Settings as PowerhouseSettings;


/** Class Compatibility 不同版本間的相容性設定 */
final class Compatibility {
	use \J7\WpUtils\Traits\SingletonTrait;

	const AS_COMPATIBILITY_ACTION = 'pc_compatibility_action_scheduler';

	/** Constructor */
	public function __construct() {
		$scheduled_version = \get_option('pc_compatibility_action_scheduled');
		if ($scheduled_version === Plugin::$version) {
			return;
		}

		\delete_option('pc_compatibility_action_scheduled');

		ApiOptimize::instance();
		// 升級成功後執行
		\add_action( 'upgrader_process_complete', [ __CLASS__, 'compatibility' ]);

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
		\as_enqueue_async_action( self::AS_COMPATIBILITY_ACTION, [] );
	}


	/**
	 * 執行排程
	 *
	 * @return void
	 */
	public static function compatibility(): void {

		self::add_post_meta_to_course_product();
		/**
		 * ============== START 相容性代碼 ==============
		 */

		// 判斷是否已經有 wp_pc_avl_chaptermeta 這張 table，沒有就建立
		AbstractTable::create_chapter_table();

		// 判斷是否已經有 wp_pc_email_records 這張 table，沒有就建立
		AbstractTable::create_email_records_table();

		// 判斷是否已經有 wp_pc_student_logs 這張 table，沒有就建立
		AbstractTable::create_student_logs_table();

		// 0.8.0 之後使用新的章節結構
		Chapter::migrate_chapter_to_new_structure();
		// 儲存章節使用的編輯器
		Chapter::set_editor_meta_to_chapter();
		Course::set_editor_meta_to_course();

		BundleProduct::set_catalog_visibility_to_hidden();

		// 0.9.0 之前版本
		if (version_compare(Plugin::$version, '0.9.0', '<=')) {
			self::migration_bunny_settings();
		}

		/**
		 * ============== END 相容性代碼 ==============
		 */

		// ❗不要刪除此行，註記已經執行過相容設定
		\update_option('pc_compatibility_action_scheduled', Plugin::$version);

		Plugin::logger(Plugin::$version . ' 已執行兼容性設定', 'info');
	}


	/**
	 * 將 bunny_settings 從 option 個別欄位轉移到 option 的 powerhouse_settings array 底下
	 *
	 * @return void
	 */
	private static function migration_bunny_settings(): void {
		$bunny_library_id     = \get_option('bunny_library_id');
		$bunny_cdn_hostname   = \get_option('bunny_cdn_hostname');
		$bunny_stream_api_key = \get_option('bunny_stream_api_key');

		if (!$bunny_library_id && !$bunny_cdn_hostname && !$bunny_stream_api_key) {
			return;
		}

		$powerhouse_settings                         = PowerhouseSettings::instance()->to_array();
		$powerhouse_settings['bunny_library_id']     = $bunny_library_id;
		$powerhouse_settings['bunny_cdn_hostname']   = $bunny_cdn_hostname;
		$powerhouse_settings['bunny_stream_api_key'] = $bunny_stream_api_key;

		PowerhouseSettings::instance()->partial_update($powerhouse_settings);

		\delete_option('bunny_library_id');
		\delete_option('bunny_cdn_hostname');
		\delete_option('bunny_stream_api_key');
	}


	/**
	 * Add Post Meta To Course Product
	 * 把每個商品都標示，是否為課程商品
	 *
	 * @return void
	 */
	public static function add_post_meta_to_course_product(): void {
		$args = [
			'post_type'      => 'product',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => [
				[
					'key'     => '_is_course',
					'compare' => 'NOT EXISTS',
				],
			],
		];

		$ids = \get_posts($args);

		foreach ($ids as $id) {
			\add_post_meta($id, '_is_course', 'no');
		}
	}
}
