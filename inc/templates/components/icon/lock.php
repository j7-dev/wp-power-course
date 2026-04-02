<?php
/**
 * Icon: Lock
 *
 * Heroicons 風格的鎖頭圖示
 */

use J7\PowerCourse\Utils\Base;

$default_args = [
	'class' => 'size-6', // 可以用 tailwind 子選擇器覆寫路徑顏色 跟 透明度
	'color' => Base::PRIMARY_COLOR,
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
	<path fill-rule="evenodd" clip-rule="evenodd" d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" fill="%2$s" opacity="0.3"/>
	<path fill-rule="evenodd" clip-rule="evenodd" d="M12 7C10.8954 7 10 7.89543 10 9V10H14V9C14 7.89543 13.1046 7 12 7ZM15.5 10V9C15.5 7.067 13.933 5.5 12 5.5C10.067 5.5 8.5 7.067 8.5 9V10C7.67157 10 7 10.6716 7 11.5V16.5C7 17.3284 7.67157 18 8.5 18H15.5C16.3284 18 17 17.3284 17 16.5V11.5C17 10.6716 16.3284 10 15.5 10ZM12 14.5C12.5523 14.5 13 14.0523 13 13.5C13 12.9477 12.5523 12.5 12 12.5C11.4477 12.5 11 12.9477 11 13.5C11 14.0523 11.4477 14.5 12 14.5Z" fill="#fff"/>
</svg>',
	$class,
	$color
);
