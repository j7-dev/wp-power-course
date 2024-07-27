<?php
/**
 * My Account 我的學習
 */

use J7\PowerCourse\Templates\Templates;


$course_tabs = [
	'all' => [
		'label'   => '所有課程',
		'content' => Templates::get( 'course-product/grid', [ 'type' => 'all' ], false ),
	],
	'ready' => [
		'label'   => '已開課',
		'content' => Templates::get( 'course-product/grid', [ 'type' => 'ready' ], false ),
	],
	'not-ready' => [
		'label'   => '尚未開課',
		'content' => Templates::get( 'course-product/grid', [ 'type' => 'not-ready' ], false ),
	],
];

Templates::get(
	'tabs',
	[
		'course_tabs'        => $course_tabs,
		'default_active_key' => 'all',
	]
);
