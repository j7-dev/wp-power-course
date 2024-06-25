<?php
use J7\PowerCourse\Templates\Templates;


$product     = $args;
$product_id  = $product->get_id();
$teacher_ids = \get_post_meta( $product_id, 'teacher_ids', false );
if ( ! is_array( $teacher_ids ) ) {
	$teacher_ids = [];
}

?>
<div class="flex gap-6 flex-col md:flex-row mb-20">
		<div class="w-full md:w-[55%]">
		<?php Templates::get( 'course/video' ); ?>
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
				Templates::get(
					'rate/base',
					[
						'show_before' => true,
						'value'       => 3.7,
						'total'       => 100,
					]
				);
				?>
		</div>
</div>
