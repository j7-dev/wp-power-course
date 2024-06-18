<?php
/**
 * Button
 */

declare(strict_types=1);

namespace J7\PowerCourse\Templates\Components;

use J7\PowerCourse\Templates\Components\Icon;
use J7\PowerCourse\Utils\Base;

/**
 * Class FrontEnd
 */
abstract class Course {
	/**
	 * 課程資訊
	 *
	 * @param array|null $props props.
	 * @return string
	 */
	public static function info( ?array $props = array() ): string {

		$default_props = array(
			'items' => array(),
		);

		$props = array_merge( $default_props, $props );

		$items = $props['items'];

		if ( ! is_array( $items ) ) {
			echo 'items 必須是陣列';
			$items = array();
		}

		ob_start();
		?>
<div class="w-full grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-6">
		<?php foreach ( $items as $index => $item ) : ?>
	<div class="flex items-center gap-3">
	<div class="bg-blue-500 rounded-xl h-8 w-8 flex items-center justify-center">
			<?php
			$icon = call_user_func(
				array( Icon::class, $item['icon'] ),
				array(
					'class' => 'h-4 w-4',
					'color' => '#ffffff',
				)
			);
			echo $icon;
			?>
	</div>
	<div>
			<?php echo $item['label']; ?>
	</div>
	<div class="font-semibold">
			<?php echo $item['value']; ?>
	</div>
</div>
		<?php endforeach; ?>
</div>
		<?php

		$html = ob_get_clean();

		return $html;
	}

	/**
	 * Video
	 *
	 * @param array|null $props props.
	 * @return string
	 */
	public static function video( ?array $props = array() ): string {
		$html = '<div class="w-full rounded-2xl aspect-video bg-slate-400 animate-pulse"></div>';

		return $html;
	}

	/**
	 * List
	 *
	 * @param array|null $props props.
	 * @return string
	 */
	public static function list( ?array $props = array() ): string {

		$product = $props['product'] ?? null;

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

		ob_start();
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

		<?php
		$html = ob_get_clean();

		return $html;
	}
}
