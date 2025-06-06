<?php
/**
 * Bunny video component
 */

use J7\PowerCourse\Plugin;

$default_args = [
	'class'      => 'rounded-xl',
	'library_id' => '',
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
	'library_id' => $library_id,
	'video_info'   => $video_info,
] = $args;

$video_id = $video_info['id'];

if ( ! $library_id || ! $video_id ) {
	Plugin::load_template(
		'video/404',
		[
			'message' => '缺少 ' . ( ! $library_id ? 'library_id' : 'video_id' ) . ' ，請聯絡老師',
		]
		);

	return;
}

$base_url = "https://iframe.mediadelivery.net/embed/{$library_id}/{$video_id}";

$iframe_url = add_query_arg(
	[
		'autoplay'   => 'true',
		'loop'       => 'false',
		'muted'      => 'false',
		'preload'    => 'true',
		'responsive' => 'true',
		'controls'   => 'true',
	],
	$base_url
);

echo '<div class="relative [&>*]:absolute [&>*]:top-0 [&>*]:left-0 [&>*]:w-full [&>*]:h-full" style="padding-top:56.25%;">';
printf(
	/*html*/'
	<iframe class="z-20 border-0 %2$s" src="%1$s" loading="lazy"
			allow="accelerometer;gyroscope;autoplay;encrypted-media;picture-in-picture;"
			allowfullscreen="true"></iframe>
	<div class="z-10 animate-pulse aspect-video bg-gray-200 text-gray-400 tracking-widest flex items-center justify-center %2$s">LOADING...</div>
			',
	$iframe_url,
	$class
);
echo '</div>';
