<?php
/**
 * Email User Replace
 */

declare( strict_types=1 );

namespace J7\PowerCourse\PowerEmail\Resources\Email\Replace;

/**
 * Class User
 */
abstract class User {

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
	 * @param string   $html HTML
	 * @param \WP_User $user 使用者
	 * @return string 格式化後的 HTML
	 */
	public static function get_formatted_html( string $html, \WP_User $user ): string {
		$schema_keys   = array_map( fn( $key ) => '{' . $key . '}', array_keys( self::$schema ) );
		$schema_values = [];
		foreach ( self::$schema as $key => $value ) {
			$schema_values[] = $user->get( $key );
		}

		$formatted_html = str_replace( $schema_keys, $schema_values, $html );

		return $formatted_html;
	}
}
