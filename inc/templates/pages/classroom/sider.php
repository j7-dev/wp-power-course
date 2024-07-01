<?php

/**
 * @var WC_Product $args
 */

use J7\PowerCourse\Templates\Templates;
use J7\PowerCourse\Utils\Course as CourseUtils;

global $product;

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
	'
<div id="pc-classroom-sider" class="fixed w-[25rem] left-0 overflow-x-scroll bg-white z-[99999]"
	style="border-right: 1px solid #eee;">
	<div class="flex justify-between items-center p-4">
		<span class="text-base tracking-wide font-bold">課程單元</span>
		<span class="text-sm text-gray-400">%1$s 個單元%2$s</span>
	</div>
	%3$s
</div>
',
	$count_all_chapters,
	$course_length_in_minutes ? "，{$course_length_in_minutes} 分鐘" : '',
	Templates::get( 'collapse/classroom-chapter', null, false, false )
);
