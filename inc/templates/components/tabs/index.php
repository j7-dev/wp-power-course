<?php
/**
 * Tabs
 */

declare(strict_types=1);

namespace J7\PowerCourse\Templates\Components;

use J7\PowerCourse\Templates\Components\Icon;

/**
 * Class FrontEnd
 */
abstract class Tabs {


	/**
	 * Tabs
	 *
	 * @param array|null $props props.
	 * - course_tabs
	 * -- key
	 * -- label
	 * -- content
	 * - default_active_key
	 * @return string
	 */
	public static function base( ?array $props = array() ): string {

		$default_props = array(
			'course_tabs'        => array(),
			'default_active_key' => '0',
		);

		$props = \array_merge( $default_props, $props );

		$course_tabs        = $props['course_tabs'];
		$default_active_key = $props['default_active_key'];

		ob_start();
		?>
<div class="mb-12">
<div class="flex gap-4 text-gray-400 justify-between [&_.active]:!border-gray-800 [&_.active]:text-gray-800" style="border-bottom: 3px solid #eee;">
		<?php foreach ( $course_tabs as $course_tab ) : ?>
		<div id="tab-nav-<?php echo $course_tab['key']; ?>" class="cursor-pointer py-2 px-8 relative -bottom-[3px] font-semibold <?php echo $default_active_key === $course_tab['key'] ? 'active' : ''; ?>" style="border-bottom: 3px solid transparent;"><?php echo $course_tab['label']; ?></div>
	<?php endforeach; ?>
</div>

<div class="[&_.active]:!block">
		<?php foreach ( $course_tabs as $course_tab ) : ?>
		<div id="tab-content-<?php echo $course_tab['key']; ?>" class="hidden py-8 <?php echo $default_active_key === $course_tab['key'] ? 'active' : ''; ?>">
			<?php echo $course_tab['content']; ?>
		</div>
	<?php endforeach; ?>
</div>
</div>


<script>
	// TODO 打包
(function($){
	$('div[id^="tab-nav-"]').on('click', function(){
		$(this).addClass('active').siblings().removeClass('active')
		$('#tab-content-' + $(this).attr('id').split('-')[2]).addClass('active').siblings().removeClass('active')
	})
})(jQuery)
</script>
		<?php
		$html = \ob_get_clean();

		return $html;
	}
}
