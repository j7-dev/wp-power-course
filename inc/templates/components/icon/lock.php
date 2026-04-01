<?php
/**
 * Icon: Lock
 * 鎖頭圖示，用於線性觀看鎖定章節
 */

use J7\PowerCourse\Utils\Base;

$default_args = [
	'class' => 'size-6',
	'color' => '#9ca3af', // gray-400 顏色
];

/**
 * @var array $args
 * @phpstan-ignore-next-line
 */
$args = wp_parse_args( $args, $default_args );

[
	'class' => $class,
	'color' => $color,
] = $args;

printf(
	/*html*/'
	<svg xmlns="http://www.w3.org/2000/svg" class="%1$s" viewBox="0 0 24 24" fill="none">
		<path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C9.23858 2 7 4.23858 7 7V9H5C3.89543 9 3 9.89543 3 11V19C3 20.1046 3.89543 21 5 21H19C20.1046 21 21 20.1046 21 19V11C21 9.89543 20.1046 9 19 9H17V7C17 4.23858 14.7614 2 12 2ZM15 9V7C15 5.34315 13.6569 4 12 4C10.3431 4 9 5.34315 9 7V9H15ZM12 13C12.5523 13 13 13.4477 13 14V16C13 16.5523 12.5523 17 12 17C11.4477 17 11 16.5523 11 16V14C11 13.4477 11.4477 13 12 13Z" fill="%2$s"/>
	</svg>',
	esc_attr( $class ),
	esc_attr( $color )
);
