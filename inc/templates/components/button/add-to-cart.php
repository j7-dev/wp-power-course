<?php

use J7\PowerCourse\Templates\Templates;

$default_args = [
	'product'       => null,
	'class'         => '',
	'wrapper_class' => '[&_.added]:hidden',
	'label'         => '加入購物車',
];

/**
 * @var array $args
 */
$merged_args = wp_parse_args( $args, $default_args );


[
	'product'       => $product,
	'class'         => $class,
	'wrapper_class' => $wrapper_class,
	'label'         => $label
] = $merged_args;

$sku  = $product->get_sku();
$name = $product->get_name();

if ( 'icon' === $label ) :
	$icon_html = Templates::get(
		'icon/shopping-bag',
		[
			'class' => 'h-4 w-4',

		],
		false
	);
	printf(
		'<div class="%6$s"><a href="#" data-quantity="1" class="product_type_simple add_to_cart_button ajax_add_to_cart %2$s" data-product_id="%1$s" data-product_sku="%3$s" aria-label="Add to cart: “%4$s”" aria-describedby="" rel="nofollow">%5$s</a></div>',
		$product->get_id(),
		$class,
		$sku,
		$name,
		$icon_html,
		$wrapper_class
	);
else :
	printf(
		'<div class="%6$s"><a href="#" data-quantity="1" class="text-nowrap button product_type_simple add_to_cart_button ajax_add_to_cart %2$s" data-product_id="%1$s" data-product_sku="%3$s" aria-label="Add to cart: %4$s" aria-describedby="" rel="nofollow">%5$s</a></div>',
		$product->get_id(),
		$class,
		$sku,
		$name,
		$label,
		$wrapper_class
	);
endif;
