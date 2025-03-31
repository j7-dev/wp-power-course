<?php
/**
 * 帶有價格，用於銷售用的卡片
 */

use J7\PowerCourse\Plugin;
use J7\PowerCourse\Utils\Base;
use J7\PowerCourse\Resources\Chapter\Utils\Utils as ChapterUtils;
use J7\PowerCourse\Utils\User as UserUtils;

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
	return;
}

$product_id  = $product->get_id();
$chapter_ids = ChapterUtils::get_flatten_post_ids($product_id);

$name              = $product->get_name();
$product_image_url = Base::get_image_url_by_product( $product, 'full' );
$teacher_ids       = \get_post_meta( $product_id, 'teacher_ids', false );
$teacher_ids       = is_array($teacher_ids) ? $teacher_ids : [];
$teacher_name      = 'by ';
foreach ( $teacher_ids as $key => $teacher_id ) {
	$is_last       = $key === count( $teacher_ids ) - 1;
	$connect       = $is_last ? '' : ' & ';
	$teacher       = \get_user_by( 'id', (int) $teacher_id );
	$teacher_name .= $teacher ? $teacher->display_name . $connect : '';
}
$teacher_name = count($teacher_ids) > 0 ? $teacher_name : '&nbsp;';

// 標籤顯示
$is_popular  = \get_post_meta( $product_id, 'is_popular', true ) === 'yes';
$is_featured = \get_post_meta( $product_id, 'is_featured', true ) === 'yes';

$tags_html = '<div class="flex gap-2 items-center my-2 h-6">';
if ($is_popular) {
	$tags_html .= Plugin::load_template('badge/popular', null, false);
}
if ($is_featured) {
	$tags_html .= Plugin::load_template('badge/feature', null, false);
}

if (!$is_popular && !$is_featured) {
	$tags_html .= Plugin::load_template('badge/join', null, false);
}

$tags_html .= '</div>';

// 課程時長
$course_hour   = (int) $product->get_meta( 'course_hour' );
$course_minute = (int) $product->get_meta( 'course_minute' );

$course_length      = "{$course_hour} 小時 {$course_minute} 分";
$course_length      = $course_hour + $course_minute > 0 ? $course_length : '-';
$course_length_html = Plugin::load_template('icon/clock', null, false) . $course_length;

// 學員人數
$total_student      = ( UserUtils::count_student( $product->get_id() ) ) + ( (int) $product->get_meta( 'extra_student_count' ) );
$show_total_student = \wc_string_to_bool( (string) $product->get_meta( 'show_total_student' ) ?: 'yes');
$total_student      = $show_total_student ? $total_student : '-';
$total_student_html = Plugin::load_template('icon/team', null, false) . $total_student;

printf(
	/*html*/'
<div class="pc-course-card">
	<a href="%1$s">
		<div class="pc-course-card__image-wrap pc-course-card__image-wrap-product group mb-0">
			<img class="pc-course-card__image group-hover:scale-110 transition duration-300 ease-in-out" src="%2$s" alt="%3$s" loading="lazy">
	  </div>
  </a>
	%4$s
	<a href="%1$s">
		<h3 class="pc-course-card__name">%3$s</h3>
	</a>
	<p class="pc-course-card__teachers !mb-1 md:!mb-4">%5$s</p>
	<div class="pc-course-card__price h-[2.5rem] md:h-8">%6$s</div>
	<div class="flex gap-2 items-center justify-between border-y border-x-0 border-solid border-gray-300 py-2 mt-2">
		<div class="text-base-content text-xs font-semibold flex items-center gap-1 [&_svg]:size-3.5 [&_svg_path]:stroke-gray-400">%7$s</div>
		<div class="text-base-content text-xs font-semibold flex items-center gap-1 [&_svg]:size-3.5 [&_svg]:fill-gray-400">%8$s</div>
	</div>
</div>
',
$product->get_permalink(),
	$product_image_url,
	$name,
	$tags_html,
	$teacher_name,
	Base::get_price_html($product),
	$course_length_html,
	$total_student_html,
);
