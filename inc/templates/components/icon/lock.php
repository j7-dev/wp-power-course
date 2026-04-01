<?php
/**
 * Icon: Lock
 * 鎖頭圖示，用於線性觀看模式中標示被鎖定的章節
 */

$default_args = [
	'class' => 'size-6', // 可以用 tailwind 子選擇器覆寫路徑顏色 跟 透明度
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
	<path fill-rule="evenodd" clip-rule="evenodd" d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" fill="%2$s" opacity="0.3"/>
	<path d="M15 10V9C15 7.34315 13.6569 6 12 6C10.3431 6 9 7.34315 9 9V10M12 13.5V15.5M10.2 18H13.8C14.9201 18 15.4802 18 15.908 17.782C16.2843 17.5903 16.5903 17.2843 16.782 16.908C17 16.4802 17 15.9201 17 14.8V13.2C17 12.0799 17 11.5198 16.782 11.092C16.5903 10.7157 16.2843 10.4097 15.908 10.218C15.4802 10 14.9201 10 13.8 10H10.2C9.07989 10 8.51984 10 8.09202 10.218C7.71569 10.4097 7.40973 10.7157 7.21799 11.092C7 11.5198 7 12.0799 7 13.2V14.8C7 15.9201 7 16.4802 7.21799 16.908C7.40973 17.2843 7.71569 17.5903 8.09202 17.782C8.51984 18 9.07989 18 10.2 18Z" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
</svg>',
	$class,
	$color
);
