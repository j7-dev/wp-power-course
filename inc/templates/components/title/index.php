<?php
/**
 * Title
 */

declare(strict_types=1);

namespace J7\PowerCourse\Templates\Components;

use J7\PowerCourse\Utils\Base;

/**
 * Class FrontEnd
 */
abstract class Title {


	/**
	 * Button
	 *
	 * @param array|null $props props.
	 * - title: string
	 * - color: string
	 * @return string
	 */
	public static function title( ?array $props = array() ): string {

		$default_props = array(
			'level'  => 'h2', // 'h1', 'h2', 'h3', 'h4', 'h5', 'h6
			'value'  => '標題',
			'color'  => Base::PRIMARY_COLOR,
			'class'  => '',
			'styles' => array(),
		);

		$props = \array_merge( $default_props, $props );

		$color = $props['color'];

		$styles       = array(
			'border-left'   => "4px solid {$color}",
			'padding-left'  => '0.75rem',
			'margin-bottom' => '2rem',
			'font-size'     => '1.25rem',
			'font-weight'   => '400',
			'color'         => '#333333',
		);
		$props_styles = $props['styles'];
		if ( ! is_array( $props_styles ) ) {
			$props_styles = array();
		}
		$styles = \array_merge( $styles, $props_styles );

		$style = '';
		foreach ( $styles as $key => $value ) {
			$style .= $key . ':' . $value . ';';
		}

		$html = sprintf(
			'<%1$s class="%2$s" style="%3$s">%4$s</%1$s>',
			$props['level'], // %1$s
			$props['class'], // %2$s
			$style, // %3$s
			$props['value'], // %4$s
		);

		return $html;
	}
}
