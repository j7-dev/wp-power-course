<?php
// TODO

$props = $args;

$default_props = [
	'children' => '加入購物車',
	'icon'     => '',
	'href'     => '',
];

$props = \array_merge( $default_props, $props );

$html  = '<a href="?add-to-cart=391" data-quantity="1" class="group product_type_simple add_to_cart_button ajax_add_to_cart" data-product_id="391" data-product_sku="" aria-describedby="" rel="nofollow">';
$html .= self::base( $props );
$html .= '</a>';

echo $html;
