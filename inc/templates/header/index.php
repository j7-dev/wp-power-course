<?php
use J7\PowerCourse\Templates\Components\Icon;
use J7\PowerCourse\Templates\Components\Button;
use J7\PowerCourse\Templates\Components\Rate;
use J7\PowerCourse\Templates\Components\Course;
use J7\PowerCourse\Templates\Components\User;

$product_id  = $product->get_id();
$teacher_ids = \get_post_meta( $product_id, 'teacher_ids', false );
if ( ! is_array( $teacher_ids ) ) {
	$teacher_ids = array();
}

?>
<div class="flex gap-6 flex-col md:flex-row">
		<div class="w-full md:w-[55%]">
			<?php echo Course::video(); ?>
		</div>

		<div class="w-full md:w-[45%]">
			<div class="mb-2 flex gap-x-4 gap-y-2 flex-wrap">
			<?php
			foreach ( $teacher_ids as $teacher_id ) :
				$teacher = \get_user_by( 'id', $teacher_id );
				?>
				<?php
				echo User::Base(
					array(
						'user' => $teacher,
					)
				);
				?>
			<?php endforeach; ?>
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

			<div class="flex h-8 items-center gap-2">
				<?php
				echo Icon::fire();
				?>
				熱門課程
			</div>


			<div class="flex h-8 items-center gap-2">
				<?php echo Icon::shopping_bag(); ?>
				2,310 人已購買
			</div>

			<?php echo Icon::clock(); ?>

			<?php
			echo Button::base(
				array(
					'children' => '立即購買',
					'icon'     => 'fire',
				)
			);
			?>

			<div class="flex h-8 items-center gap-2">
				<?php
				echo Rate::rate(
					array(
						'value' => 3.7,
						'total' => 100,
					)
				);
				?>
			</div>

			<div class="flex h-8 items-center gap-2">
				<?php
				echo Rate::base(
					array(
						'show_before' => true,
						'value'       => 3.7,
						'total'       => 100,
					)
				);
				?>
			</div>




		</div>
</div>
