<?php
/**
 * Table trait
 */

namespace J7\PowerCourse;

use J7\WpUtils\Classes\WP;

if ( trait_exists( 'TableTrait' ) ) {
	return;
}

trait TableTrait {
	/**
	 * 創建課程 meta table
	 *
	 * @return void
	 * @throws \Exception Exception.
	 */
	private static function create_course_table(): void {
		try {
			global $wpdb;
			$table_name      = $wpdb->prefix . Plugin::COURSE_TABLE_NAME;
			$is_table_exists = WP::is_table_exists( $table_name );
			if ( $is_table_exists ) {
				return;
			}

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
	private static function create_chapter_table(): void {
		try {
			global $wpdb;
			$table_name      = $wpdb->prefix . Plugin::CHAPTER_TABLE_NAME;
			$is_table_exists = WP::is_table_exists( $table_name );
			if ( $is_table_exists ) {
				return;
			}

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


	/**
	 * 創建 email records table
	 *
	 * @return void
	 * @throws \Exception Exception.
	 */
	private static function create_email_records_table(): void {
		try {
			global $wpdb;
			$table_name      = $wpdb->prefix . Plugin::EMAIL_RECORDS_TABLE_NAME;
			$is_table_exists = WP::is_table_exists( $table_name );
			if ( $is_table_exists ) {
				return;
			}

			$wpdb->email_records = $table_name;

			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE $table_name (
										id bigint(20) NOT NULL AUTO_INCREMENT,
										post_id bigint(20) NOT NULL,
										user_id bigint(20) NOT NULL,
										email_id bigint(20) NOT NULL,
										action_id bigint(20) NOT NULL,
										email_subject varchar(255) DEFAULT NULL,
										trigger_at varchar(30) DEFAULT NULL,
										email_date datetime DEFAULT NULL,
										PRIMARY KEY  (id),
										KEY post_id (post_id),
										KEY user_id (user_id),
										KEY email_id (email_id)
								) $charset_collate;";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			$result = \dbDelta($sql);
		} catch (\Throwable $th) {
			throw new \Exception($th->getMessage());
		}
	}
}
