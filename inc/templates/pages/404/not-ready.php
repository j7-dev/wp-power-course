<?php
/**
 * 課程還沒開始
 */

use J7\PowerCourse\Plugin;

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

$course_schedule_timestamp = (int) $product->get_meta( 'course_schedule' );

$message = sprintf(
	'課程尚未開始，預計於 %1$s 開始',
	\wp_date( 'Y/m/d H:i', $course_schedule_timestamp )
);


echo '<div class="leading-7 text-base-content w-full max-w-[1138px] mx-auto  px-0 md:px-6 text-base font-normal pt-0 lg:pt-[5rem] pb-[10rem]">';
Plugin::load_template(
	'alert',
	[
		'type'    => 'error',
		'message' => $message,
	]
);
Plugin::load_template(
	'course-product/header',
	[
		'show_link' => true,
	]
	);
echo '</div>';
