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
			'variable-subscription' => '',
			default => $product->get_price_html(),
		};
	}

	/**
	 * 取得訂閱商品的 meta data
	 *
	 * @param \WC_Product $product 商品
	 *
	 * @return array<string>
	 */
	public static function get_subscription_product_meta_data_label( \WC_Product $product ): array {
		if (!class_exists('\WC_Subscription')) {
			return [];
		}

		$product_meta_data = [];

		[
			'_subscription_period' => $subscription_period,
			'_subscription_length' => $subscription_length,
			'_subscription_sign_up_fee' => $subscription_sign_up_fee,
			'_subscription_trial_length' => $subscription_trial_length,
			'_subscription_trial_period' => $subscription_trial_period,
		] = self::get_subscription_product_meta_data( $product );

		// 持續 4 個月文字
		if ($subscription_length) {
			$subscription_period_label = self::get_subscription_period_label( $subscription_period );
			$product_meta_data[]       = "扣款持續 {$subscription_length} {$subscription_period_label}";
		}

		if ($subscription_sign_up_fee) {
			$price               = \wc_price( (float) $subscription_sign_up_fee );
			$product_meta_data[] = "首次開通 {$price}";
		}

		if ($subscription_trial_length) {
			$subscription_trial_period_label = self::get_subscription_period_label( $subscription_trial_period );
			$product_meta_data[]             = "包含 {$subscription_trial_length} {$subscription_trial_period_label}免費試用";
		}

		return $product_meta_data;
	}

	/**
	 * 取得訂閱商品價格
	 *
	 * @param \WC_Product $product 商品
	 *
	 * @return string
	 */
	private static function get_subscription_product_price_html( \WC_Product $product ): string {
		$price = $product->get_price_html();
		if (!class_exists('\WC_Subscription')) {
			return $price;
		}

		[
			'_subscription_period' => $subscription_period,
			'_subscription_period_interval' => $subscription_period_interval,
		] = self::get_subscription_product_meta_data( $product );

		$subscription_period_label = self::get_subscription_period_label( $subscription_period );

		// 組合成  /月 /2月 的文字
		$period_label = '/' . ( $subscription_period_interval > 1 ? "{$subscription_period_interval} {$subscription_period_label}" : "{$subscription_period_label}" );
		$period_label = sprintf( /*html*/'<span class="text-sm">%1$s</span>', $period_label);

		// 同 WC_Product_Simple::get_price_html()
		if ( '' === $product->get_price() ) {
			$price = (string) \apply_filters( 'woocommerce_empty_price_html', '', $product );
		} elseif ( $product->is_on_sale() ) {
			$price = self::wc_format_subscription_sale_price( (string) \wc_get_price_to_display( $product, [ 'price' => $product->get_regular_price() ] ), (string) \wc_get_price_to_display( $product ), $period_label ) . $product->get_price_suffix();
		} else {
			$price = \wc_price( \wc_get_price_to_display( $product ) ) . $product->get_price_suffix() . $period_label;
		}

		return $price;
	}

	/**
	 * 取得訂閱商品的 meta data
	 *
	 * @param \WC_Product $product 商品
	 *
	 * @return array{
	 *  _subscription_price: string,
	 *  _subscription_period: string,
	 *  _subscription_period_interval: string,
	 *  _subscription_length: string,
	 *  _subscription_sign_up_fee: string,
	 *  _subscription_trial_length: string,
	 *  _subscription_trial_period: string,
	 * }
	 */
	public static function get_subscription_product_meta_data( \WC_Product $product ): array {

		$fields = [
			'_subscription_price',
			'_subscription_period',
			'_subscription_period_interval',
			'_subscription_length',
			'_subscription_sign_up_fee',
			'_subscription_trial_length',
			'_subscription_trial_period',
		];

		if (!class_exists('\WC_Subscription')) {
			return array_fill_keys($fields, '');
		}

		$values = [];
		foreach ($fields as $field) {
			$value            = $product->get_meta($field);
			$values[ $field ] = $value;
		}

		/**
		 * @var array{
		 *  _subscription_price: string,
		 *  _subscription_period: string,
		 *  _subscription_period_interval: string,
		 *  _subscription_length: string,
		 *  _subscription_sign_up_fee: string,
			*  _subscription_trial_length: string,
			*  _subscription_trial_period: string,
			* } $values
		 */
		return $values;
	}

	/**
	 * 取得訂閱商品的 period label
	 *
	 * @param string $subscription_period 訂閱商品的 period
	 *
	 * @return string
	 */
	public static function get_subscription_period_label( string $subscription_period ): string {
		return match ($subscription_period) {
			'day' => '天',
			'week' => '週',
			'month' => '月',
			'year' => '年',
			default => '',
		};
	}


	/**
	 * 覆寫 wc_format_sale_price
	 * Format a sale price for display.
	 *
	 * @since  3.0.0
	 * @param  string $regular_price Regular price.
	 * @param  string $sale_price    Sale price.
	 * @param  string $period_label  Period label.
	 * @return string
	 */
	public static function wc_format_subscription_sale_price( $regular_price, $sale_price, $period_label ) {
		// Format the prices.
		$formatted_regular_price = is_numeric( $regular_price ) ? \wc_price( (float) $regular_price ) : $regular_price;
		$formatted_sale_price    = is_numeric( $sale_price ) ? \wc_price( (float) $sale_price ) : $sale_price;
		$formatted_sale_price    = $formatted_sale_price . $period_label;

		// Strikethrough pricing.
		$price = '<del aria-hidden="true">' . $formatted_regular_price . '</del> ';

		// For accessibility (a11y) we'll also display that information to screen readers.
		$price .= '<span class="screen-reader-text">';
		// translators: %s is a product's regular price.
		$price .= esc_html( sprintf( __( 'Original price was: %s.', 'woocommerce' ), wp_strip_all_tags( $formatted_regular_price ) ) );
		$price .= '</span>';

		// Add the sale price.
		$price .= '<ins aria-hidden="true">' . $formatted_sale_price . '</ins>';

		// For accessibility (a11y) we'll also display that information to screen readers.
		$price .= '<span class="screen-reader-text">';
		// translators: %s is a product's current (sale) price.
		$price .= esc_html( sprintf( __( 'Current price is: %s.', 'woocommerce' ), wp_strip_all_tags( $formatted_sale_price ) ) );
		$price .= '</span>';

		return (string) apply_filters( 'woocommerce_format_sale_price', $price, $regular_price, $sale_price );
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
