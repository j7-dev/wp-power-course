<?php
/**
 * Sidebar for classroom
 *
 * @deprecated 0.8.0 後棄用
 */

use J7\PowerCourse\Plugin;
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

$count_all_chapters       = count( CourseUtils::get_sub_chapter_ids( $product ) );
$course_length_in_minutes = CourseUtils::get_course_length( $product, 'minute' );



printf(
	/*html*/'
		<h2 class="text-lg text-bold tracking-wide my-0 line-clamp-2 h-14 pt-5 pl-0 lg:pl-4">%1$s</h2>
		<div class="flex justify-between items-center py-4 px-0 lg:px-4">
			<span class="text-base tracking-wide font-bold">課程章節</span>
			<span class="text-sm text-gray-400">%2$s 個章節%3$s</span>
		</div>
		<div class="pc-sider-chapters overflow-y-auto -ml-4 lg:ml-0 -mr-4 lg:mr-0">
			%4$s
		</div>
',
	$product->get_title(),
	$count_all_chapters,
	$course_length_in_minutes ? "，{$course_length_in_minutes} 分鐘" : '',
	Plugin::load_template( 'collapse/classroom-chapter', null, false ),
);
