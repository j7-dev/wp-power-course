<?php
/**
 * Base
 */

declare ( strict_types=1 );

namespace J7\PowerCourse\Utils;

use J7\WpUtils\Classes\WC;

/**
 * Class Utils
 */
abstract class Base {
	public const BASE_URL      = '/';
	public const APP1_SELECTOR = '#power_course';
	public const APP2_SELECTOR = '#power_course_metabox';
	public const API_TIMEOUT   = '30000';
	public const DEFAULT_IMAGE = 'https://placehold.co/800x600/1677ff/white?text=%3Cimg%20/%3E';
	public const PRIMARY_COLOR = '#1677ff';


	/**
	 * 取得商品圖片
	 *
	 * @param \WC_Product $product 商品
	 * @param string|null $size 尺寸
	 *
	 * @return string
	 */
	public static function get_image_url_by_product(
		\WC_Product $product,
		?string $size = 'single-post-thumbnail'
	): string {
		return WC::get_image_url_by_product( $product, $size, self::DEFAULT_IMAGE );
	}

	/**
	 * 將秒數轉換為時分秒格式。
	 *
	 * 接收一個整數秒數，轉換為 "時:分:秒" 的格式字符串。
	 * 如果輸入為0，則返回空字符串。
	 *
	 * @param int $seconds 秒數。
	 * @return string 格式化的時間字符串。
	 */
	public static function get_video_length_by_seconds( int $seconds ): string {
		if (!$seconds) {
			return '';
		}
		$video_length_h = sprintf('%02d', floor($seconds / 3600));
		$video_length_m = sprintf('%02d', floor(( $seconds - $video_length_h * 3600 ) / 60));
		$video_length_s = sprintf('%02d', $seconds - $video_length_h * 3600 - $video_length_m * 60);
		return "$video_length_h : $video_length_m : $video_length_s";
	}

	/**
	 * 格式化數組，將 '[]' 轉為空數組。
	 *
	 * 遍歷數組，將值為 '[]' 的項目轉換為空數組。
	 *
	 * @param array $arr 原始數組。
	 * @return array 轉換後的數組。
	 */
	public static function format_empty_array( array $arr ): array {
		$formatted_array = [];
		foreach ($arr as $key => $value) {
			if ( '[]' === $value) {
				$formatted_array[ $key ] = [];
			} else {
				$formatted_array[ $key ] = $value;
			}
		}

		return $formatted_array;
	}
}
