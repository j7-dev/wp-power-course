<?php

use J7\PowerCourse\Templates\Templates;

/**
 * @var \WC_Product $product
 */
global $product;

$course_schedule_timestamp = $product->get_meta( 'course_schedule' );

$message = sprintf(
	'OOPS! 🤯 課程還沒開始，課程預計於 %1$s，開始',
	date( 'Y/m/d H:i', $course_schedule_timestamp )
);


echo '<div class="leading-7 text-gray-800 w-full max-w-[1138px] mx-auto  px-0 md:px-6 text-base font-normal pt-[5rem] pb-[10rem]">';

Templates::get(
	'alert',
	[
		'type'    => 'error',
		'message' => $message,
	]
);

Templates::get( 'course-product/header', $product );
echo '</div>';
