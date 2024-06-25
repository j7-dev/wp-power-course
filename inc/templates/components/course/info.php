<?php

use J7\PowerCourse\Templates\Templates;

$items = $args;

if ( ! is_array( $items ) ) {
	echo 'items 必須是陣列';
	$items = [];
}

?>
<div class="w-full grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-6">
	<?php foreach ( $items as $index => $item ) : ?>
		<div class="flex items-center gap-3">
			<div class="bg-blue-500 rounded-xl h-8 w-8 flex items-center justify-center">
		<?php
		Templates::safe_get(
			'icon/' . $item['icon'],
			[
				'class' => 'h-4 w-4',
				'color' => '#ffffff',
			]
		);
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
