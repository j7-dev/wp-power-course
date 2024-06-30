<?php

/**
 * @var WC_Product $args
 */

use J7\PowerCourse\Templates\Templates;
use J7\PowerCourse\Utils\Course as CourseUtils;

$product = $args;

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
$count_all_chapters = (int) count( CourseUtils::get_sub_chapters( $product, true ) );

printf(
	'
<div id="pc-classroom-sider" class="fixed w-[25rem] left-0 overflow-x-scroll bg-white"
	style="border-right: 1px solid #eee;">
	<div class="flex justify-between items-center p-4">
		<span class="text-base tracking-wide font-bold">課程單元</span>
		<span class="text-sm text-gray-400">%1$s 個單元，%2$s分鐘</span>
	</div>
	%3$s
</div>
',
	$count_all_chapters,
	741,
	Templates::get( 'collapse/classroom-chapter', $product, false, false )
);
