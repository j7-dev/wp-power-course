<?php
/**
 * My Account 裡面上課用的卡片
 * 已登入，有上課進度，可直接上課
 */

use J7\PowerCourse\Templates\Templates;
use J7\PowerCourse\Utils\Base;
use J7\PowerCourse\Utils\Course as CourseUtils;

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
$teacher_name      = '';
foreach ( $teacher_ids as $key => $teacher_id ) {
	$is_last       = $key === count( $teacher_ids ) - 1;
	$connect       = $is_last ? '' : ' & ';
	$teacher       = \get_user_by( 'id', $teacher_id );
	$teacher_name .= $teacher->display_name . $connect;
}


printf(
	/*html*/'
<div class="pc-course-card">
	<a href="%1$s">
		<div class="pc-course-card__image-wrap group">
	         <img class="pc-course-card__image group-hover:scale-125 transition duration-300 ease-in-out" src="%2$s" alt="%3$s" loading="lazy">
	    </div>
  </a>
	<a href="%1$s">
		<h3 class="pc-course-card__name">%3$s</h3>
	</a>
	<p class="pc-course-card__teachers">by %4$s</p>
	<div>%5$s</div>
</div>
',
site_url( 'classroom' ) . sprintf(
	'/%1$s/%2$s',
	$product->get_slug(),
	count($chapter_ids) > 0 ? $chapter_ids[0] : ''
),
	$product_image_url,
	$name,
	$teacher_name,
	Templates::get(
		'progress/vertical',
		[
			'product' => $product,
		],
		false
		)
);
