<?php
/**
 * Price component
 */

use J7\PowerCourse\Utils\Base;

$default_args = [
	'product' => null,
	'size'    => 'large',
];

/**
 * @var array $args
 * @phpstan-ignore-next-line
 */
$args = wp_parse_args( $args, $default_args );

[
	'product' => $product,
	'size'    => $size,
] = $args;

if ( ! ( $product instanceof \WC_Product ) ) {
	return;
}

$price_html = Base::get_price_html( $product );
echo '<div class="pc-price-html">';
echo $price_html;
echo '</div>';
