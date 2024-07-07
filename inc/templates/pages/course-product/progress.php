<?php
/**
 * Classroom > body > Progress
 */

use J7\PowerCourse\Utils\Course as CourseUtils;

$default_args = [
	'product' => $GLOBALS['product'],
	'label'   => '上課進度',
];

/**
 * @var array $args
 * @phpstan-ignore-next-line
 */
$args = wp_parse_args( $args, $default_args );

[
	'product' => $product,
	'label'   => $label,
] = $args;

if ( ! ( $product instanceof \WC_Product ) ) {
	throw new \Exception( 'product 不是 WC_Product' );
}

$progress = CourseUtils::get_course_progress( $product );

printf(
	/*html*/'
<div class="flex gap-2 bg-gray-100 px-12 py-4 items-center">
	<span class="text-gray-400 text-sm text-nowrap">%1$s</span>
	<span class="text-primary text-sm text-nowrap font-bold">%2$s%%</span>
	<progress class="pc-progress pc-progress-primary flex-1" value="%1$s" max="100"></progress>
</div>',
$label,
	$progress
);
