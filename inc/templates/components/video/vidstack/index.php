<?php
/**
 * Vidstack component 用 React 渲染，見 /js/src/App2.tsx
 * 可以撥放 youtube, vimeo, HLS (bunny) 影片
 *
 * @see https://www.vidstack.io
 */

use J7\PowerCourse\Plugin;

$default_args = [
	'class'         => 'rounded-xl',
	'thumbnail_url' => '',
	'video_info'    => [
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
	'thumbnail_url' => $thumbnail_url,
	'video_info'   => $video_info,
] = $args;

[
	'id'   => $video_id,
] = $video_info;

$bunny_cdn_hostname = \get_option( 'bunny_cdn_hostname', '' );

$src = match ($video_info['type']) {
	'youtube' => "youtube/{$video_id}",
	'vimeo' => "vimeo/{$video_id}",
	'bunny-stream-api' => "https://{$bunny_cdn_hostname}/{$video_id}/playlist.m3u8",
	default => '',
};

if ( !$video_id || !$src || ( !$bunny_cdn_hostname && 'bunny-stream-api' === $video_info['type'] ) ) {

	Plugin::get(
		'video/404',
		[
			'message' => '缺少 video_id | src ，請聯絡老師',
		]
		);
	return;
}



$wp_current_user = \wp_get_current_user();
$email           = $wp_current_user ? $wp_current_user->user_email : '';

$marquee_qty   = \get_option( 'pc_marquee_qty', '3' );
$marquee_color = \get_option( 'pc_marquee_color', 'rgba(205, 205, 205, 0.5)' );


printf(
/*html*/'
<div class="pc-vidstack relative aspect-video"
	data-src="%2$s"
	data-marquee_text="%3$s"
	data-thumbnail_url="%4$s"
	data-marquee_qty="%5$s"
	data-marquee_color="%6$s"
>
	<div class="z-10 animate-pulse aspect-video bg-gray-200 text-gray-400 tracking-widest flex items-center justify-center %1$s">LOADING...</div>
</div>
',
	$class,
	$src,
	$email,
	$thumbnail_url,
	$marquee_qty,
	$marquee_color
);
