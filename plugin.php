<?php
/**
 * Plugin Name:       Power Course | WordPress 最好用的課程外掛
 * Plugin URI:        https://github.com/j7-dev/wp-power-course
 * Description:       WordPress 最好用的課程外掛
 * Version:           0.5.0rc2
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

		const COURSE_TABLE_NAME  = 'pc_avl_coursemeta';
		const CHAPTER_TABLE_NAME = 'pc_avl_chaptermeta';
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
					'version'  => '2.0.11',
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
			self::create_course_table();
			self::create_chapter_table();
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


		/**
		 * 創建課程 meta table
		 *
		 * @return void
		 * @throws \Exception Exception.
		 */
		private static function create_course_table(): void {
			try {
				global $wpdb;

				$table_name           = $wpdb->prefix . self::COURSE_TABLE_NAME;
				$wpdb->avl_coursemeta = $table_name;

				$charset_collate = $wpdb->get_charset_collate();

				$sql = "CREATE TABLE $table_name (
										meta_id bigint(20) NOT NULL AUTO_INCREMENT,
										course_id bigint(20) NOT NULL,
										user_id bigint(20) NOT NULL,
										meta_key varchar(255) DEFAULT NULL,
										meta_value longtext,
										PRIMARY KEY  (meta_id),
										KEY course_id (course_id),
										KEY user_id (user_id),
										KEY meta_key (meta_key(191))
								) $charset_collate;";

				require_once ABSPATH . 'wp-admin/includes/upgrade.php';
				$result = \dbDelta($sql);
			} catch (\Throwable $th) {
				throw new \Exception($th->getMessage());
			}
		}

		/**
		 * 創建章節 meta table
		 *
		 * @return void
		 * @throws \Exception Exception.
		 */
		public static function create_chapter_table(): void {
			try {
				global $wpdb;

				$table_name            = $wpdb->prefix . self::CHAPTER_TABLE_NAME;
				$wpdb->avl_chaptermeta = $table_name;

				$charset_collate = $wpdb->get_charset_collate();

				$sql = "CREATE TABLE $table_name (
										meta_id bigint(20) NOT NULL AUTO_INCREMENT,
										post_id bigint(20) NOT NULL,
										user_id bigint(20) NOT NULL,
										meta_key varchar(255) DEFAULT NULL,
										meta_value longtext,
										PRIMARY KEY  (meta_id),
										KEY post_id (post_id),
										KEY user_id (user_id),
										KEY meta_key (meta_key(191))
								) $charset_collate;";

				require_once ABSPATH . 'wp-admin/includes/upgrade.php';
				$result = \dbDelta($sql);
			} catch (\Throwable $th) {
				throw new \Exception($th->getMessage());
			}
		}
	}



	Plugin::instance();
}
