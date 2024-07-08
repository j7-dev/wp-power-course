<?php
/**
 * Feature video for course product
 */

use J7\PowerCourse\Templates\Templates;

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

$product_id   = $product->get_id();
$product_name = $product->get_name();
$teacher_ids  = \get_post_meta( $product_id, 'teacher_ids', false );
if ( ! is_array( $teacher_ids ) ) {
	$teacher_ids = [];
}


// TODO 清除預設值 DELETE
$library_id = \get_option( 'library_id', '244459' );

$feature_video = \get_post_meta( $product_id, 'feature_video', true );
$image_id      = $product->get_image_id();
$image_url     = \wp_get_attachment_image_url( (int) $image_id, 'full' );

if ( ! ! $feature_video ) {
	Templates::get(
		'bunny/video',
		[
			'library_id' => $library_id,
			'video_id'   => $feature_video,
		]
	);
} else {
	printf(
		'<div class="group w-full rounded-2xl aspect-video overflow-hidden">
			<img src="%1$s" class="h-full w-full object-cover group-hover:scale-125 duration-300 transition ease-in-out" alt="%2$s" loading="lazy">
			</div>',
		$image_url,
		$product_name
	);
}
