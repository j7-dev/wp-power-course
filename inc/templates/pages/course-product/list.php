<?php
// TODO

use J7\PowerCourse\Utils\Base;

/**
 * @var WC_Product $args
 */
$product = $args;

if ( ! ( $product instanceof \WC_Product ) ) {
	throw new \Exception( 'product 不是 WC_Product' );
}

$regular_price = $product->get_regular_price();

$regular_price_html = $regular_price ? '<del class="block text-xs text-gray-600">NT$' . $regular_price . '</del>' : '';

$product_name = $product->get_name();

$product_image_url = Base::get_image_url_by_product( $product );

$regular_price = \wc_price( $product->get_regular_price() );

printf(
	'
<div class="flex gap-5">
	<div class="group w-1/3 aspect-video rounded overflow-hidden">
		<img class="w-full h-full object-cover group-hover:scale-125 transition duration-300 ease-in-out" src="%1$s" alt="%2$s" loading="lazy">
	</div>
	<div class="w-2/3">
		<h6 class="text-sm font-semibold mb-1">%2$s</h6>
		<del class="block text-xs text-gray-600">%3$s</del>
	</div>
</div>',
	$product_image_url,
	$product_name,
	$regular_price
);
