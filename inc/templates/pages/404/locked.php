<?php
/**
 * 章節被鎖定（線性觀看模式）
 * 學員必須先完成前面的章節才能觀看此章節
 */

use J7\PowerCourse\Plugin;

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

echo '<div class="leading-7 text-base-content w-full mx-auto  px-0 md:px-6 text-base font-normal pt-0 lg:pt-[5rem] pb-[10rem]" style="max-width: 1200px;">';
Plugin::load_template(
	'alert',
	[
		'type'    => 'warning',
		'message' => '請先完成前面的章節才能觀看此章節',
	]
);
Plugin::load_template(
	'course-product/header',
	[
		'show_link' => true,
	]
	);
echo '</div>';
