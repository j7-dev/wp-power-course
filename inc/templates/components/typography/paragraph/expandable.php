<?php
/**
 * Expandable component
 */

use J7\PowerCourse\Utils\Base;

/**
 * @var mixed $args
 */

$default_args = [
	'children'   => '',
	'height'     => 160,
	'wrap_class' => 'bg-gradient-to-t from-gray-50',
];

$args = wp_parse_args( $args, $default_args );

[
	'children' => $children,
	'height'  => $height,
	'wrap_class' => $wrap_class
] = $args;

printf(
	/*html*/'
	<div data-init-height="%1$s" data-init-bg="%2$s" class="pc-toggle-content text-gray-400 overflow-hidden relative" style="height:%1$spx;">
			<div class="pc-toggle-content__main">
				%3$s
			</div>
	</div>
	<div class="pc-toggle-content__wrap w-full relative h-10 bottom-10 left-0 cursor-pointer text-sm text-primary flex justify-center items-center font-semibold %2$s">
		<p class="relative top-[3rem]">展開內容</p>
	</div>
	',
	$height,
	$wrap_class,
	$children,
);
