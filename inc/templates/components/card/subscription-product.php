<?php
/**
 * Bundle product card
 */

use J7\PowerCourse\BundleProduct\BundleProduct;
use J7\PowerCourse\Plugin;

$default_args = [
	'product' => null, // BundleProduct
];

/**
 * @var array $args
 * @phpstan-ignore-next-line
 */
$args = wp_parse_args( $args, $default_args );

[
	'product' => $product,
] = $args;

if ( ! ( $product instanceof \WC_Product_Subscription ) ) {
	throw new \Exception( 'product 不是 WC_Product_Subscription' );
}

$product_name = $product->get_name();

$purchase_note = \wpautop( $product->get_purchase_note() );

$bundle_type_label = $product->get_meta( 'bundle_type_label' );

$is_on_sale      = $product->is_on_sale();
$date_on_sale_to = $product->get_date_on_sale_to()?->date('Y/m/d H:i');

echo '<div class="w-full bg-white shadow-lg rounded p-6">';
printf(
/*html*/'
  <p class="text-xs text-center mb-1 text-red-400">%1$s</p>
	<h6 class="text-base font-semibold text-center">%2$s</h6>
',
	$bundle_type_label,
	$product_name
);

Plugin::get( 'divider' );

printf(
/*html*/'<div class="mb-6 text-sm">%s</div>',
	$purchase_note
);


echo '<div class="flex gap-3 justify-between items-end">';

Plugin::get(
	'price',
	[
		'product' => $product,
		'size'    => 'small',
	]
);

Plugin::get(
	'button/add-to-cart',
	[
		'product'       => $product,
		'type'          => 'primary',
		'class'         => 'px-6 text-white ',
		'wrapper_class' => '[&_a.wc-forward]:tw-hidden',
	]
);

echo '</div>';

if ($is_on_sale && $date_on_sale_to) {
	printf(
	/*html*/'<p class="text-gray-500 text-xs text-center mt-2 mb-0">限時優惠至 %s</p>',
	$date_on_sale_to
	);
}

echo '</div>';