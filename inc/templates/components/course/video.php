<?php

[
	'library_id' => $library_id,
	'video_id'   => $video_id,
] = $args;

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

?>
<!-- <div class="w-full rounded-2xl aspect-video bg-slate-400 animate-pulse"></div> -->


<div style="position:relative;padding-top:56.25%;">
	<iframe class="rounded-xl" src="
	<?php
	echo $iframe_url;
	?>
	" loading="lazy" style="border:0;position:absolute;top:0;height:100%;width:100%;"
			allow="accelerometer;gyroscope;autoplay;encrypted-media;picture-in-picture;"
			allowfullscreen="true"></iframe>
</div>