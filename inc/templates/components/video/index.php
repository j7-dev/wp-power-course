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
	'next_post_url'  => '',
	'subtitles'      => [],
];

/**
 * @var array $args
 * @phpstan-ignore-next-line
 */
$args = \wp_parse_args( $args, $default_args );

/**
 * @var array{type: string, id: string, meta: ?array<string, mixed>} $video_info
 */
[
	'class'      => $class,
	'video_info'   => $video_info,
	'thumbnail_url' => $thumbnail_url,
	'hide_watermark' => $hide_watermark,
	'next_post_url'  => $next_post_url,
	'subtitles'      => $subtitles,
] = $args;

$video_type = $video_info['type'];

if ('none' === $video_type) {
	return;
}

if ('code' === $video_type) {
	Plugin::load_template(
		'video/code',
		[
			'video_info' => $video_info,
			'class'      => $class,
		]
		);
	return;
}

Plugin::load_template(
	'video/vidstack',
	[
		'video_info'     => $video_info,
		'class'          => $class,
		'thumbnail_url'  => $thumbnail_url,
		'hide_watermark' => $hide_watermark,
		'next_post_url'  => $next_post_url,
		'subtitles'      => $subtitles,
	]
);
