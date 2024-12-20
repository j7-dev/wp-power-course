<?php
/**
 * Api Optimize
 * 將 power-course-api-booster.php 檔案移動到 mu-plugins 目錄下
 * 加快 API 回應速度
 */

declare(strict_types=1);

namespace J7\PowerCourse\Compatibility;

/**
 * ApiOptimize Api
 */
final class ApiOptimize {
	use \J7\WpUtils\Traits\SingletonTrait;

	/**
	 * Constructor
	 */
	public function __construct() {
		\add_action( Compatibility::AS_COMPATIBILITY_ACTION, [ __CLASS__, 'move_file' ]);
	}

	/**
	 * Move File
	 * 負責將 power-course-api-booster.php 移動到 mu-plugins 目錄
	 *
	 * @return void
	 */
	public static function move_file(): void {
		// 取得 mu-plugins 目錄路徑
		$mu_plugins_dir = WPMU_PLUGIN_DIR;

		// 檢查 mu-plugins 目錄是否存在
		if (!is_dir($mu_plugins_dir)) {
			\J7\WpUtils\Classes\ErrorLog::info('mu-plugins 目錄不存在', $mu_plugins_dir);
			return;
		}

		// 源文件路徑
		$source_file = __DIR__ . '/power-course-api-booster.php';
		// 目標文件路徑
		$target_file = $mu_plugins_dir . '/power-course-api-booster.php';

		try {
			// 檢查源文件是否存在
			if (!file_exists($source_file)) {
				\J7\WpUtils\Classes\ErrorLog::info('源文件不存在', $source_file);
				return;
			}

			// 複製文件（如果目標文件已存在會覆蓋）
			if (!copy($source_file, $target_file)) {
				\J7\WpUtils\Classes\ErrorLog::info(
					[
						'source' => $source_file,
						'target' => $target_file,
					],
					'文件複製失敗'
					);
				return;
			}
		} catch (\Exception $e) {
			\J7\WpUtils\Classes\ErrorLog::info(
				[
					'message' => $e->getMessage(),
					'source'  => $source_file,
					'target'  => $target_file,
				],
				'複製文件時發生錯誤',
				);
			return;
		}
	}
}
