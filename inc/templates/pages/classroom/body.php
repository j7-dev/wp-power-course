<?php
/**
 * Body of the classroom page.
 */

use J7\PowerCourse\Templates\Templates;

/**
 * @var WC_Product $product
 */
global $product;

$chapter_id = \get_query_var( 'chapter_id' );
// TODO
$library_id = get_option( 'bunny_library_id', '244459' );
$video_id   = get_post_meta( $chapter_id, 'bunny_video_id', true );

echo '<div class="w-full bg-white">';

Templates::get( 'classroom/header' );

Templates::get(
	'bunny/video',
	[
		'library_id' => $library_id,
		'video_id'   => $video_id,
		'class'      => 'rounded-none',
	]
);

Templates::get( 'course-product/progress' );

$course_tabs = [
	[
		'key'     => '1',
		'label'   => '討論',
		'content' => '🚧 施工中... 🚧',
	],
	[
		'key'     => '2',
		'label'   => '教材',
		'content' => '🚧 施工中... 🚧',
	],
	[
		'key'     => '3',
		'label'   => '公告',
		'content' => '🚧 施工中... 🚧',
	],
	[
		'key'     => '4',
		'label'   => '評價',
		'content' => '🚧 施工中... 🚧',
	],
];

Templates::get(
	'tabs/base',
	[
		'course_tabs'        => $course_tabs,
		'default_active_key' => '1',
	]
);

echo '</div>';
