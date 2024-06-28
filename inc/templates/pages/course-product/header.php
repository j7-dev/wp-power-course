<?php

use J7\PowerCourse\Templates\Templates;

/**
 * @var WC_Product $product
 * @var WC_Product $args
 */
$product      = $args;
$product_id   = $product->get_id();
$product_name = $product->get_name();
$teacher_ids  = \get_post_meta( $product_id, 'teacher_ids', false );
if ( ! is_array( $teacher_ids ) ) {
	$teacher_ids = [];
}

?>
<div class="flex gap-6 flex-col md:flex-row mb-20">
	<div class="w-full md:w-[55%] px-8 md:px-0">
		<?php
		// TODO 清除預設值 DELETE
		$library_id = \get_option( 'library_id', '244459' );

		$feature_video = \get_post_meta( $product_id, 'feature_video', true );
		$image_id      = $product->get_image_id();
		$image_url     = \wp_get_attachment_image_url( $image_id, 'full' );

		if ( ! ! $feature_video ) {
			Templates::get(
				'bunny/video',
				[
					'library_id' => $library_id,
					'video_id'   => $feature_video,
				]
			);
		} else {
			printf(
				'<div class="group w-full rounded-2xl aspect-video overflow-hidden">
			<img src="%s" class="h-full w-full object-cover group-hover:scale-125 duration-300 transition ease-in-out" alt="%2$s" loading="lazy">
			</div>',
				$image_url,
				$product_name
			);
		}
		?>

	</div>

	<div class="w-full md:w-[45%]">
		<div class="mb-2 flex gap-x-4 gap-y-2 flex-wrap">
			<?php
			foreach ( $teacher_ids as $teacher_id ) {
				$teacher = \get_user_by( 'id', $teacher_id );
				Templates::get( 'user/base', $teacher );
			}
			?>
		</div>

		<h1 class="mb-[10px] text-xl md:text-4xl md:leading-[3rem] font-semibold">
			<?php
			echo $product->get_name();
			?>
		</h1>

		<div class="text-gray-400">
			<?php
			echo \wpautop( $product->get_short_description() );
			?>
		</div>


		<?php
		$rating       = $product->get_average_rating();
		$review_count = $product->get_review_count();
		Templates::get(
			'rate/base',
			[
				'show_before' => true,
				'value'       => $rating,
				'total'       => $review_count,
			]
		);
		?>
	</div>
</div>