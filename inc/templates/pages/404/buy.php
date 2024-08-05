<?php
/**
 * 還沒購買課程
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
$course_permalink_structure = \get_option('course_permalink_structure', 'courses');
$message                    = sprintf(
	'您還沒購買此課程，無法上課，<a target="_blank" href="%1$s" class="text-primary font-semibold underline hover:opacity-70">前往購買</a>',
	site_url( "{$course_permalink_structure}/{$product->get_slug()}" )
);


echo '<div class="leading-7 text-gray-800 w-full max-w-[1138px] mx-auto  px-0 md:px-6 text-base font-normal pt-[5rem] pb-[10rem]">';
Templates::get(
	'alert',
	[
		'type'    => 'error',
		'message' => $message,
	]
);
Templates::get(
	'course-product/header',
	[
		'show_link' => true,
	]
	);
echo '</div>';
