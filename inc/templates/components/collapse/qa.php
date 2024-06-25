<?php

$product = $args;

if ( ! ( $product instanceof \WC_Product ) ) {
	throw new \Exception( 'Invalid Product' );
}

$product_id = $product->get_id();

$qa_list = \get_post_meta( $product_id, 'qa_list', true );

if ( ! is_array( $qa_list ) ) {
	$qa_list = [];
}
foreach ( $qa_list as $qa ) :
	?>
	<div class="pc-collapse pc-collapse-arrow rounded-none mb-1">
		<input type="checkbox" />
		<div class="pc-collapse-title text-sm font-semibold bg-gray-200 py-3 flex items-center justify-between">
			<span><?php echo $qa['question']; ?></span>
		</div>
		<div class="pc-collapse-content bg-gray-100 p-0">
			<div class="text-xs border-t-0 border-x-0 border-b border-gray-200 border-solid py-6 flex px-8 leading-8">
	<?php echo \wpautop( $qa['answer'] ); ?>
			</div>
		</div>
	</div>
	<?php
endforeach;
