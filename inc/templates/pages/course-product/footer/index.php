<?php
/**
 * Footer for course product
 */

use J7\PowerCourse\Templates\Templates;

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

$product_id  = $product->get_id();
$teacher_ids = \get_post_meta( $product_id, 'teacher_ids', false );
if ( ! is_array( $teacher_ids ) ) {
	$teacher_ids = [];
}

/**
 * @var array{type: string, id: string, meta: ?array} $trial_video
 */
$trial_video = \get_post_meta( $product_id, 'trial_video', true );

if ( ! ! $trial_video ) {
	printf(
	/*html*/'
<div class="mb-12">
	%1$s
	<div class="max-w-[30rem]">
		%2$s
	</div>
</div>
',
	Templates::get(
	'typography/title',
	[
		'value' => '課程試看',
	],
	false
	),
	Templates::get(
	'video',
	[
		'video_info' => $trial_video,
	],
	false
	)
	);
}

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
	Templates::get(
		'user/about',
		[
			'user' => $teacher,
		]
		);
	echo '</div>';
}
