<?php
/**
 * Button component
 */

use J7\PowerCourse\Plugin;

$default_props = [
	'type'          => '', // primary | secondary | neutral | link | ghost | accent | info | success | warning | error
	'outline'       => false,
	'size'          => '', // xs | sm  | lg
	'children'      => '加入購物車',
	'icon'          => '',
	'icon_position' => 'start', // start | end
	'disabled'      => false,
	'href'          => '#',
	'class'         => '',
	'active'        => false,
	'glass'         => false,
	'attr'          => '',
	'shape'         => '', // square | circle
	'loading'       => false,
	'product'       => null, // 🆕  WC_Product
	'qty'           => 1, // 🆕
	'wrapper_class' => '[&_.added]:tw-hidden', // 🆕
];

/**
 * @var array $args
 * @phpstan-ignore-next-line
 */
$args    = wp_parse_args( $args, $default_props );
$product = $args['product'];
if (!( $product instanceof \WC_Product )) {
	throw new \Exception('product 不是 WC_Product');
}

$wrapper_class  = $args['wrapper_class'];
$args['href']   = '#';
$args['class'] .= ' product_type_simple add_to_cart_button ajax_add_to_cart cursor-pointer ';
$args['attr']  .= sprintf(
	' data-product_id="%1$s" data-quantity="%2$s" data-product_sku="%3$s" aria-label="Add to cart: “%4$s”" aria-describedby="" rel="nofollow" ',
	$product->get_id(),
	$args['qty'],
	$product->get_sku(),
	$product->get_name()
);

unset($args['product'], $args['wrapper_class'], $args['qty']);

$button_html = Plugin::get(
	'button',
	$args,
	false
);

printf(
/*html*/'<div class="pc-add-to-cart %1$s">%2$s</div>',
$wrapper_class,
$button_html
);
