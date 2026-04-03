<?php
/**
 * Icon: Lock（鎖頭圖示）
 * 用於線性觀看模式下顯示鎖定章節的圖示
 */

use J7\PowerCourse\Utils\Base;

$default_args = [
	'class' => 'size-6',
	'color' => '#9ca3af', // gray-400
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
<svg class="%1$s" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
	<path fill-rule="evenodd" clip-rule="evenodd" d="M12 1C9.23858 1 7 3.23858 7 6V8H6C4.89543 8 4 8.89543 4 10V20C4 21.1046 4.89543 22 6 22H18C19.1046 22 20 21.1046 20 20V10C20 8.89543 19.1046 8 18 8H17V6C17 3.23858 14.7614 1 12 1ZM15 8V6C15 4.34315 13.6569 3 12 3C10.3431 3 9 4.34315 9 6V8H15ZM12 13C11.4477 13 11 13.4477 11 14V17C11 17.5523 11.4477 18 12 18C12.5523 18 13 17.5523 13 17V14C13 13.4477 12.5523 13 12 13Z" fill="%2$s"/>
</svg>',
	$class,
	$color
);
