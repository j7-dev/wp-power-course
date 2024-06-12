<?php
/**
 * Rate
 */

declare(strict_types=1);

namespace J7\PowerCourse\Templates\Components;

use J7\PowerCourse\Templates\Components\Icons;

/**
 * Class FrontEnd
 */
abstract class Rate {


	/**
	 * Button
	 *
	 * @param array|null $props props.
	 * @return string
	 */
	public static function rate( ?array $props = array() ): string {

		$default_props = array(
			'show_before' => false, // 是否顯示前面的文字
			'count'       => 5, // 總共幾個星星
			'value'       => 3.7, // 有幾個星星是填滿的
			'total'       => null, // 有幾個評論 null | int
		);

		$props = \array_merge( $default_props, $props );

		ob_start();
		?>

		<?php

		$value       = $props['value'];
		$count       = $props['count'];
		$total       = $props['total'];
		$show_before = $props['show_before'];
		$rest        = fmod( $value, 1 );

		$fill_start_num    = ( (int) $value ) + ( $rest >= 0.8 ? 1 : 0 );
		$half_start_num    = ( $rest > 0.2 && $rest < 0.8 ) ? 1 : 0;
		$outline_start_num = $count - $fill_start_num - $half_start_num;
		$icons_html        = '';
		for ( $i = 0; $i < $fill_start_num; $i++ ) {
			$icons_html .= Icons::star();
		}
		$icons_html .= ( $half_start_num === 1 ) ? Icons::star(
			array(
				'type' => 'half',
			)
		) : '';

		for ( $i = 0; $i < $outline_start_num; $i++ ) {
			$icons_html .= Icons::star(
				array(
					'type' => 'outline',
				)
			);
		}

		?>
<div class="flex items-center gap-1 whitespace-nowrap">
		<?php if ( $show_before ) : ?>
	<span class="text-xl font-bold"><?php echo $value; ?></span>
		<?php endif; ?>
		<?php echo $icons_html; ?>
		<?php if ( null !== $total ) : ?>
		<span class="">(<?php echo $total; ?>)</span>
	<?php endif; ?>
</div>
		<?php
		$html = ob_get_clean();

		return $html;
	}
}
