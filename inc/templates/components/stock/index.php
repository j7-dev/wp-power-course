<?php
/**
 * 顯示庫存組件
 */

use J7\PowerCourse\BundleProduct\Helper;

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

$stock_quantity = $product->get_stock_quantity();

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

if (!$stock_quantity || !$show_stock_quantity) {
	return;
}

printf(
/*html*/'
<div class="mt-1 flex">
	<span class="px-2 py-1 bg-red-100 text-red-500 text-xs rounded-md font-bold">剩餘 %1$d 組</span>
</div>
',
$stock_quantity
);
