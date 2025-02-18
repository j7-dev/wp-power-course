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

	/**
	 * Constructor
	 */
	public function __construct() {
		\add_action( Compatibility::AS_COMPATIBILITY_ACTION, [ __CLASS__, 'move_file' ]);
	}

	/**
	 * Move File
	 * 負責將 self::FILE_NAME 移動到 mu-plugins 目錄
	 *
	 * @return void
	 * @throws \Exception 如果檔案操作失敗
	 */
	public static function move_file(): void {
		// 取得 mu-plugins 目錄路徑
		$mu_plugins_dir = WPMU_PLUGIN_DIR;

		// 檢查 mu-plugins 目錄是否存在
		if (!is_dir($mu_plugins_dir)) {
			\J7\WpUtils\Classes\WC::log($mu_plugins_dir, 'mu-plugins 目錄不存在，嘗試創建 mu-plugins');
			require_once ABSPATH . 'wp-admin/includes/file.php';
			// 創建 mu-plugins 目錄
			global $wp_filesystem;
			if (!WP_Filesystem()) {
				\J7\WpUtils\Classes\WC::log($mu_plugins_dir, '無法初始化 WP_Filesystem');
				return;
			}
			if (!$wp_filesystem->mkdir($mu_plugins_dir, 0755)) {
				\J7\WpUtils\Classes\WC::log($mu_plugins_dir, '無法創建 mu-plugins 目錄');
				return;
			} else {
				\J7\WpUtils\Classes\WC::log($mu_plugins_dir, '成功創建 mu-plugins 目錄');
			}
		}

		// 源文件路徑
		$source_file = __DIR__ . '/' . self::FILE_NAME;
		// 目標文件路徑
		$target_file = $mu_plugins_dir . '/' . self::FILE_NAME;

		try {
			// 檢查源文件是否存在
			if (!file_exists($source_file)) {
				\J7\WpUtils\Classes\WC::log($source_file, '源文件不存在');
				return;
			}

			// 如果目標檔案存在，先嘗試刪除
			if (file_exists($target_file)) {
				if (!unlink($target_file)) {
					throw new \Exception('無法刪除現有檔案');
				}
			}

			// 複製新檔案
			if (!copy($source_file, $target_file)) {
				throw new \Exception('檔案複製失敗');
			}
		} catch (\Exception $e) {
			\J7\WpUtils\Classes\WC::log(
				[
					'message' => $e->getMessage(),
					'source'  => $source_file,
					'target'  => $target_file,
				],
				'檔案操作失敗'
				);
		}
	}
}
