<?php
/**
 * Vidstack component 用 React 渲染，見 /js/src/App2.tsx
 * 可以撥放 youtube, vimeo, HLS (bunny) 影片
 *
 * @see https://www.vidstack.io
 */

use J7\PowerCourse\Plugin;
use J7\PowerCourse\Utils\Base;

$default_args = [
	'class'          => 'rounded-xl',
	'thumbnail_url'  => '',
	'hide_watermark' => false,
	'video_info'     => [
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
	'hide_watermark'  => $hide_watermark,
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
$display_name    = $wp_current_user ? $wp_current_user->display_name : '';
$username        = $wp_current_user ? $wp_current_user->user_login : '';
$ip              = Base::get_real_ip();
global $chapter;
$post_title = $chapter ? $chapter?->post_title : '';

$watermark_qty      = $hide_watermark ? '0' : \get_option( 'pc_watermark_qty', '0' );
$watermark_color    = \get_option( 'pc_watermark_color', 'rgba(205, 205, 205, 0.5)' );
$watermark_interval = \get_option( 'pc_watermark_interval', '10' );
$watermark_text     = \get_option( 'pc_watermark_text', '用戶 {display_name} 正在觀看 {post_title} IP:{ip} <br /> Email:{email}' );

$watermark_text = str_replace( [ '{email}', '{ip}', '{display_name}', '{username}', '{post_title}' ], [ $email, $ip, $display_name, $username, $post_title ], $watermark_text );


printf(
/*html*/'
<div class="pc-vidstack relative aspect-video %1$s !overflow-hidden"
	data-src="%2$s"
	data-thumbnail_url="%3$s"
	data-watermark_text="%4$s"
	data-watermark_qty="%5$s"
	data-watermark_color="%6$s"
	data-watermark_interval="%7$s"
>
	<div class="z-10 animate-pulse aspect-video bg-gray-200 text-gray-400 tracking-widest flex items-center justify-center %1$s">LOADING...</div>
</div>
',
	$class,
	$src,
	$thumbnail_url,
	$watermark_text,
	$watermark_qty,
	$watermark_color,
	$watermark_interval
);
