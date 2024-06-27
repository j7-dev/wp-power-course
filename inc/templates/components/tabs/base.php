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

?>
<div class="mb-12">
	<div class="flex gap-4 text-gray-400 justify-between [&_.active]:!border-gray-800 [&_.active]:text-gray-800"
		style="border-bottom: 3px solid #eee;">
		<?php
		foreach ( $course_tabs as $course_tab ) {
			printf(
				'<div id="tab-nav-%1$s" class="cursor-pointer py-2 px-8 relative -bottom-[3px] font-semibold %2$s" style="border-bottom: 3px solid transparent;">%3$s</div>',
				$course_tab['key'],
				$default_active_key === $course_tab['key'] ? 'active' : '',
				$course_tab['label']
			);
		}
		?>
	</div>

	<div class="[&_.active]:!block">
		<?php
		foreach ( $course_tabs as $course_tab ) {
			printf(
				'<div id="tab-content-%1$s" class="hidden py-8 %2$s">%3$s</div>',
				$course_tab['key'],
				$default_active_key === $course_tab['key'] ? 'active' : '',
				$course_tab['content']
			);
		}
		?>
	</div>
</div>


<script>
	// TODO 打包
	(function ($) {
		$('div[id^="tab-nav-"]').on('click', function () {
			$(this).addClass('active').siblings().removeClass('active')
			$('#tab-content-' + $(this).attr('id').split('-')[2]).addClass('active').siblings().removeClass('active')
		})
	})(jQuery)
</script>
