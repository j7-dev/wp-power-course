<?php
/**
 * Classroom > body > Progress
 */

/**
 * @var WC_Product $product
 */
use J7\PowerCourse\Utils\Course as CourseUtils;

global $product;

$progress = CourseUtils::get_course_progress( $product );

printf(
	'
<div class="flex gap-2 bg-gray-100 px-12 py-4 items-center">
<span class="text-gray-400 text-sm text-nowrap">上課進度</span>
<span class="text-primary text-sm text-nowrap font-bold">%1$s%%</span>
<progress class="pc-progress pc-progress-primary flex-1" value="%1$s" max="100"></progress>
</div>',
	$progress
);
