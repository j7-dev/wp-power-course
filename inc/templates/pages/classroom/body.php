<?php
/**
 * Body of the classroom page.
 */

use J7\PowerCourse\Templates\Templates;

// TODO 清除預設值
$library_id = get_option( 'bunny_library_id', '244459' );

$default_args = [
	'product' => $GLOBALS['product'] ?? null,
	'chapter' => $GLOBALS['chapter'],
];

/**
 * @var array $args
 * @phpstan-ignore-next-line
 */
$args = wp_parse_args( $args, $default_args );

[
	'product' => $product,
	'chapter' => $chapter,
] = $args;

if ( ! ( $product instanceof \WC_Product ) ) {
	throw new \Exception( 'product 不是 WC_Product' );
}

$chapter_id = $chapter->ID;

$video_id = get_post_meta( $chapter_id, 'bunny_video_id', true );

echo '<div class="w-full bg-white pt-16">';

Templates::get( 'classroom/header' );

Templates::get(
	'bunny/video',
	[
		'library_id' => $library_id,
		'video_id'   => $video_id,
		'class'      => 'rounded-none',
	]
);

echo '<div class="bg-gray-100 px-12 py-4">';
Templates::get( 'progress' );
echo '</div>';

/*
TODO  🚧 施工中... 🚧

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
'tabs',
[
'course_tabs'        => $course_tabs,
'default_active_key' => '1',
]
);
*/

echo '</div>';
