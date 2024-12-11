<?php
/**
 * 對 pc_email_records table 的 CRUD 抽象
 */

declare ( strict_types=1 );

namespace J7\PowerCourse\PowerEmail\Resources\EmailRecord;

use J7\PowerCourse\Plugin;

/**
 * Class CRUD
 */
abstract class CRUD {

	/**
	 * 對應的 table name
	 *
	 * @var string
	 */
	public static string $table_name = Plugin::EMAIL_RECORDS_TABLE_NAME;

	/**
	 * 取得紀錄
	 *
	 * @param int $post_id 課程/章節 ID
	 * @param int $user_id 使用者 ID
	 * @param int $email_id 信件 ID
	 * @return array|null
	 */
	public static function get( int $post_id, int $user_id, int $email_id ): array|null {
		global $wpdb;
		$table_name = $wpdb->prefix . static::$table_name;
		return $wpdb->get_row("SELECT * FROM $table_name WHERE post_id = $post_id AND user_id = $user_id AND email_id = $email_id", ARRAY_A); // phpcs:ignore
	}


	/**
	 * Adds a meta value for a specific course and user in the AVL Course Meta class.
	 *
	 * @param int    $post_id 課程/章節 ID
	 * @param int    $user_id 使用者 ID
	 * @param int    $email_id 信件 ID
	 * @param string $email_subject 信件主題
	 * @param string $trigger_at 觸發時間
	 * @return int|false The ID of the newly added meta data, or false on failure.
	 */
	public static function add( int $post_id, int $user_id, int $email_id, ?string $email_subject = '', ?string $trigger_at = '' ): int|false {
		global $wpdb;

		$table_name = $wpdb->prefix . static::$table_name;

		$data = [
			'post_id'       => $post_id,
			'user_id'       => $user_id,
			'email_id'      => $email_id,
			'email_subject' => $email_subject,
			'trigger_at'    => $trigger_at,
			'email_date'    => \wp_date('Y-m-d H:i:s'),
		];
		return $wpdb->insert(
				$table_name,
				$data,
				[ '%d', '%d', '%d', '%s', '%s', '%s' ]
			);
	}

	/**
	 * 更新 record
	 * TODO 尚未測試過
	 *
	 * @param int    $id 紀錄 ID
	 * @param string $key 欄位名稱
	 * @param mixed  $value 欄位值
	 *
	 * @return int|false The number of rows affected on success, or false on failure.
	 */
	public static function update( int $id, string $key, $value ): int|false {

		global $wpdb;

		$table_name = $wpdb->prefix . static::$table_name;

		return $wpdb->update(
				$table_name,
				[ // data
					$key => \maybe_serialize( $value ),
				],
				[ // where
					'id' => $id,
				],
				[ // format
					'%s',
				],
				[ // where format
					'%d',
				]
			);
	}


	/**
	 * 刪除紀錄
	 *
	 * @param int $id 紀錄 ID
	 * @return int|false 移除的數量, or false on error.
	 */
	public static function delete( int $id ): int|false {
		global $wpdb;
		$table_name = $wpdb->prefix . static::$table_name;
		return $wpdb->delete(
		$table_name,
		[
			'id' => $id,
		],
		[ '%d' ]
		);
	}
}
