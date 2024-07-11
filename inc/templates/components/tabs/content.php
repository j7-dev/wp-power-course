<?php
/**
 * @var mixed $args
 */

$default_props = [
	'course_tabs'        => [],
	'default_active_key' => '0',
];

$props = wp_parse_args( $args, $default_props );

$course_tabs        = $props['course_tabs'];
$default_active_key = $props['default_active_key'];

echo '<div class="[&_.active]:!block mb-12">';

foreach ( $course_tabs as $course_tab ) {
	printf(
		'<div id="tab-content-%1$s" class="tw-hidden py-8 %2$s">%3$s</div>',
		$course_tab['key'],
		$default_active_key === $course_tab['key'] ? 'active' : '',
		$course_tab['content']
	);
}

echo '</div>';
