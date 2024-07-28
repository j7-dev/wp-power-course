<?php
/**
 * Review
 * TODO 前端AJAX換頁
 */

use J7\PowerCourse\Templates\Templates;

$default_args = [
	'product' => $GLOBALS['product'] ?? null,
];

/**
 * @var array $args
 * @phpstan-ignore-next-line
 */
$args = wp_parse_args( $args, $default_args );

[
	'product' => $product,
] = $args;

$product_comments = \get_comments(
	[
		'post_id'      => $product->get_id(),
		'post_type'    => 'product',
		'hierarchical' => 'threaded',
	]
);

// Review 標題
$count = $product->get_review_count();
echo '<h2 class="text-base font-bold">';
if ( $count && wc_review_ratings_enabled() ) {
	/* translators: 1: reviews count 2: product name */
	$reviews_title = sprintf( esc_html( _n( '%1$s review for %2$s', '%1$s reviews for %2$s', $count, 'woocommerce' ) ), esc_html( $count ), '<span>' . get_the_title() . '</span>' );
	echo apply_filters( 'woocommerce_reviews_title', $reviews_title, $count, $product ); // WPCS: XSS ok.
} else {
	esc_html_e( 'Reviews', 'woocommerce' );
}
echo '</h2>';

// Review List
$show_review_list = $product->get_meta( 'show_review_list' ) === 'yes';
if ( $show_review_list && count( $product_comments ) ) {
	foreach ( $product_comments as $product_comment ) {
		Templates::get(
			'review/item',
			[
				'comment' => $product_comment,
			]
			);
	}
}


$reviews_allowed = $product->get_reviews_allowed();
$has_bought      = wc_customer_bought_product( '', get_current_user_id(), $product->get_id() );

if ($reviews_allowed && $has_bought) {
	include __DIR__ . '/comment_form.php';
}
