<?php
/**
 * Base
 */

declare ( strict_types=1 );

namespace J7\PowerCourse\Utils;

use J7\WpUtils\Classes\WC;

/**
 * Class Base
 */
abstract class Base {
	public const BASE_URL      = '/';
	public const APP1_SELECTOR = '#power_course';
	public const APP2_SELECTOR = '.pc-vidstack';
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
	 * 取得產品價格 HTML。
	 *
	 * @param \WC_Product $product WooCommerce 產品實例。
	 *
	 * @return string 產品價格的 HTML 字串。
	 */
	public static function get_price_html( \WC_Product $product ): string {
		$product_type = $product->get_type();
		return match ($product_type) {
			'subscription' => self::get_subscription_product_price_html($product),
			default => $product->get_price_html(),
		};
	}

	/**
	 * 取得訂閱商品價格
	 *
	 * @param \WC_Product $product 商品
	 *
	 * @return string
	 */
	private static function get_subscription_product_price_html( \WC_Product $product ): string {
		if (!class_exists('\WC_Product_Subscription')) {
			return $product->get_price_html();
		}

		$fields = [
			'_subscription_price',
			'_subscription_period',
			'_subscription_period_interval',
			'_subscription_length',
		];

		$values = [];
		foreach ($fields as $field) {
			$value            = $product->get_meta($field);
			$values[ $field ] = $value;
		}
		/**
		 * @var array{
		 *  _subscription_price: string,
		 *  _subscription_period: string,
		 *  _subscription_period_interval: int,
		 *  _subscription_length: int,
		 * } $values
		 */

		$_subscription_period_label = match ($values['_subscription_period']) {
			'day' => '天',
			'week' => '週',
			'month' => '月',
			'year' => '年',
			default => '',
		};

		return sprintf(
			/*html*/'
				<span class="woocommerce-Price-amount amount">
					%1$s
				</span>
				/ %2$s%3$s%4$s
			',
			\wc_price( (float) $values['_subscription_price']),
			$values['_subscription_period_interval'] > 1 ? "{$values['_subscription_period_interval']} " : '',
			$_subscription_period_label,
			$values['_subscription_length'] ? "<p class='text-gray-600/50 text-sm mb-0'>持續 {$values['_subscription_length']} {$_subscription_period_label}</p>" : ''
		);
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
}
