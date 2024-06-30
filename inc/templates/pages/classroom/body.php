<?php

use J7\PowerCourse\Templates\Templates;

/**
 * @var WC_Product $args
 */
$product = $args;
?>
<div class="w-full bg-white">
	<?php
	Templates::get( 'classroom/header', $product );

	Templates::get(
		'bunny/video',
		[
			'library_id' => '244459',                               // TODO
			'video_id'   => 'fa7999b9-7b98-4852-84c1-880be189921d', // TODO
			'class'      => 'rounded-none',
		]
	);

	Templates::get( 'course-product/progress', $product );

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
	?>
</div>>
