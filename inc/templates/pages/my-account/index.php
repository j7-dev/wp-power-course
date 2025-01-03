<?php
/**
 * My Account 我的學習
 */

use J7\PowerCourse\Plugin;


$course_tabs = [
	'all' => [
		'label'   => '所有課程',
		'content' => Plugin::get( 'course-product/grid', [ 'type' => 'all' ], false ),
	],
	'ready' => [
		'label'   => '已開課',
		'content' => Plugin::get( 'course-product/grid', [ 'type' => 'ready' ], false ),
	],
	'not-ready' => [
		'label'   => '尚未開課',
		'content' => Plugin::get( 'course-product/grid', [ 'type' => 'not-ready' ], false ),
	],
];

Plugin::get(
	'tabs',
	[
		'course_tabs'        => $course_tabs,
	]
);
