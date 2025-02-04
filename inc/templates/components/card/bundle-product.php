<?php
/**
 * Bundle product card
 */

use J7\PowerCourse\BundleProduct\Helper;
use J7\PowerCourse\Plugin;
use J7\PowerCourse\Utils\Base;

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

$helper = Helper::instance( $product );
if ( ! $helper?->is_bundle_product ) {
	throw new \Exception( 'product 不是 BundleProduct' );
}


$pbp_product_ids = $helper?->get_product_ids() ?? []; // @phpstan-ignore-line

$product_name = $product->get_name();

$purchase_note = \wpautop( $product->get_purchase_note() );

$bundle_type_label = $product->get_meta( 'bundle_type_label' );

$is_on_sale      = $product->is_on_sale();
$date_on_sale_to = $product->get_date_on_sale_to()?->date('Y/m/d H:i');

echo '<div class="w-full bg-base-100 shadow-lg rounded p-6">';
printf(
/*html*/'
  <p class="text-xs text-center mb-1 text-error">%1$s</p>
	<h6 class="text-base text-base-content font-semibold text-center">%2$s</h6>
',
	$bundle_type_label,
	$product_name
);

Plugin::get( 'divider' );

printf(
/*html*/'<div class="mb-6 text-sm">%s</div>',
	$purchase_note
);

foreach ( $pbp_product_ids as $pbp_product_id ) :
	if (!is_numeric($pbp_product_id)) {
		continue;
	}
	$pbp_product = \wc_get_product( $pbp_product_id );
	echo '<div>';
	Plugin::get(
		'course-product/list',
		[
			'product' => $pbp_product,
		]
		);
	echo '</div>';
	Plugin::get( 'divider' );
endforeach;

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


if ('subscription' === $product->get_type()) {
	$product_meta_data = Base::get_subscription_product_meta_data_label( $product );
	echo '<div class="grid grid-cols-2 gap-y-1.5 mt-2">';
	foreach ($product_meta_data as $key => $meta_data) {
		printf(
		/*html*/'<span class="text-gray-500 text-xs %1$s">- %2$s</span>',
		$key % 2 === 0 ? 'text-left' : 'text-right',
		$meta_data
		);
	}
	echo '</div>';
}


if ($is_on_sale && $date_on_sale_to) {
	printf(
	/*html*/'<p class="text-gray-500 text-xs text-center mt-2 mb-0">限時優惠至 %s</p>',
	$date_on_sale_to
	);
}

echo '</div>';
