<?php
/**
 * Button
 */

declare(strict_types=1);

namespace J7\PowerCourse\Templates\Components;

use J7\PowerCourse\Templates\Components\Icon;

/**
 * Class FrontEnd
 */
abstract class Button {


	/**
	 * Button
	 *
	 * @param array|null $props props.
	 * - type
	 * - children string
	 * - icon string
	 * - disabled bool
	 * - href string|null
	 * @return string
	 */
	public static function base( ?array $props = array() ): string {

		$default_props = array(
			'type'     => 'primary',
			'children' => '按鈕',
			'icon'     => '',
			'disabled' => false,
			'href'     => null,
			'class'    => '',
		);

		$props = \array_merge( $default_props, $props );

		$type       = $props['type'];
		$type_class = '';
		switch ( $type ) {
			case 'primary':
				$type_class = 'bg-blue-500 group-hover:bg-blue-400 text-white border-transparent';
				$icon_class = 'fill-white h-4 w-4';
				break;
			case 'outline':
				$type_class = 'bg-transparent group-hover:bg-blue-500 text-blue-700  group-hover:text-white border-2 border-blue-500 border-solid group-hover:border-transparent';
				$icon_class = 'fill-blue-500 h-4 w-4 group-hover:fill-white';
				break;
			default:
				$type_class = 'bg-blue-500 group-hover:bg-blue-300 text-white border-transparent';
				$icon_class = 'fill-white h-4 w-4';
				break;
		}
		$icon_class .= $props['children'] ? ' mr-1' : '';

		$icon = $props['icon'] ? call_user_func(
			array( Icon::class, $props['icon'] ),
			array(
				'class' => $icon_class,
			)
		) : '';

		$button_class = $type_class . ' ' . $props['class'];

		$html = sprintf(
			'<button type="button" class="%1$s py-0 px-3 rounded-md  transition duration-300 ease-in-out flex items-center justify-center whitespace-nowrap h-10 text-sm font-normal tracking-wide">
  %2$s %3$s
		</button>',
			$button_class, // %1$s
			$icon, // %2$s
			$props['children'], // %3$s
		);

		return $html;
	}

	/**
	 * Add to cart button
	 *
	 * TODO 改寫成 AJAX
	 *
	 * @param array|null $props props.
	 * - children string
	 * - icon string
	 * - href string|null
	 * @return string
	 */
	public static function add_to_cart( ?array $props = array() ): string {

		$default_props = array(
			'children' => '加入購物車',
			'icon'     => '',
			'href'     => '',
		);

		$props = \array_merge( $default_props, $props );

		$html  = '<a href="?add-to-cart=391" data-quantity="1" class="group product_type_simple add_to_cart_button ajax_add_to_cart" data-product_id="391" data-product_sku="" aria-describedby="" rel="nofollow">';
		$html .= self::base( $props );
		$html .= '</a>';

		return $html;
	}
}
