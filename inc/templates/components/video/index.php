<?php
/**
 * Video component
 */

use J7\PowerCourse\Plugin;

$default_args = [
	'class'      => 'rounded-xl',
	'video_info' => [
		'type' => 'youtube',
		'id'   => '',
		'meta' => [],
	],
];

/**
 * @var array $args
 * @phpstan-ignore-next-line
 */
$args = wp_parse_args( $args, $default_args );

/**
 * @var array{type: string, id: string, meta: ?array} $video_info
 */
[
	'class'      => $class,
	'video_info'   => $video_info,
] = $args;

$video_type = $video_info['type'] ?? 'none';

if ('none' === $video_type) {
	// Plugin::get(
	// 'video/404',
	// [
	// 'message' => '缺少 影片資訊 ，請聯絡老師',
	// ]
	// );
	echo '';
	return;
}

if ('youtube' === $video_type) {
	Plugin::get(
		'video/iframe/youtube',
		[
			'video_info' => $video_info,
			'class'      => $class,
		]
		);
}

if ('vimeo' === $video_type) {
	Plugin::get(
		'video/iframe/vimeo',
		[
			'video_info' => $video_info,
			'class'      => $class,
		]
		);
}

if ('bunny-stream-api' === $video_type) {
	$library_id = \get_option( 'bunny_library_id', '' );
	Plugin::get(
		'video/bunny',
		[
			'library_id' => $library_id,
			'video_info' => $video_info,
			'class'      => $class,
		]
		);
}


if ('code' === $video_type) {
	Plugin::get(
		'video/code',
		[
			'video_info' => $video_info,
			'class'      => $class,
		]
		);
}
