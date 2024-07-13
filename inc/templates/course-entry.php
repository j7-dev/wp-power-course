<?php
/**
 * The Template for displaying all single products
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://woo.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

\add_filter(
	'body_class',
	function ( $classes ) {
		$classes[] = 'bg-gray-50';
		return $classes;
	}
);


use J7\PowerCourse\Templates\Templates;

global $product;

get_header(); ?>
	<div class="leading-7 text-gray-800 w-full max-w-[1138px] mx-auto  px-0 md:px-6 text-base font-normal pt-[5rem] pb-[10rem]">

		<!-- Header -->
		<?php Templates::get( 'course-product/header', null, true, true ); ?>

		<div class="flex flex-col md:flex-row gap-8">
			<!-- Body -->
			<?php Templates::get( 'course-product/body', null, true, true ); ?>

			<!-- Sider -->
			<?php Templates::get( 'course-product/sider', null, true, true ); ?>
		</div>

	</div>
<?php
get_footer();
