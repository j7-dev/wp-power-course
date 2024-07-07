<?php
/**
 * Title component
 */

use J7\PowerCourse\Utils\Base;

/**
 * @var mixed $args
 */

$default_args = [
	'level'  => 'h2', // 'h1', 'h2', 'h3', 'h4', 'h5', 'h6
	'value'  => '標題',
	'color'  => Base::PRIMARY_COLOR,
	'class'  => '',
	'styles' => [],
];

$args = wp_parse_args( $args, $default_args );

$color = $args['color'];

$styles      = [
	'border-left'   => "4px solid {$color}",
	'padding-left'  => '0.75rem',
	'margin-bottom' => '2rem',
	'font-size'     => '1.25rem',
	'font-weight'   => '400',
	'color'         => '#333333',
];
$args_styles = $args['styles'];
if ( ! is_array( $args_styles ) ) {
	$args_styles = [];
}
$styles = \array_merge( $styles, $args_styles );

$style = '';
foreach ( $styles as $key => $value ) {
	$style .= $key . ':' . $value . ';';
}

$html = sprintf(
	'<%1$s class="%2$s" style="%3$s">%4$s</%1$s>',
	$args['level'], // %1$s
	$args['class'], // %2$s
	$style, // %3$s
	$args['value'], // %4$s
);

echo $html;
