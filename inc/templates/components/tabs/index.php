<?php
/**
 * @var mixed $args
 */

use J7\PowerCourse\Plugin;

$default_args = [
	'course_tabs'        => [],
	'default_active_key' => '0',
];

$args = wp_parse_args( $args, $default_args );

$course_tabs        = $args['course_tabs'];
$default_active_key = $args['default_active_key'];


Plugin::get('tabs/nav', $args);
Plugin::get('tabs/content', $args);
