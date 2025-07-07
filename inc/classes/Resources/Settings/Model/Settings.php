<?php

declare( strict_types=1 );

namespace J7\PowerCourse\Resources\Settings\Model;

use J7\WpUtils\Classes\DTO;
use J7\WpUtils\Classes\General;

/** Class Settings */
final class Settings extends DTO {

	const SETTINGS_OPTION_NAME = 'power_course_settings';

	/** @var string $course_access_trigger 當訂單處於什麼狀態時，會觸發課程開通 */
	public string $course_access_trigger = 'completed';

	/** @var string $course_permalink_structure 課程永久連結結構 */
	public string $course_permalink_structure = '';

	/** @var string $hide_myaccount_courses 是否隱藏我的帳戶中的課程 */
	public string $hide_myaccount_courses = 'no';

	/** @var string $fix_video_and_tabs_mobile 手機板時，影片以及 tabs 黏性(sticky)置頂 */
	public string $fix_video_and_tabs_mobile = 'no';

	/** @var string $pc_header_offset 黏性的偏移距離 */
	public string $pc_header_offset = '0';

	/** @var string $hide_courses_in_main_query 是否在主查詢中隱藏課程 */
	public string $hide_courses_in_main_query = 'no';

	/** @var string $hide_courses_in_search_result 是否在搜尋結果中隱藏課程 */
	public string $hide_courses_in_search_result = 'no';

	/** @var int $pc_watermark_qty 浮水印數量 */
	public int $pc_watermark_qty = 0;

	/** @var string $pc_watermark_color 浮水印顏色 */
	public string $pc_watermark_color = 'rgba(255, 255, 255, 0.5)';

	/** @var int $pc_watermark_interval 浮水印間隔 */
	public int $pc_watermark_interval = 10;

	/** @var string $pc_watermark_text 浮水印文字 */
	public string $pc_watermark_text = '用戶 {display_name} 正在觀看 {post_title} IP:{ip} <br /> Email:{email}';

	/** @var int $pc_pdf_watermark_qty PDF 浮水印數量 */
	public int $pc_pdf_watermark_qty = 0;

	/** @var string $pc_pdf_watermark_color PDF 浮水印顏色 */
	public string $pc_pdf_watermark_color = 'rgba(255, 255, 255, 0.5)';

	/** @var string $pc_pdf_watermark_text PDF 浮水印文字 */
	public string $pc_pdf_watermark_text = '用戶 {display_name} 正在觀看 {post_title} IP:{ip} \n Email:{email}';

	/** @return self 獲取實例*/
	public static function instance(): self {
		if ( self::$dto_instance ) {
			return self::$dto_instance;
		}

		$settings = \get_option( self::SETTINGS_OPTION_NAME );
		$settings = is_array( $settings ) ? $settings : [];

		$instance           = new self( $settings );
		self::$dto_instance = $instance;
		return $instance;
	}

	/**
	 * 設定屬性
	 *
	 * @param array<string, mixed> $properties 屬性名稱與值
	 * @return self
	 */
	public function set_properties( array $properties ) {
		foreach ( $properties as $property => $value ) {
			if ( !property_exists( $this, $property ) ) {
				$this->dto_error->add( 'invalid_property', "Invalid property: {$property}" );
			}
			$this->$property = General::to_same_type( $this->$property, $value );
		}
		return $this;
	}


	/** 儲存設定到資料庫 */
	public function save(): void {
		\update_option( self::SETTINGS_OPTION_NAME, $this->to_array() );
	}


	/**
	 * 設定屬性
	 *
	 * @param string $property 屬性名稱
	 * @param mixed  $value 屬性值
	 * @return void
	 */
	public function __set( string $property, $value ): void {
		if ( property_exists( $this, $property ) ) {
			$this->$property = $value;
		}
	}
}
