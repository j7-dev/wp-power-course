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

echo '<div class="mb-12">';
echo '<div class="flex gap-0 text-gray-400 justify-between [&_.active]:!border-gray-500 [&_.active]:text-gray-500 [&_div:hover]:!border-gray-500 [&_div:hover]:text-gray-500" style="border-bottom: 3px solid #eee;">';

foreach ( $course_tabs as $course_tab ) {
	printf(
		'<div id="tab-nav-%1$s" class="cursor-pointer w-full text-center py-2 px-8 relative -bottom-[3px] font-semibold transition duration-300 ease-in-out %2$s" style="border-bottom: 3px solid transparent;">%3$s</div>',
		$course_tab['key'],
		$default_active_key === $course_tab['key'] ? 'active' : '',
		$course_tab['label']
	);
}

echo '</div>';
echo '<div class="[&_.active]:!block">';

foreach ( $course_tabs as $course_tab ) {
	printf(
		'<div id="tab-content-%1$s" class="hidden py-8 %2$s">%3$s</div>',
		$course_tab['key'],
		$default_active_key === $course_tab['key'] ? 'active' : '',
		$course_tab['content']
	);
}

echo '</div>';
echo '</div>';
?>
<script>
	// TODO 打包
	(function ($) {
		$('div[id^="tab-nav-"]').on('click', function () {
			$(this).addClass('active').siblings().removeClass('active')
			$('#tab-content-' + $(this).attr('id').split('-')[2]).addClass('active').siblings().removeClass('active')
		})
	})(jQuery)
</script>
