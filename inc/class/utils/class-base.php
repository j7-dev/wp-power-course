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

	public static function get_video_length_by_seconds(int $seconds):string{
		if(!$seconds){
			return '-- : -- : --';
		}
		$video_length_h = sprintf("%02d", floor($seconds / 3600));
		$video_length_m =  sprintf("%02d", floor(($seconds - $video_length_h * 3600) / 60));
		$video_length_s =  sprintf("%02d", $seconds - $video_length_h * 3600 - $video_length_m * 60);
		return "$video_length_h : $video_length_m : $video_length_s";
	}
}
