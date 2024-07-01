<?php

/**
 * @var WC_Product $product
 */

global $product;
if ( ! ( $product instanceof \WC_Product ) ) {
	throw new \Exception( 'product 不是 WC_Product' );
}

$name = $product->get_name();

printf(
	'
<div class="py-4 px-6 text-base">
	<h2 class="text-base text-bold tracking-wide">%1$s</h2>
</div>
',
	$name
);
