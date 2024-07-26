<?php
/**
 * Body of the classroom page.
 */

use J7\PowerCourse\Templates\Templates;

$default_args = [
	'product' => $GLOBALS['product'] ?? null,
	'chapter' => $GLOBALS['chapter'],
];

/**
 * @var array $args
 * @phpstan-ignore-next-line
 */
$args = wp_parse_args( $args, $default_args );

[
	'product' => $product,
	'chapter' => $chapter,
] = $args;

if ( ! ( $product instanceof \WC_Product ) ) {
	throw new \Exception( 'product 不是 WC_Product' );
}

$chapter_id = $chapter->ID;

/**
 * @var array{type: string, id: string, meta: ?array} $video_info
 */
$video_info = get_post_meta( $chapter_id, 'chapter_video', true );


$course_tabs = [
	[
		'key'     => '0',
		'label'   => '章節',
		'content' => Templates::get( 'classroom/chapters', null, false ),
	],

	/*
	[
		'key'     => '1',
		'label'   => '討論',
		'content' => '🚧 施工中... 🚧',
	],
	[
		'key'     => '2',
		'label'   => '教材',
		'content' => '🚧 施工中... 🚧',
	],
	[
		'key'     => '3',
		'label'   => '公告',
		'content' => '🚧 施工中... 🚧',
	],
	[
		'key'     => '4',
		'label'   => '評價',
		'content' => '🚧 施工中... 🚧',
	],
	*/

];

echo '<div class="w-full bg-white pt-[3.25rem] lg:pt-16">';

Templates::get( 'classroom/header' );

echo '<div class="z-[15] sticky lg:relative top-0">';

Templates::get(
	'video',
	[
		'video_info' => $video_info,
		'class'      => 'rounded-none',
	]
);
echo '<div class="bg-gray-100 px-4 lg:px-12 py-4">';
Templates::get( 'progress' );
echo '</div>';

Templates::get(
	'tabs/nav',
	[
		'course_tabs'        => $course_tabs,
		'default_active_key' => '0',
	]
	);

echo '</div>';





Templates::get(
'tabs/content',
[
	'course_tabs'        => $course_tabs,
	'default_active_key' => '0',
]
);

echo '</div>';

printf(
/*html*/'
<dialog id="finish-chapter__dialog" class="pc-modal">
	<div class="pc-modal-box">
		<h3 id="finish-chapter__dialog__title" class="text-lg font-bold"></h3>
		<p id="finish-chapter__dialog__message" class="py-4"></p>
		<div class="pc-modal-action">
			<form method="dialog">
				<button class="pc-btn pc-btn-sm pc-btn-primary text-white px-4">關閉</button>
			</form>
		</div>
	</div>
	<form method="dialog" class="pc-modal-backdrop">
		<button class="opacity-0">close</button>
	</form>
</dialog>
'
);
