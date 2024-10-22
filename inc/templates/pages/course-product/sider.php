<?php
/**
 * Sidebar for course product
 */

use J7\PowerCourse\BundleProduct\BundleProduct;
use J7\PowerCourse\Templates\Templates;
use J7\PowerCourse\Utils\Course as CourseUtils;

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

if ( ! ( $product instanceof \WC_Product ) ) {
	throw new \Exception( 'product 不是 WC_Product' );
}
?>

<div class="w-full md:w-[20rem] px-4 md:px-0 flex flex-col gap-6">

	<?php Templates::get( 'card/single-product' ); ?>
	<?php
	$bundle_ids = CourseUtils::get_bundles_by_product( (int) $product->get_id(), true );
	foreach ( $bundle_ids as $bundle_id ) {
		$bundle_product = \wc_get_product( $bundle_id );
		if ( ! $bundle_product ) {
			continue;
		}

		$bundle_product = new BundleProduct( $bundle_product );
		if ( 'publish' !== $bundle_product->get_status() ) {
			continue;
		}
		Templates::get(
			'card/bundle-product',
			[
				'bundle_product' => $bundle_product,
			]
			);
	}
	?>


</div>
