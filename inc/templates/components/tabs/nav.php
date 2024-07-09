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


echo '<div class="flex gap-0 text-gray-400 justify-between [&_.active]:!border-gray-600 [&_.active]:text-gray-600 [&_div:hover]:!border-gray-600 [&_div:hover]:text-gray-600" style="border-bottom: 3px solid #eee;">';

foreach ( $course_tabs as $course_tab ) {
	printf(
		'<div id="tab-nav-%1$s" class="cursor-pointer w-full text-center py-2 px-2 lg:px-8 relative -bottom-[3px] font-normal lg:font-semibold transition text-sm lg:text-base duration-300 ease-in-out %2$s" style="border-bottom: 3px solid transparent;">%3$s</div>',
		$course_tab['key'],
		$default_active_key === $course_tab['key'] ? 'active' : '',
		$course_tab['label']
	);
}

echo '</div>';
