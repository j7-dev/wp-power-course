<?php
/**
 * Email User Replace
 */

declare( strict_types=1 );

namespace J7\PowerCourse\PowerEmail\Resources\Email\Replace;

/**
 * Class User
 */
abstract class User extends ReplaceBase {

	/**
	 * @var array<string, string> 使用者資料取代字串的 Schema
	 */
	public static $schema = [
		'display_name' => '用戶的顯示名稱',
		'user_email'   => '用戶的電子郵件',
		'ID'           => '用戶的ID',
		'user_login'   => '用戶的帳號',
	];

	/**
	 * 取得取代字串後的 HTML
	 *
	 * @param string $html HTML
	 * @param int    $user_id 用戶 ID
	 * @param int    $course_id 課程 ID
	 * @param int    $chapter_id 章節 ID
	 * @return string 格式化後的 HTML
	 */
	public static function replace_string( $html, $user_id, $course_id, $chapter_id ): string {
		$user = \get_user_by( 'ID', $user_id );
		if (!$user) {
			return $html;
		}

		$schema_keys   = array_map( fn( $key ) => '{' . $key . '}', array_keys( self::$schema ) );
		$schema_values = [];
		foreach ( self::$schema as $key => $value ) {
			$schema_values[] = $user->get( $key );
		}

		$formatted_html = str_replace( $schema_keys, $schema_values, $html );

		return $formatted_html;
	}
}
