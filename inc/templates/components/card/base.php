<?php
/**
 * My Account 裡面上課用的卡片
 * 已登入，有上課進度，可直接上課
 */

use J7\PowerCourse\Utils\Base;

/**
 * @var WC_Product $args
 */
$product = $args;
if ( ! ( $product instanceof \WC_Product ) ) {
	throw new \Exception( 'product 不是 WC_Product' );
}

$name              = $product->get_name();
$product_image_url = Base::get_image_url_by_product( $product, 'full' );
$teacher_ids       = \get_post_meta( $product->get_id(), 'teacher_ids', false );
$teacher_name      = '';
foreach ( $teacher_ids as $key => $teacher_id ) {
	$is_last       = $key === count( $teacher_ids ) - 1;
	$connect       = $is_last ? '' : ' & ';
	$teacher       = \get_user_by( 'id', $teacher_id );
	$teacher_name .= $teacher->display_name . $connect;
}


printf(
	'
<div class="pc-course-card">
	<a href="%4$s">
		<div class="pc-course-card__image-wrap group">
	         <img class="pc-course-card__image group-hover:scale-125 transition duration-300 ease-in-out" src="%1$s" alt="%2$s" loading="lazy">
	    </div>
    </a>
    <h3 class="pc-course-card__name">%2$s</h3>
    <p class="pc-course-card__teachers">by %3$s</p>
</div>
',
	$product_image_url,
	$name,
	$teacher_name,
	site_url( 'classroom' . '/' . $product->get_slug() )
);
