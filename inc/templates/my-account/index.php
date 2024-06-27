<?php

use J7\PowerCourse\Templates\Templates;

$course_tabs = [
	[
		'key'     => '1',
		'label'   => '所有課程',
		'content' => '所有課程',
	],
	[
		'key'     => '2',
		'label'   => '已開課',
		'content' => '已開課',
	],
	[
		'key'     => '3',
		'label'   => '尚未開課',
		'content' => '尚未開課',
	],
];

Templates::get(
	'tabs/base',
	[
		'course_tabs'        => $course_tabs,
		'default_active_key' => '1',
	]
);

echo '<div class="max-w-[20rem]">';
Templates::get(
	'card/base',
	wc_get_product( 2030 )
);
echo '</div>';
