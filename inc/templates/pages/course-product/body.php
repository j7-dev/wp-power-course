<?php
/**
 * Course Product > body
 */

use J7\PowerCourse\Plugin;
use J7\PowerCourse\Utils\Course as CourseUtils;
use J7\PowerCourse\Utils\User as UserUtils;
use J7\PowerCourse\Resources\Course\Limit;

$default_args = [
	'product' => $GLOBALS['course'] ?? null,
];

/**
 * @var array $args
 * @phpstan-ignore-next-line
 */
$args = wp_parse_args( $args, $default_args );

[
	'product' => $product,
] = $args;

if ( ! ( $product instanceof \WC_Product ) ) {
	throw new \Exception( 'product 不是 WC_Product' );
}



echo '<div class="flex-1 px-4 md:px-0">';
echo '<div class="mb-12">';

$is_avl = CourseUtils::is_avl( $product->get_id() );
if ( $is_avl ) {
	Plugin::load_template(
		'alert',
	[
		'type'    => 'info',
		'message' => '您已經購買課程',
		'buttons' => sprintf(
			/*html*/'<a  href="%1$s" target="_blank" class="pc-btn pc-btn-sm pc-btn-primary text-white">%2$s</a>',
				site_url( "classroom/{$product->get_slug()}" ),
				'前往教室',
				),
		] // phpcs:ignore
	);
}



$course_schedule_in_timestamp = $product->get_meta( 'course_schedule' );
$course_schedule              = $course_schedule_in_timestamp ? \wp_date(
			'Y/m/d H:i',
			(int) $course_schedule_in_timestamp
		) : '未設定';
$course_hour                  = (int) $product->get_meta( 'course_hour' );
$course_minute                = (int) $product->get_meta( 'course_minute' );

$count_all_chapters = count( CourseUtils::get_sub_chapter_ids( $product ) );


$total_student = ( UserUtils::count_student( $product->get_id() ) ) + ( (int) $product->get_meta( 'extra_student_count' ) );
$limit_labels  = Limit::instance($product)->get_limit_label();

$items = [
	[
		'icon'     => 'calendar',
		'label'    => '開課時間',
		'value'    => $course_schedule,
		'disabled' => !\wc_string_to_bool( (string) $product->get_meta( 'show_course_schedule' ) ?: 'yes'),
	],
	[
		'icon'     => 'clock',
		'label'    => '預計時長',
		'value'    => "{$course_hour} 小時 {$course_minute} 分",
		'disabled' => !\wc_string_to_bool( (string) $product->get_meta( 'show_course_time' ) ?: 'yes'),
	],
	[
		'icon'     => 'list',
		'label'    => '預計章節',
		'value'    => "{$count_all_chapters} 個",
		'disabled' => !\wc_string_to_bool( (string) $product->get_meta( 'show_course_chapters' ) ?: 'yes'),
	],
	[
		'icon'     => 'eye',
		'label'    => '觀看時間',
		'value'    =>"{$limit_labels->type} {$limit_labels->value}",
		'disabled' => !\wc_string_to_bool( (string) $product->get_meta( 'show_course_limit' ) ?: 'yes'),
	],
	[
		'icon'     => 'team',
		'label'    => '課程學員',
		'value'    => "{$total_student} 人",
		'disabled' => !\wc_string_to_bool( (string) $product->get_meta( 'show_total_student' ) ?: 'yes'),
	],
];

$items = array_filter($items, fn( $item ) => !( $item['disabled'] ));

if ($items) {
	Plugin::load_template(
	'typography/title',
	[
		'value' => '課程資訊',
		'class' => 'mb-8 text-xl font-normal text-base-content',
	]
	);

	Plugin::load_template(
			'course-product/info',
			$items
		);

}
// echo '<div class="mt-8 flex items-end gap-4">';
// Plugin::load_template(
// 'countdown',
// [
// 'type'       => 'lg',
// 'item_class' => '',
// ]
// );
// echo '<h2>後課程即將停賣</h2>';
// echo '</div>';

echo '</div>';
// Tabs
Plugin::load_template( 'course-product/tabs', null, true, true );
// Footer
Plugin::load_template( 'course-product/footer', null, true, true );
echo '</div>';
