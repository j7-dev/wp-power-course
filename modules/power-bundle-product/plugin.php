<?php
/**
 * Plugin Name:       Power Bundle Product | 創建方案，綑綁銷售，自訂價格
 * Plugin URI:
 * Description:       創建方案，綑綁銷售，自訂價格
 * Version:           0.0.1
 * Requires at least: 5.7
 * Requires PHP:      8.0
 * Author:            J7
 * Author URI:        https://github.com/j7-dev
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       power-bundle-product
 * Domain Path:       /languages
 * Tags:
 */
declare (strict_types = 1);

namespace J7\PowerBundleProduct;

if ( ! class_exists( 'Plugin' ) ) {

	/**
	 * Custom product type class.
	 */
	final class Plugin {

		use \J7\WpUtils\Traits\PluginTrait;
		use \J7\WpUtils\Traits\SingletonTrait;

		const PRODUCT_TYPE = 'power_bundle_product';

		/**
		 * Constructor.
		 */
		public function __construct() {
			require_once __DIR__ . '/inc/class/class-bootstrap.php';

			$this->init(
				array(
					'app_name'    => 'Power Bundle Product',
					'github_repo' => '',
					'callback'    => array( Bootstrap::class, 'instance' ),
				)
			);
		}
	}
}

Plugin::instance();
