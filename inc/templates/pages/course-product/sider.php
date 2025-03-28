<?php
/**
 * Sidebar for course product
 */

use J7\PowerCourse\Plugin;
use J7\PowerCourse\BundleProduct\Helper;

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

echo '<div id="course-pricing" class="w-full md:!w-[20rem] md:h-[calc(100vh-8rem)] overflow-y-auto md:sticky md:top-[8rem] flex flex-col gap-6">';

Plugin::load_template( 'card/single-product' );

$linked_products = Helper::get_bundle_products( (int) $product->get_id() );
foreach ( $linked_products as $linked_product ) {
	/** @var WC_Product $linked_product */
	if ( 'publish' !== $linked_product->get_status() ) {
		continue;
	}

	Plugin::load_template(
		'card/bundle-product',
		[
			'product' => $linked_product,
		]
		);
	continue;

}


echo '</div>';
