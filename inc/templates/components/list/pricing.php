<?php
/**
 * 帶有價格，用於銷售用的卡片列表
 */

use J7\PowerCourse\Plugin;


$default_args = [
	'products' => [],
	'columns'  => 3,
];

/**
 * @var array $args
 * @phpstan-ignore-next-line
 */
$args = wp_parse_args( $args, $default_args );

[
	'products' => $products,
	'columns'  => $columns,
] = $args;

$grid_class = match ( (string) $columns) {
	'2' => '',
	'3' => 'lg:grid-cols-3',
	'4' => 'lg:grid-cols-4',
	default => 'lg:grid-cols-3',
};

echo "<div class='grid grid-cols-2 gap-x-5 gap-y-14 {$grid_class}'>";

foreach ($products as $product) {
	Plugin::get(
	'card/pricing',
	[
		'product' => $product,
	]
	);
}

echo '</div>';
