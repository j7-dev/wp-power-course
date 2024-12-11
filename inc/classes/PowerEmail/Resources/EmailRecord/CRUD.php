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
	 * @param int  $post_id 課程/章節 ID
	 * @param ?int $user_id 使用者 ID
	 * @param ?int $email_id 信件 ID
	 * @param ?int $mark_as_sent 是否已寄送
	 * @return array<int, object{id: int, post_id: int, user_id: int, email_id: int, email_subject: string, trigger_at: string, mark_as_sent: int, email_date: string}>
	 */
	public static function get( int $post_id, ?int $user_id = 0, ?int $email_id = 0, ?int $mark_as_sent = null ) {
		global $wpdb;
		$table_name = $wpdb->prefix . static::$table_name;

		$where = "post_id = $post_id";
		if ( $user_id ) {
			$where .= " AND user_id = $user_id";
		}
		if ( $email_id ) {
			$where .= " AND email_id = $email_id";
		}
		if ( $mark_as_sent !== null ) {
			$where .= " AND mark_as_sent = $mark_as_sent";
		}
		return $wpdb->get_results("SELECT * FROM $table_name WHERE $where"); // phpcs:ignore
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
			'mark_as_sent'  => 1,
		];
		return $wpdb->insert(
				$table_name,
				$data,
				[ '%d', '%d', '%d', '%s', '%s', '%s' ]
			);
	}

	/**
	 * 更新 record
	 *
	 * @param array<string, mixed> $where 要更新的資料
	 * @param array<string, mixed> $data 要更新的資料
	 *
	 * @return int|false The number of rows affected on success, or false on failure.
	 */
	public static function update( array $where, array $data ): int|false {

		global $wpdb;

		$table_name = $wpdb->prefix . static::$table_name;

		return $wpdb->update(
				$table_name,
				$data,
				$where,
				null,
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
