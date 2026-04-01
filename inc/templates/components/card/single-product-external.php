<?php
/**
 * 外部課程銷售頁側欄卡片
 *
 * 顯示展示用價格與「前往課程」CTA 按鈕（管理員可自訂文字），
 * 以新視窗開啟外部平台連結，不走站內購物流程。
 */

use J7\PowerCourse\Plugin;

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

if ( ! ( $product instanceof \WC_Product_External ) ) {
	return;
}

$hide_single_course = $product->get_meta( 'hide_single_course' ) ?: 'no';
if ( 'yes' === $hide_single_course ) {
	return;
}

$external_url = $product->get_product_url();
$button_text  = $product->get_button_text() ?: '前往課程';

printf(
/*html*/'
<div class="w-full bg-base-100 shadow-lg rounded p-6 relative">
	<span class="absolute top-3 right-3 text-xs text-gray-400" title="此課程位於外部平台">↗ 外部課程</span>
	<h6 class="text-base text-base-content font-semibold text-center">外部課程</h6>
	%1$s
	<div class="mt-8">%2$s</div>
	<div class="mt-8 mb-6 text-sm text-gray-500">實際購買請前往外部平台</div>
	<div class="flex gap-3">
		<a href="%3$s" target="_blank" rel="noopener noreferrer"
		   class="pc-external-cta-link flex-1 text-white bg-primary hover:bg-primary-focus text-center py-3 px-4 rounded font-semibold no-underline transition-colors">
			%4$s ↗
		</a>
	</div>
</div>
',
Plugin::load_template( 'divider', null, false ),
Plugin::load_template(
	'price',
	[
		'product' => $product,
	],
	false
),
esc_url( $external_url ),
esc_html( $button_text )
);
