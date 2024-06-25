<?php

$props   = $args;
$product = $props['product'] ?? null;

if ( ! ( $product instanceof \WC_Product ) ) {
	throw new \Exception( 'product 不是 WC_Product' );
}

$default_props = [
	'size' => 'large',
];

$props = \array_merge( $default_props, $props );

$regular_price = $product->get_regular_price();
$sale_price    = $product->get_sale_price();

$price_html  = '<del aria-hidden="true" class="text-gray-600">' . ( is_numeric( $regular_price ) ? \wc_price( $regular_price ) : $regular_price ) . '</del>';
$price_html .= '<ins class="text-red-400 text-2xl font-semibold">' . ( is_numeric( $sale_price ) ? \wc_price( $sale_price ) : $sale_price ) . '</ins>';


?>
<div class="flex flex-col">
	<?php echo $price_html; ?>
</div>
