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
abstract class Buttons {


	/**
	 * Button
	 *
	 * @param array|null $props props.
	 * @return string
	 */
	public static function button( ?array $props = array() ): string {

		$default_props = array(
			'children' => '按鈕',
			'icon'     => '',
			'disabled' => false,
		);

		$props = \array_merge( $default_props, $props );
		$icon  = $props['icon'] ? call_user_func(
			array( Icons::class, $props['icon'] ),
			array(
				'class' => 'fill-blue-500 h-4 w-4 group-hover:fill-white mr-1',
			)
		) : '';

		$html = sprintf(
			'<div class="group %1$s"><button type="button" class="bg-transparent group-hover:bg-blue-500 text-blue-700 font-normal group-hover:text-white py-1 px-3 border-2 border-blue-500 border-solid group-hover:border-transparent rounded-lg text-sm transition duration-300 ease-in-out flex items-center whitespace-nowrap pointer-events-none">
  %2$s %3$s
		</button></div>',
			$props['disabled'] ? 'opacity-70 cursor-not-allowed' : '', // %1$s
			$icon, // %2$s
			$props['children'], // %3$s
		);

		return $html;
	}
}
