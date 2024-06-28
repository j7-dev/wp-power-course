<?php

use J7\PowerCourse\Templates\Templates;


/**
 * @var array $args
 */

$default_props = [
	'type'     => 'primary',
	'children' => '按鈕',
	'icon'     => '',
	'disabled' => false,
	'href'     => null,
	'class'    => '',
];

$props = wp_parse_args( $args, $default_props );

$btn_type   = $props['type'];
$type_class = '';
switch ( $btn_type ) {
	case 'primary':
		$type_class = 'bg-blue-500 hover:bg-blue-400 text-white border-transparent';
		$icon_class = 'fill-white h-4 w-4';
		break;
	case 'outline':
		$type_class = 'bg-transparent hover:bg-blue-500 text-blue-700  hover:text-white border-2 border-blue-500 border-solid hover:border-transparent';
		$icon_class = 'fill-blue-500 h-4 w-4 hover:fill-white';
		break;
	default:
		$type_class = 'bg-blue-500 hover:bg-blue-300 text-white border-transparent';
		$icon_class = 'fill-white h-4 w-4';
		break;
}
$icon_class .= $props['children'] ? ' mr-1' : '';

$icon = $props['icon'];

/** @noinspection PhpUnhandledExceptionInspection */
$icon_html = Templates::safe_get(
	"icon/{$icon}",
	[
		'class' => $icon_class,
	],
	load_once: false,
	echo: false
);

$button_class = $type_class . ' ' . $props['class'];

/** @noinspection HtmlUnknownTarget */
printf(
	'<a href="%4$s" class="%1$s py-0 px-3 rounded-md  transition duration-300 ease-in-out flex items-center justify-center whitespace-nowrap h-10 text-sm font-normal tracking-wide">
%2$s %3$s
</a>',
	$button_class, // %1$s
	$icon_html, // %2$s
	$props['children'], // %3$s
	$props['href'] ?? '#' // %4$s
);
