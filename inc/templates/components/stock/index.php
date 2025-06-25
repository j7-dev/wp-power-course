<?php
/**
 * 顯示庫存組件
 */

use J7\PowerCourse\BundleProduct\Helper;
use J7\Powerhouse\Domains\Woocommerce\Model\Settings as WCSettings;

$default_args = [
	'product' => $GLOBALS['course'] ?? null,
];

/**
	* @var array{product: \WC_Product} $args
	* @phpstan-ignore-next-line
	*/
$args = wp_parse_args( $args, $default_args );

[
	'product' => $product,
] = $args;

$helper = Helper::instance( $product );
if ($helper?->is_bundle_product ) {
	$course_product = $helper->get_course_product();
	if (!$course_product) {
		$course_product = $product;
	}
} else {
	$course_product = $product;
}

$show_stock_quantity = wc_string_to_bool($course_product->get_meta('show_stock_quantity'));

if (!$show_stock_quantity) {
	return;
}

$product_low_stock_amount = $product->get_low_stock_amount();

$notify_low_stock_amount = ( $product_low_stock_amount === '' ) ? (int) WCSettings::instance()->notify_low_stock_amount : (int) $product_low_stock_amount;

$stock_quantity = $product->get_stock_quantity();

$color_class = 'bg-green-100 text-green-500';
if ($stock_quantity <= $notify_low_stock_amount) {
	$color_class = 'bg-red-100 text-red-500';
}

if ($stock_quantity <= 0) {
	$color_class = 'bg-gray-100 text-gray-500';
}

printf(
/*html*/'
<div class="mt-1 flex">
	<span class="px-2 py-1 %1$s text-xs rounded-md font-bold">剩餘 %2$d 組</span>
</div>
',
$color_class,
$stock_quantity
);
