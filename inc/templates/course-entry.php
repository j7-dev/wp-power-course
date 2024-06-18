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


use J7\PowerCourse\Templates\Components\Course;
use J7\PowerCourse\Templates\Components\Title;
use J7\PowerCourse\Templates\Components\Card;
use J7\PowerBundleProduct\BundleProduct;
use J7\PowerCourse\Utils\Base;

global $product;


get_header(); ?>
<!-- <script src="https://cdn.tailwindcss.com"></script> -->
<div class="leading-7 text-gray-800 w-full max-w-[1138px] mx-auto  px-0 md:px-6 text-base font-normal pt-[5rem] pb-[10rem]">

	<!-- Header -->
	<?php require_once __DIR__ . '/header/index.php'; ?>

	<div class="flex flex-col md:flex-row gap-8">
		<!-- Body -->
		<div class="flex-1">

			<div class="mb-12">
				<?php
				echo Title::title(
					array(
						'value' => '課程資訊',
					)
				);
				?>
				<?php
				echo Course::info(
					array(
						'items' => array(
							array(
								'icon'  => 'calendar',
								'label' => '開課時間',
								'value' => '2022/08/31 16:00',
							),
							array(
								'icon'  => 'clock',
								'label' => '預計時長',
								'value' => '15 小時 8 分',
							),
							array(
								'icon'  => 'list',
								'label' => '預計單元',
								'value' => '39個',
							),
							array(
								'icon'  => 'eye',
								'label' => '觀看時間',
								'value' => '無限制',
							),
							array(
								'icon'  => 'team',
								'label' => '課程學員',
								'value' => '1214 人',
							),
						),
					)
				);
				?>
			</div>
			<!-- Tabs -->
			<?php require_once __DIR__ . '/tabs/index.php'; ?>

			<!-- Footer -->
			<?php require_once __DIR__ . '/footer/index.php'; ?>
		</div>

		<!-- Sider -->
		<div class="w-[20rem] flex flex-col gap-6">

			<?php
			echo Card::single_product(
				array(
					'product' => $product,
				)
			);
			?>

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
	echo Card::bundle_product(
		array(
			'bundle_product' => $bundle_product,
			'title'          => $bundle_product->get_name(),
		)
	);

}
?>



		</div>

	</div>

</div>
<?php
get_footer();

/* Omit closing PHP tag at the end of PHP files to avoid "headers already sent" issues. */
