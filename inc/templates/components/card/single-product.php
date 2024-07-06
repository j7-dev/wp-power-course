<?php

use J7\PowerCourse\Templates\Templates;

/**
 * @var WC_Product $args
 */
$product = $args;
if ( ! ( $product instanceof \WC_Product ) ) {
	throw new \Exception( 'product 不是 WC_Product' );
}

$purchase_note = \wpautop( $product->get_purchase_note() );

?>
<div class="w-full bg-white shadow-lg rounded p-6">
	<h6 class="text-base font-semibold text-center">購買單堂課</h6>
	<?php
	Templates::get( 'divider/base' );
	?>

	<div class="my-8">
		<?php
		Templates::get(
			'price/base',
			[
				'product' => $product,
			]
		);
		?>
	</div>

	<div class="mb-6 text-sm">
		<?php
		echo $purchase_note;
		?>
	</div>

	<div class="flex gap-3">
		<?php
		$checkout_url = \wc_get_checkout_url();
		$url          = \add_query_arg(
			[
				'add-to-cart' => $product->get_id(),
			],
			$checkout_url
		);
		Templates::get(
			'button',
			[
				'type'     => 'primary',
				'children' => '立即購買',
				'class'    => 'text-white flex-1',
				'href'     => $url,
			]
		);

		Templates::get(
			'button/add-to-cart',
			[
				'product'       => $product,
				'children'      => '',
				'type'          => 'primary',
				'outline'       => true,
				'icon'          => 'shopping-bag',
				'shape'         => 'square',
				'wrapper_class' => '[&_a.wc-forward]:hidden',
				'class'         => '',
			]
		);
		?>

	</div>
</div>
