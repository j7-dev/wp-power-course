<?php
/**
 * Sidebar for course product
 */

use J7\PowerCourse\Plugin;
use J7\PowerCourse\BundleProduct\Helper;

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

echo '<div class="w-full md:w-[20rem] px-4 md:px-0 flex flex-col gap-6">';

Plugin::get( 'card/single-product' );

$linked_products = Helper::get_bundle_products( (int) $product->get_id() );
foreach ( $linked_products as $linked_product ) {
	/** @var WC_Product $linked_product */
	if ( 'publish' !== $linked_product->get_status() ) {
		continue;
	}

	Plugin::get(
		'card/bundle-product',
		[
			'product' => $linked_product,
		]
		);
	continue;

}


echo '</div>';
