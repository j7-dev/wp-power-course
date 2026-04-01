<?php
/**
 * Single Product Card
 */

use J7\PowerCourse\Plugin;

$default_args = [
	'product' => $GLOBALS['course'] ?? null,
];

/**
 * @var array $args
 * @phpstan-ignore-next-line
 */
$args = wp_parse_args( $args, $default_args );

[
	'product' => $product,
] = $args;

if ( ! ( $product instanceof \WC_Product ) ) {
	return;
}

// 外部課程使用專用卡片模板
if ( $product instanceof \WC_Product_External ) {
	Plugin::load_template(
		'card/single-product-external',
		[
			'product' => $product,
		]
		);
	return;
}

$is_free = 'yes' === $product->get_meta( 'is_free' );

if ( $is_free ) {
	Plugin::load_template(
		'card/single-product-free',
		[
			'product' => $product,
		]
		);
	return;
}

Plugin::load_template(
	'card/single-product-sale',
	[
		'product' => $product,
	]
	);
return;
