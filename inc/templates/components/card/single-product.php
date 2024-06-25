<?php

use J7\PowerCourse\Templates\Templates;

$product = $args;
if ( ! ( $product instanceof \WC_Product ) ) {
	throw new \Exception( 'product 不是 WC_Product' );
}

$purchase_note = \wpautop( $product->get_purchase_note() );

?>
<div class="w-full bg-white shadow-lg rounded p-6">
	<h6 class="text-base font-semibold text-center">購買單堂課</h6>
	<?php Templates::get( 'divider/base' ); ?>

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
		<?php echo $purchase_note; ?>
	</div>

	<div class="flex gap-3">
		<?php
		Templates::get(
			'button/base',
			[
				'children' => '立即購買',
				'class'    => 'w-full',
			]
		);

		Templates::get(
			'button/base',
			[
				'children' => '',
				'type'     => 'outline',
				'icon'     => 'shopping_bag',
			]
		);
		?>
	</div>
</div>
