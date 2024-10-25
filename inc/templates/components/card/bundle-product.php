<?php
/**
 * Bundle product card
 */

use J7\PowerCourse\BundleProduct\BundleProduct;
use J7\PowerCourse\Plugin;

$default_args = [
	'bundle_product' => null, // BundleProduct
];

/**
 * @var array $args
 * @phpstan-ignore-next-line
 */
$args = wp_parse_args( $args, $default_args );

[
	'bundle_product' => $bundle_product,
] = $args;

if ( ! ( $bundle_product instanceof BundleProduct ) ) {
	throw new \Exception( 'product 不是 BundleProduct' );
}


$product_ids = $bundle_product->get_product_ids();

$bundle_title = $bundle_product->get_name();

$purchase_note = \wpautop( $bundle_product->get_purchase_note() );

$bundle_type_label = $bundle_product->get_meta( 'bundle_type_label' );

$is_on_sale      = $bundle_product->is_on_sale();
$date_on_sale_to = $bundle_product->get_date_on_sale_to()?->date('Y/m/d H:i');

echo '<div class="w-full bg-white shadow-lg rounded p-6">';
printf(
/*html*/'
  <p class="text-xs text-center mb-1 text-red-400">%1$s</p>
	<h6 class="text-base font-semibold text-center">%2$s</h6>
',
	$bundle_type_label,
	$bundle_title
);

Plugin::get( 'divider' );

printf(
/*html*/'<div class="mb-6 text-sm">%s</div>',
	$purchase_note
);

foreach ( $product_ids as $product_id ) :
	if (!is_numeric($product_id)) {
		continue;
	}
	$product = \wc_get_product( $product_id );
	echo '<div>';
	Plugin::get(
		'course-product/list',
		[
			'product' => $product,
		]
		);
	echo '</div>';
	Plugin::get( 'divider' );
endforeach;

echo '<div class="flex gap-3 justify-between items-end">';

Plugin::get(
	'price',
	[
		'product' => $bundle_product,
		'size'    => 'small',
	]
);

Plugin::get(
	'button/add-to-cart',
	[
		'product'       => $bundle_product,
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
