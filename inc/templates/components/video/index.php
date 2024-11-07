<?php
/**
 * Video component
 */

use J7\PowerCourse\Plugin;

$default_args = [
	'class'          => 'rounded-xl',
	'video_info'     => [
		'type' => 'youtube',
		'id'   => '',
		'meta' => [],
	],
	'thumbnail_url'  => '',
	'hide_watermark' => false,
];

/**
 * @var array $args
 * @phpstan-ignore-next-line
 */
$args = \wp_parse_args( $args, $default_args );

/**
 * @var array{type: string, id: string, meta: ?array} $video_info
 */
[
	'class'      => $class,
	'video_info'   => $video_info,
	'thumbnail_url' => $thumbnail_url,
	'hide_watermark' => $hide_watermark,
] = $args;

$video_type = $video_info['type'] ?? 'none';

if ('none' === $video_type) {
	return;
}

if ('code' === $video_type) {
	Plugin::get(
		'video/code',
		[
			'video_info' => $video_info,
			'class'      => $class,
		]
		);
	return;
}

Plugin::get(
	'video/vidstack',
	[
		'video_info'     => $video_info,
		'class'          => $class,
		'thumbnail_url'  => $thumbnail_url,
		'hide_watermark' => $hide_watermark,
	]
);
