<?php
/**
 * General Shortcodes
 */

declare(strict_types=1);

namespace J7\PowerCourse\Shortcodes;

use J7\PowerCourse\Plugin;
use J7\PowerCourse\Utils\Course as CourseUtils;

/**
 * Class General
 */
final class General {
	use \J7\WpUtils\Traits\SingletonTrait;

	/**
	 * 所有短碼
	 *
	 * @var array<string>
	 */
	public static array $shortcodes = [
		'pc_courses',
		'pc_my_courses',
	];

	/**
	 * Constructor
	 */
	public function __construct() {
		foreach (self::$shortcodes as $shortcode) {
			\add_shortcode($shortcode, [ __CLASS__, "{$shortcode}_callback" ]);
		}
	}

	/**
	 * 課程列表短碼 pc_courses callback
	 *
	 * @param array $params 短碼參數
	 * @return string
	 */
	public static function pc_courses_callback( array $params ): string {

		$default_args = [
			'status'              => [ 'publish' ],
			'paginate'            => true,
			'limit'               => 12,
			'page'                => 1,
			'orderby'             => 'date',
			'order'               => 'DESC',
			'meta_key'            => '_is_course',
			'meta_value'          => 'yes',
			'exclude_avl_courses' => false, // 是否排除當前用戶已授權的課程
		];

		$args = \wp_parse_args(
		$params,
		$default_args,
		);

		$exclude_avl_courses = $args['exclude_avl_courses'] ?? false;
		unset($args['exclude_avl_courses']);

		$columns = $args['columns'] ?? 3;
		unset($args['columns']);

		$array_keys = [ 'include', 'exclude', 'tag', 'category' ];

		foreach ($array_keys as $key) {
			// 因為 wc_get_products 是接受 array 所以在這裡要做轉換
			if (isset($args[ $key ])) {
				$args[ $key ] = explode(',', str_replace(' ', '', $args[ $key ]));
			}
		}

		if ( $exclude_avl_courses ) {
			$current_user_id     = \get_current_user_id();
			$user_avl_course_ids = CourseUtils::get_avl_courses_by_user( $current_user_id, true );
			$args['exclude']     = $user_avl_course_ids;
		}

		$results     = \wc_get_products( $args );
		$total       = $results->total;
		$total_pages = $results->max_num_pages;

		$products = $results->products;

		$html = Plugin::get(
			'list/pricing',
			[
				'products' => $products,
				'columns'  => $columns,
			],
			false
			);

		return $html;
	}

	/**
	 * 我的課程短碼 pc_my_courses callback
	 *
	 * @param array $params 短碼參數
	 * @return string
	 */
	public static function pc_my_courses_callback( array $params ): string {
		$html = Plugin::get(
			'my-account',
			null,
			false
		);

		return $html;
	}
}
