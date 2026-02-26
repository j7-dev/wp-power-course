<?php
/**
 * 顯示庫存組件
 */

use J7\Powerhouse\Domains\Woocommerce\Model\Settings as WCSettings;

$default_args = [
	'product' => $GLOBALS['course'] ?? null,
	'class'   => 'mt-1',
];

/**
	* @var array{product: \WC_Product} $args
	* @phpstan-ignore-next-line
	*/
$args = wp_parse_args( $args, $default_args );

[
	'product' => $product,
	'class'   => $class,
] = $args;

$managing_stock = $product->managing_stock();

if (!$managing_stock) {
	return;
}


$show_stock_quantity = wc_string_to_bool($product->get_meta('show_stock_quantity'));

$product_low_stock_amount = $product->get_low_stock_amount(); // 個別商品 override 低庫存通知數量

$notify_low_stock_amount = ( $product_low_stock_amount === '' ) ? (int) WCSettings::instance()->notify_low_stock_amount : (int) $product_low_stock_amount; // 檢查 個別商品是否 override 低庫存通知數量，如果沒有則使用設定值

$stock_quantity = $product->get_stock_quantity();

$color_class = 'bg-green-100 text-green-500';
if ($stock_quantity <= $notify_low_stock_amount) {
	$color_class = 'bg-red-100 text-red-500';
}

if ($stock_quantity <= 0) {
	$color_class = 'bg-gray-100 text-gray-500';
}

echo <<<HTML
    <div class="{$class}">
        <span class="px-2 py-1 {$color_class} text-xs rounded-md font-bold">剩餘 {$stock_quantity} 組</span>
    </div>
HTML;
