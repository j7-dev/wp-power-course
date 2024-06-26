<?php

use J7\PowerCourse\Templates\Templates;
use J7\PowerCourse\Utils\Course as CourseUtils;

/**
 * @var WC_Product $args
 */
$product = $args;
?>
<div class="flex-1">

	<div class="mb-12">
		<?php
		Templates::get(
			'typography/title',
			[
				'value' => '課程資訊',
			]
		);

		$course_schedule_in_timestamp = $product->get_meta( 'course_schedule' );
		$course_schedule              = $course_schedule_in_timestamp ? \date(
			'Y/m/d H:i',
			$course_schedule_in_timestamp
		) : '未設定';
		$course_hour                  = (int) $product->get_meta( 'course_hour' );
		$course_minute                = (int) $product->get_meta( 'course_minute' );

		$count_all_chapters = (int) count( CourseUtils::get_sub_chapters( $product, true ) );


		$total_sales = ( $product->get_total_sales() ) + ( (int) $product->get_meta( 'extra_student_count' ) );
		$limit_label = CourseUtils::get_limit_label_by_product( $product );

		Templates::get(
			'course-product/info',
			[
				[
					'icon'  => 'calendar',
					'label' => '開課時間',
					'value' => $course_schedule,
				],
				[
					'icon'  => 'clock',
					'label' => '預計時長',
					'value' => "{$course_hour} 小時 {$course_minute} 分",
				],
				[
					'icon'  => 'list',
					'label' => '預計單元',
					'value' => "{$count_all_chapters} 個",
				],
				[
					'icon'  => 'eye',
					'label' => '觀看時間',
					'value' => $limit_label,
				],
				[
					'icon'  => 'team',
					'label' => '課程學員',
					'value' => "{$total_sales} 人",
				],
			],
		);

		?>
	</div>
	<!-- Tabs -->
	<?php Templates::get( 'course-product/tabs', $product, true ); ?>

	<!-- Footer -->
	<?php Templates::get( 'course-product/footer', $product, true ); ?>
</div>
