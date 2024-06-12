<?php
/**
 * Buttons
 */

declare(strict_types=1);

namespace J7\PowerCourse\Templates\Components;

use J7\PowerCourse\Templates\Components\Icons;

/**
 * Class FrontEnd
 */
abstract class Course {


	/**
	 * 課程資訊
	 *
	 * @param array|null $props props.
	 * @return string
	 */
	public static function info( ?array $props = array() ): string {

		$default_props = array(
			'items' => array(),
		);

		$props = array_merge( $default_props, $props );

		$items = $props['items'];

		if ( ! is_array( $items ) ) {
			echo 'items 必須是陣列';
			$items = array();
		}

		ob_start();
		?>
<div class="w-full grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-6">
		<?php foreach ( $items as $index => $item ) : ?>
	<div class="flex items-center gap-3">
	<div class="bg-blue-500 rounded-xl h-8 w-8 flex items-center justify-center">
			<?php
			$icon = call_user_func(
				array( Icons::class, $item['icon'] ),
				array(
					'class' => 'fill-white h-4 w-4',
				)
			);
			echo $icon;
			?>
	</div>
	<div>
			<?php echo $item['label']; ?>
	</div>
	<div class="font-semibold">
			<?php echo $item['value']; ?>
	</div>
</div>
		<?php endforeach; ?>

</div>




		<?php

		$html = ob_get_clean();

		return $html;
	}
}
