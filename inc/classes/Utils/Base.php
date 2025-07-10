<?php

declare ( strict_types=1 );

namespace J7\PowerCourse\Utils;

use J7\WpUtils\Classes\WC;

/** Class Base */
abstract class Base {
	public const BASE_URL      = '/';
	public const APP1_SELECTOR = '#power_course';
	public const APP2_SELECTOR = '.pc-vidstack';
	public const API_TIMEOUT   = '30000';
	public const DEFAULT_IMAGE = 'https://placehold.co/800x600/1677ff/white?text=%3Cimg%20/%3E';
	public const PRIMARY_COLOR = 'var(--fallback-p,oklch(var(--p)/1))';

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
	 * @param int    $seconds 秒數。
	 * @param string $empty_value 預設值。
	 * @return string 格式化的時間字符串。
	 */
	public static function get_video_length_by_seconds( int $seconds, $empty_value = '' ): string {
		if (!$seconds) {
			return $empty_value;
		}
		$video_length_h = sprintf('%02d', floor($seconds / 3600));
		$video_length_m = sprintf('%02d', floor(( $seconds - $video_length_h * 3600 ) / 60));
		$video_length_s = sprintf('%02d', $seconds - $video_length_h * 3600 - $video_length_m * 60);
		return "$video_length_h : $video_length_m : $video_length_s";
	}


	/**
	 * 是否有短碼
	 *
	 * @param string $content 內容
	 *
	 * @return bool
	 */
	public static function has_shortcode( string $content ): bool {

		if ( str_contains( $content, '[' ) && str_contains( $content, ']' ) ) {
			return true;
		}

		return false;
	}


	/**
	 * 通用批次處理高階函數
	 *
	 * @param array<int, mixed> $items 需要處理的項目陣列
	 * @param callable          $callback 處理每個項目的回調函數，接收項目和索引參數，回傳布林值表示成功或失敗
	 * @param array{
	 *  batch_size: int,
	 *  pause_ms: int,
	 *  flush_cache: bool,
	 * }    $options 設定選項
	 * @return array 處理結果統計
	 */
	public static function batch_process( $items, $callback, $options = [] ) {
		// 默认选项
		$default_options = [
			'batch_size'  => 20,  // 每批次處理的項目數量
			'pause_ms'    => 500, // 每批次之間暫停的毫秒數
			'flush_cache' => true, // 每批次後是否清除 WordPress 快取
		];

		// 合併選項
		$options = \wp_parse_args( $options, $default_options );

		// 初始化結果統計
		$result = [];

		// 分批處理
		$batches = array_chunk($items, $options['batch_size']);

		foreach ($batches as $batch_index => $batch) {
			// 處理每一批
			foreach ($batch as $index => $item) {
				$result_index            = $batch_index * $options['batch_size'] + $index;
				$result[ $result_index ] = call_user_func($callback, $item, $index);
			}

			// 如果不是最後一批，執行批次間操作
			if ($batch_index < count($batches) - 1) {
				// 清除快取，釋放記憶體
				if ($options['flush_cache']) {
					wp_cache_flush();
				}

				// 暫停指定時間
				if ($options['pause_ms'] > 0) {
					usleep($options['pause_ms'] * 1000); // 轉換為微秒
				}
			}
		}

		return $result;
	}
}
