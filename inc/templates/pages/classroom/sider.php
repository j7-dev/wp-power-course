<?php
/**
 * Sidebar for classroom
 */

use J7\PowerCourse\Plugin;
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

// $chpters = \get_children(
// [
// 'post_parent' => $product->get_id(),
// 'post_type'   => RegisterCPT::POST_TYPE,
// 'numberposts' => - 1,
// 'post_status' => 'any',
// 'orderby'     => 'menu_order',
// 'order'       => 'ASC',
// ]
// );
$count_all_chapters       = (int) count( CourseUtils::get_sub_chapters( $product, true ) );
$course_length_in_minutes = CourseUtils::get_course_length( $product, 'minute' );

printf(
	/*html*/'
<div id="pc-classroom-sider" class="w-[25rem] left-0 bg-white z-50 h-screen"
	style="border-right: 1px solid #eee;position:fixed;">
	<div class="px-4 pt-5">
		<h2 class="text-lg text-bold tracking-wide my-0 line-clamp-2 h-14">%1$s</h2>
	</div>
	<div class="flex justify-between items-center p-4">
		<span class="text-base tracking-wide font-bold">課程單元</span>
		<span class="text-sm text-gray-400">%2$s 個單元%3$s</span>
	</div>
	<div class="overflow-y-auto" style="height: calc(100%% - 188px);">
		%4$s
	</div>
	<a
		href="%5$s"
		class="hover:opacity-75 transition duration-300"
	>
		<div class="flex gap-4 items-center py-4 pl-9 absolute bottom-0 w-full">
			<img class="w-6 h-6" src="%6$s" />
			<span class="text-gray-600 font-light">
					回《我的學習》
			</span>
		</div>
	</a>
</div>
',
	$product->get_title(),
	$count_all_chapters,
	$course_length_in_minutes ? "，{$course_length_in_minutes} 分鐘" : '',
	Templates::get( 'collapse/classroom-chapter', null, false ),
	\wc_get_account_endpoint_url( 'courses' ),
	Plugin::$url . '/inc/assets/src/assets/svg/wp.svg'
);
