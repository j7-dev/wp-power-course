<?php
/**
 * Api Optimize
 * 將 self::FILE_NAME 檔案移動到 mu-plugins 目錄下
 * 加快 API 回應速度
 *
 * @deprecated v0.7
 */

declare(strict_types=1);

namespace J7\PowerCourse\Compatibility;

/**
 * ApiOptimize Api
 */
final class ApiOptimize {
	use \J7\WpUtils\Traits\SingletonTrait;

	const FILE_NAME = 'power-course-api-booster.php';

	/** Constructor */
	public function __construct() {
		\add_action( Compatibility::AS_COMPATIBILITY_ACTION, [ __CLASS__, 'delete_file' ]);
		\delete_option('pc_enable_api_booster');
	}


	/**
	 * Delete File
	 * 負責刪除 self::FILE_NAME 檔案
	 *
	 * @return void
	 */
	public static function delete_file(): void {
		$file = WPMU_PLUGIN_DIR . '/' . self::FILE_NAME;
		if (file_exists($file)) {
			$success = \wp_delete_file($file);
			\J7\WpUtils\Classes\WC::log($file, $success ? '成功刪除檔案' : '刪除檔案失敗');
			return;
		}

		\J7\WpUtils\Classes\WC::log($file, '檔案不存在');
	}
}
