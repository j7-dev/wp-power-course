<?php
use J7\PowerCourse\Templates\Components\Title;
use J7\PowerCourse\Templates\Components\Course;
use J7\PowerCourse\Templates\Components\User;

$product_id  = $product->get_id();
$teacher_ids = \get_post_meta( $product_id, 'teacher_ids', false );
if ( ! is_array( $teacher_ids ) ) {
	$teacher_ids = array();
}
?>

<div class="mb-12">
				<?php
				echo Title::title(
					array(
						'value' => '課程試看',
					)
				);
				?>
				<div class="max-w-[20rem]">
					<?php
					echo Course::video();
					?>
				</div>
			</div>

			<?php if ( ! ! $teacher_ids ) : ?>
				<?php
				echo Title::title(
					array(
						'value' => '關於講師',
					)
				);
				?>
				<?php endif; ?>

				<?php
				foreach ( $teacher_ids as $teacher_id ) :
					$teacher = \get_user_by( 'id', $teacher_id );
					?>
					<div class="mb-12">
					<?php
					echo User::about(
						array(
							'user' => $teacher,
						)
					);
					?>
					</div>
				<?php endforeach; ?>
