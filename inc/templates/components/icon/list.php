<?php

use J7\PowerCourse\Utils\Base;

/**
 * @var mixed $args
 */

$default_props = [
	'type'  => '',  // '
	'class' => 'w-6 h-6',
	'color' => Base::PRIMARY_COLOR,
];

$props = wp_parse_args( $args, $default_props );

$html = sprintf(
	'<svg class="%s" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
<path stroke="%2$s" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8h15M12 16h9M12 24h15"/>
<path fill="%2$s" d="M6 10a2 2 0 100-4 2 2 0 000 4zM6 18a2 2 0 100-4 2 2 0 000 4zM6 26a2 2 0 100-4 2 2 0 000 4z"/>
</svg>',
	$props['class'],
	$props['color']
);

echo $html;
