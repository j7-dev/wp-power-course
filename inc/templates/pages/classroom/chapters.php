<?php
/**
 * Sidebar for classroom
 */

use J7\PowerCourse\Templates\Templates;
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

$count_all_chapters       = (int) count( CourseUtils::get_sub_chapters( $product, true ) );
$course_length_in_minutes = CourseUtils::get_course_length( $product, 'minute' );



printf(
	/*html*/'
		<h2 class="text-lg text-bold tracking-wide my-0 line-clamp-2 h-14 pt-5">%1$s</h2>
		<div class="flex justify-between items-center py-4">
			<span class="text-base tracking-wide font-bold">課程單元</span>
			<span class="text-sm text-gray-400">%2$s 個單元%3$s</span>
		</div>
		<div class="pc-sider-chapters overflow-y-auto -ml-4 -mr-4">
			%4$s
		</div>
',
	$product->get_title(),
	$count_all_chapters,
	$course_length_in_minutes ? "，{$course_length_in_minutes} 分鐘" : '',
	Templates::get( 'collapse/classroom-chapter', null, false ),
);
