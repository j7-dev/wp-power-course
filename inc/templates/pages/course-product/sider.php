<?php

use J7\PowerBundleProduct\BundleProduct;
use J7\PowerCourse\Templates\Templates;
use J7\PowerCourse\Utils\Base;

/**
 * @var WC_Product $args
 */
$product = $args;
?>

<div class="w-[20rem] flex flex-col gap-6">

	<?php Templates::get( 'card/single-product', $product ); ?>
	<?php
	$bundle_ids = Base::get_bundle_ids_by_product( $product->get_id() );

	foreach ( $bundle_ids as $bundle_id ) {
		$bundle_product = \wc_get_product( $bundle_id );
		if ( ! $bundle_product ) {
			continue;
		}

		$bundle_product = new BundleProduct( $bundle_product );
		if ( 'publish' !== $bundle_product->get_status() ) {
			continue;
		}
		Templates::get( 'card/bundle-product', $bundle_product );
	}
	?>


</div>
