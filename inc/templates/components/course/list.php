<?php
// TODO

use J7\PowerCourse\Utils\Base;

$product = $args;

if ( ! ( $product instanceof \WC_Product ) ) {
	throw new \Exception( 'product 不是 WC_Product' );
}

$regular_price = $product->get_regular_price();

$regular_price_html = $regular_price ? '<del class="block text-xs text-gray-600">NT$' . $regular_price . '</del>' : '';

$product_name = $product->get_name();

$product_image = \wp_get_attachment_image_src( \get_post_thumbnail_id( $product->get_id() ), 'single-post-thumbnail' );

if ( ! $product_image ) {
	$product_image_url = Base::DEFAULT_IMAGE;
} else {
	$product_image_url = $product_image[0];
}

?>
<div class="flex gap-5">
	<div class="group w-[35%] aspect-video rounded overflow-hidden">
		<img class="w-full h-full object-cover group-hover:scale-125 transition duration-300 ease-in-out" src="<?php echo $product_image_url; ?>">
	</div>
	<div class="w-[65%]">
		<h6 class="text-sm font-semibold mb-1"><?php echo $product_name; ?></h6>
		<del class="block text-xs text-gray-600">NT$12,000</del>
	</div>
</div>
