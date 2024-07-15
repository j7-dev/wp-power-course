<?php
/**
 * Course Product > body
 */

use J7\PowerCourse\Templates\Templates;
use J7\PowerCourse\Utils\Course as CourseUtils;

$default_args = [
	'product' => $GLOBALS['product'] ?? null,
];

/**
 * @var array $args
 * @phpstan-ignore-next-line
 */
$args = wp_parse_args( $args, $default_args );

[
	'product' => $product,
] = $args;

if ( ! ( $product instanceof \WC_Product ) ) {
	throw new \Exception( 'product 不是 WC_Product' );
}

?>
<div class="flex-1 px-4 md:px-0">

	<div class="mb-12">
		<?php
		Templates::get(
			'typography/title',
			[
				'value' => '課程資訊',
			]
		);

		$course_schedule_in_timestamp = $product->get_meta( 'course_schedule' );
		$course_schedule              = $course_schedule_in_timestamp ? \wp_date(
			'Y/m/d H:i',
			$course_schedule_in_timestamp
		) : '未設定';
		$course_hour                  = (int) $product->get_meta( 'course_hour' );
		$course_minute                = (int) $product->get_meta( 'course_minute' );

		$count_all_chapters = (int) count( CourseUtils::get_sub_chapters( $product, true ) );


		$total_sales  = ( $product->get_total_sales() ) + ( (int) $product->get_meta( 'extra_student_count' ) );
		$limit_labels = CourseUtils::get_limit_label_by_product( $product );

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
					'value' =>"{$limit_labels['type']} {$limit_labels['value']}",
				],
				[
					'icon'  => 'team',
					'label' => '課程學員',
					'value' => "{$total_sales} 人",
				],
			],
		);

		// echo '<div class="mt-8 flex items-end gap-4">';
		// Templates::get(
		// 'countdown',
		// [
		// 'type'       => 'lg',
		// 'item_class' => '',
		// ]
		// );
		// echo '<h2>後課程即將停賣</h2>';
		// echo '</div>';

		?>
	</div>
	<!-- Tabs -->
	<?php Templates::get( 'course-product/tabs', null, true, true ); ?>

	<!-- Footer -->
	<?php Templates::get( 'course-product/footer', null, true, true ); ?>
</div>
