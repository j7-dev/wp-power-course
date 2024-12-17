<?php
/**
 * Plugin Name:       Power Course | WordPress 最好用的課程外掛
 * Plugin URI:        https://github.com/j7-dev/wp-power-course
 * Description:       WordPress 最好用的課程外掛
 * Version:           0.5.4
 * Requires at least: 5.7
 * Requires PHP:      8.0
 * Author:            J7
 * Author URI:        https://github.com/j7-dev
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       power-course
 * Domain Path:       /languages
 * Tags: LMS, online course, vite, react, tailwind, typescript, react-query, scss, WordPress, WordPress plugin, refine
 */

declare(strict_types=1);

namespace J7\PowerCourse;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

if (!\class_exists('J7\PowerCourse\Plugin')) {
	require_once __DIR__ . '/vendor/autoload.php';

	/**
	 * Class Plugin
	 */
	final class Plugin {
		use \J7\WpUtils\Traits\PluginTrait;
		use \J7\WpUtils\Traits\SingletonTrait;

		const COURSE_TABLE_NAME        = 'pc_avl_coursemeta';
		const CHAPTER_TABLE_NAME       = 'pc_avl_chaptermeta';
		const EMAIL_RECORDS_TABLE_NAME = 'pc_email_records';


		/**
		 * Constructor
		 */
		public function __construct() {
			self::$template_page_names = [ 'course-product', 'classroom', 'my-account', '404' ];

			$this->required_plugins = [
				[
					'name'     => 'WooCommerce',
					'slug'     => 'woocommerce',
					'required' => true,
					'version'  => '7.6.0',
				],
				[
					'name'     => 'Powerhouse',
					'slug'     => 'powerhouse',
					'source'   => 'https://github.com/j7-dev/wp-powerhouse/releases/latest/download/powerhouse.zip',
					'version'  => '2.0.13',
					'required' => true,
				],
			];

			$this->init(
				[
					'app_name'    => 'Power Course',
					'github_repo' => 'https://github.com/j7-dev/wp-power-course',
					'callback'    => [ Bootstrap::class, 'instance' ],
				]
			);
		}

		/**
		 * Activate
		 * 啟用時創建 avl_coursemeta table
		 *
		 * @return void
		 * @throws \Exception Exception.
		 */
		public function activate(): void {
			require_once __DIR__ . '/inc/classes/AbstractTable.php';
			AbstractTable::create_course_table();
			AbstractTable::create_chapter_table();
			AbstractTable::create_email_records_table();
			self::set_default_product_meta();
		}

		/**
		 * 設定預設的產品 meta
		 * 將所有產品 _is_course 初始值設為 no
		 *
		 * @return void
		 */
		private static function set_default_product_meta(): void {
			$post_ids = \get_posts(
			[
				'post_type'   => 'product',
				'numberposts' => -1,
				'fields'      => 'ids',
			]
			);
			foreach ($post_ids as $post_id) {
				$is_course = \get_post_meta($post_id, '_is_course', true);
				if (!$is_course) {
					\update_post_meta($post_id, '_is_course', 'no');
				}
			}
		}
	}



	Plugin::instance();
}
