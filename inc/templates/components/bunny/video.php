<?php

/**
 * @var array $args
 */

$default_props = [
	'class'      => 'rounded-xl',
	'library_id' => '',
	'video_id'   => '',
];

$props = wp_parse_args( $args, $default_props );

[
	'library_id' => $library_id,
	'video_id'   => $video_id,
	'class'      => $class,
] = $props;

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

echo '<div style="position:relative;padding-top:56.25%;">';
printf(
	'<iframe class="border-0 absolute top-0 left-0 w-full h-full %2$s" src="%1$s" loading="lazy" 
			allow="accelerometer;gyroscope;autoplay;encrypted-media;picture-in-picture;"
			allowfullscreen="true"></iframe>',
	$iframe_url,
	$class
);
echo '</div>';
