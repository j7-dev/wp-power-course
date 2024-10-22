<?php
/**
 * Single Product Card
 */

use J7\PowerCourse\Plugin;

$default_args = [
	'product' => $GLOBALS['product'] ?? null,
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
	throw new \Exception( 'product 不是 WC_Product' );
}

$is_free = 'yes' === $product->get_meta( 'is_free' );

if ( $is_free ) {
	Plugin::get(
		'card/single-product-free',
		[
			'product' => $product,
		]
		);
	return;
}

Plugin::get(
	'card/single-product-sale',
	[
		'product' => $product,
	]
	);
return;
