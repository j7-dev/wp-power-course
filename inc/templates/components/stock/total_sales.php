<?php
/**
 * 顯示已售總數
 */

$default_args = [
	'product' => $GLOBALS['course'] ?? null,
	'class'   => 'mt-1',
];

/**
	* @var array{product: \WC_Product} $args
	* @phpstan-ignore-next-line
	*/
$args = wp_parse_args( $args, $default_args );

[
	'product' => $product,
	'class'   => $class,
] = $args;

$show_total_sales = \wc_string_to_bool($product->get_meta('show_total_sales'));

if (!$show_total_sales) {
	return;
}



$total_sales = $product->get_total_sales();

$color_class = 'bg-red-100 text-red-500';


echo <<<HTML
    <div class="{$class}">
        <span class="px-2 py-1 {$color_class} text-xs rounded-md font-bold">已售出 {$total_sales} 組</span>
    </div>
HTML;
