<?php

use J7\PowerCourse\Templates\Templates;


$course_tabs = [
	[
		'key'     => '1',
		'label'   => '所有課程',
		'content' => Templates::get( 'course-product/grid', [ 'type' => 'all' ], false, false ),
	],
	[
		'key'     => '2',
		'label'   => '已開課',
		'content' => Templates::get( 'course-product/grid', [ 'type' => 'launched' ], false, false ),
	],
	[
		'key'     => '3',
		'label'   => '尚未開課',
		'content' => Templates::get( 'course-product/grid', [ 'type' => 'un_launched' ], false, false ),
	],
];

Templates::get(
	'tabs/base',
	[
		'course_tabs'        => $course_tabs,
		'default_active_key' => '1',
	]
);
