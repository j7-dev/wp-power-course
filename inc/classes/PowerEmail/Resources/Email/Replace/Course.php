<?php
/**
 * Email Course Replace
 */

declare( strict_types=1 );

namespace J7\PowerCourse\PowerEmail\Resources\Email\Replace;

/**
 * Class Course
 */
abstract class Course {

	/**TODO
	 *
	 * @var array<string, string> 使用者資料取代字串的 Schema
	 */
	public static $schema = [
		'display_name' => '顯示名稱',
		'email'        => '電子郵件',
		'ID'           => 'ID',
		'user_login'   => '帳號',
	];

	/**
	 * 取得取代字串後的 HTML
	 *
	 * @param string      $html HTML
	 * @param \WC_Product $course 課程
	 * @return string 格式化後的 HTML
	 */
	public static function get_formatted_html( string $html, \WC_Product $course ): string {

		return $html;
	}
}
