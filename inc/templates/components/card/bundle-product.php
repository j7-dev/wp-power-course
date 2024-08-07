<?php
/**
 * Bundle product card
 */

use J7\PowerBundleProduct\BundleProduct;
use J7\PowerCourse\Templates\Templates;

$default_args = [
	'bundle_product' => null, // BundleProduct
];

/**
 * @var array $args
 * @phpstan-ignore-next-line
 */
$args = wp_parse_args( $args, $default_args );

[
	'bundle_product' => $bundle_product,
] = $args;

if ( ! ( $bundle_product instanceof BundleProduct ) ) {
	throw new \Exception( 'product 不是 BundleProduct' );
}


$product_ids = $bundle_product->get_product_ids();

$bundle_title = $bundle_product->get_name();

$purchase_note = \wpautop( $bundle_product->get_purchase_note() );

?>
<div class="w-full bg-white shadow-lg rounded p-6">
	<p class="text-xs text-center mb-1 text-red-400">合購優惠</p>
	<h6 class="text-base font-semibold text-center">
		<?php
		echo $bundle_title;
		?>
	</h6>

	<?php
	Templates::get( 'divider' );
	?>

	<div class="mb-6 text-sm">
		<?php
		echo $purchase_note;
		?>
	</div>


	<?php
	foreach ( $product_ids as $product_id ) :
		$product = \wc_get_product( $product_id );
		?>
		<div>
		<?php
		Templates::get(
			'course-product/list',
			[
				'product' => $product,
			]
			);
		?>
		</div>
		<?php
		Templates::get( 'divider' );
		?>

		<?php
	endforeach;
	?>


	<div class="flex gap-3 justify-between items-end">
		<?php
		Templates::get(
			'price',
			[
				'product' => $bundle_product,
				'size'    => 'small',
			]
		);
		?>

		<?php
		Templates::get(
			'button/add-to-cart',
			[
				'product'       => $bundle_product,
				'type'          => 'primary',
				'class'         => 'px-6 text-white ',
				'wrapper_class' => '[&_a.wc-forward]:tw-hidden',
			]
		);
		?>
	</div>
</div>
