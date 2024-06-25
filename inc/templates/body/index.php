<?php
use J7\PowerCourse\Templates\Templates;
use J7\PowerCourse\Templates\Components\Title;
use J7\PowerCourse\Templates\Components\Course;

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

				Templates::get(
					'course/info',
					[
						[
							'icon'  => 'calendar',
							'label' => '開課時間',
							'value' => '2022/08/31 16:00',
						],
						[
							'icon'  => 'clock',
							'label' => '預計時長',
							'value' => '15 小時 8 分',
						],
						[
							'icon'  => 'list',
							'label' => '預計單元',
							'value' => '39個',
						],
						[
							'icon'  => 'eye',
							'label' => '觀看時間',
							'value' => '無限制',
						],
						[
							'icon'  => 'team',
							'label' => '課程學員',
							'value' => '1214 人',
						],
					],
				);

				?>
			</div>
			<!-- Tabs -->
			<?php Templates::get( 'body/tabs', $product, true ); ?>

			<!-- Footer -->
			<?php Templates::get( 'body/footer', $product, true ); ?>
		</div>
