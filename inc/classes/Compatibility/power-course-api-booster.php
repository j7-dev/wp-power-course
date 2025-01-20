<?php
/**
 * ApiBooster
 * 在特定的 API 路徑下，只載入必要的插件
 */

namespace J7\PowerCourse\MU;

/**
 * ApiBooster
 */
final class ApiBooster {

	/**
	 * Namespaces 只有這幾個 namespace 的 API 請求，才會載入必要的插件
	 *
	 * @var array<string>
	 */
	protected static $namespaces = [
		'/wp-json/power-course',
		'/wp-json/power-email',
	];

	/**
	 * Constructor.
	 */
	public function __construct() {
		$enable = \get_option('pc_enable_api_booster', 'no') === 'yes';
		if (!$enable) {
			return;
		}

		\add_action('muplugins_loaded', [ __CLASS__, 'only_load_required_plugins' ], 100);
	}

	/**
	 * Only Load Required Plugins
	 * 只載入必要的插件
	 *
	 * @return void
	 */
	public static function only_load_required_plugins(): void {
		// 檢查是否為 "/wp-json/{$namespace}" API 請求
		$some_strpos = false;
		foreach (self::$namespaces as $namespace) {
			if (strpos((string) $_SERVER['REQUEST_URI'],  $namespace) !== false) { // phpcs:ignore
				$some_strpos = true;
				break;
			}
		}
		if (!$some_strpos) {
			return;
		}

		// 只保留需要的插件
		$required_plugins = [
			'powerhouse/plugin.php',
			'woocommerce/woocommerce.php',
			'power-course/plugin.php',
		];

		// 檢查是否所有必要的插件都已經載入
		// 取得所有已啟用的插件
		$active_plugins                = (array) \get_option('active_plugins');
		$all_required_plugins_included = array_intersect($required_plugins, $active_plugins);
		if (count($all_required_plugins_included) !== count($required_plugins)) {
			return;
		}

		// 如果 WooCommerce Subscriptions 已經啟用，則需要載入 WooCommerce Subscriptions
		if (in_array('woocommerce-subscriptions/woocommerce-subscriptions.php', $active_plugins, true)) {
			$required_plugins[] = 'woocommerce-subscriptions/woocommerce-subscriptions.php';
		}

		// 移除不必要的 WordPress 功能
		$hooks_to_remove = [
			'setup_theme',
			'after_setup_theme',
			'widgets_init',
			'register_sidebar',
			'wp_register_sidebar_widget',
			'wp_default_scripts',
			'wp_default_styles',
			'admin_bar_init',
			'add_admin_bar_menus',
			'wp_loaded',
		];

		foreach ( $hooks_to_remove as $hook ) {
			\add_action(
				$hook,
				function () use ( $hook ) {
					\remove_all_actions($hook);
				},
				-999999
				);
		}

		\add_filter('option_active_plugins', fn () => $required_plugins, 100 );
	}
}

new ApiBooster();
