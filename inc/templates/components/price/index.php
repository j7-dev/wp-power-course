<?php
/**
 * Price component
 */

$default_args = [
	'product' => null,
	'size'    => 'large',
];

/**
 * @var array $args
 * @phpstan-ignore-next-line
 */
$args = wp_parse_args( $args, $default_args );

[
	'product' => $product,
	'size'    => $size,
] = $args;

if ( ! ( $product instanceof \WC_Product ) ) {
	throw new \Exception( 'product 不是 WC_Product' );
}
$is_on_sale    = $product->is_on_sale();
$regular_price = $product->get_regular_price();
$sale_price    = $product->get_sale_price();


if ($is_on_sale) {
	printf(
		/*html*/'
		<div class="flex flex-col">
			<del aria-hidden="true" class="text-gray-600">%1$s</del>
			<ins class="text-red-400 text-2xl font-semibold">%2$s</ins>
		</div>
		',
		( is_numeric( $regular_price ) ? \wc_price( (float) $regular_price ) : $regular_price ),
		( is_numeric( $sale_price ) ? \wc_price( (float) $sale_price ) : $sale_price )
		);
	return;
}


printf(
		/*html*/'
		<div class="flex flex-col">
			<ins class="text-gray-600 text-2xl font-semibold">%1$s</ins>
		</div>
		',
		( is_numeric( $regular_price ) ? \wc_price( (float) $regular_price ) : $regular_price ),
		);
