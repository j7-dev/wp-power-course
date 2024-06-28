<?php

use J7\PowerCourse\Templates\Templates;

$product     = $args;
$product_id  = $product->get_id();
$teacher_ids = \get_post_meta( $product_id, 'teacher_ids', false );
if ( ! is_array( $teacher_ids ) ) {
	$teacher_ids = [];
}

$trial_video = \get_post_meta( $product_id, 'trial_video', true );

?>
<?php
if ( ! ! $trial_video ) :
	?>
	<div class="mb-12">
	<?php
	Templates::get(
		'typography/title',
		[
			'value' => '課程試看',
		]
	);
	?>

		<div class="max-w-[20rem]">
	<?php
	// TODO 清除預設值
	$library_id = \get_option( 'library_id', '244459' );

	Templates::get(
		'bunny/video',
		[
			'library_id' => $library_id,
			'video_id'   => $trial_video,
		]
	);
	?>
		</div>
	</div>
	<?php
endif;
?>
<?php
if ( ! ! $teacher_ids ) {
	Templates::get(
		'typography/title',
		[
			'value' => '關於講師',
		]
	);
}

foreach ( $teacher_ids as $teacher_id ) {
	$teacher = \get_user_by( 'id', $teacher_id );
	echo '<div class="mb-12">';
	Templates::get( 'user/about', $teacher );
	echo '</div>';
}
