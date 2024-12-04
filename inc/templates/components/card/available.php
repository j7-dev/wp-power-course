<?php
/**
 * My Account 裡面上課用的卡片
 * 已登入，有上課進度，可直接上課
 */

use J7\PowerCourse\Plugin;
use J7\PowerCourse\Utils\Base;
use J7\PowerCourse\Utils\Course as CourseUtils;
use J7\PowerCourse\Resources\Course\MetaCRUD as AVLCourseMeta;

$default_args = [
	'product' => $GLOBALS['product'] ?? null,
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

$product_id  = $product->get_id();
$chapter_ids = CourseUtils::get_sub_chapters($product_id, return_ids :true);

$name              = $product->get_name();
$product_image_url = Base::get_image_url_by_product( $product, 'full' );
$teacher_ids       = \get_post_meta( $product_id, 'teacher_ids', false );
$teacher_name      = 'by ';
foreach ( $teacher_ids as $key => $teacher_id ) {
	$is_last       = $key === count( $teacher_ids ) - 1;
	$connect       = $is_last ? '' : ' & ';
	$teacher       = \get_user_by( 'id', $teacher_id );
	$teacher_name .= $teacher->display_name . $connect;
}
$teacher_name = count($teacher_ids) > 0 ? $teacher_name : '&nbsp;';

$current_user_id   = get_current_user_id();
$limit_labels      = CourseUtils::get_limit_label_by_product( $product );
$expire_date       = AVLCourseMeta::get( $product_id, $current_user_id, 'expire_date', true );
$expire_date_label = empty($expire_date) ? '無限期' : '至' . \wp_date('Y/m/d H:i', $expire_date);
$is_expired        = CourseUtils::is_expired($product, $current_user_id);
$avl_status        = CourseUtils::get_avl_status($product, $current_user_id);

$badge_html = Plugin::get(
	'badge',
	[
		'type'     => $avl_status['badge_color'],
		'children' => $avl_status['label'],
		'class'    => 'absolute top-2 right-2 text-white text-xs z-20',
	],
	false
	);


$last_visit_info = AVLCourseMeta::get( $product_id, $current_user_id, 'last_visit_info', true );
if ( $last_visit_info ) {
	$goto_chapter_id = $last_visit_info['chapter_id'] ?? null;
} else {
	$goto_chapter_id = count($chapter_ids) > 0 ? $chapter_ids[0] : null;
}
$goto_classroom_link = $goto_chapter_id ? \site_url( 'classroom' ) . "/{$product->get_slug()}/{$goto_chapter_id}" : site_url( '404' );

printf(
	/*html*/'
<div class="pc-course-card">
	<a href="%1$s">
		<div class="pc-course-card__image-wrap group">
			%2$s
			<img class="pc-course-card__image group-hover:scale-110 transition duration-300 ease-in-out" src="%3$s" alt="%4$s" loading="lazy">
	  </div>
  </a>
	<a href="%1$s">
		<h3 class="pc-course-card__name">%4$s</h3>
	</a>
	<p class="pc-course-card__teachers">%5$s</p>
	<div>%6$s</div>
	<div class="flex gap-2 items-center">
		<span class="text-gray-400 text-xs text-nowrap">觀看期限</span>
		<span class="text-primary text-xs text-nowrap font-bold">%7$s</span>
	</div>
</div>
',
$goto_classroom_link,
$badge_html,
	$product_image_url,
	$name,
	$teacher_name,
	Plugin::get(
		'progress/vertical',
		[
			'product' => $product,
		],
		false
	),
	$expire_date_label
);
