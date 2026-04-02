<?php
/**
 * Classroom > locked
 *
 * 章節被線性觀看模式鎖定時的提示頁面。
 * 不載入影片、不載入 the_content。
 */

use J7\PowerCourse\Plugin;
use J7\PowerCourse\FrontEnd\MyAccount;

$default_args = [
	'product'      => $GLOBALS['course'] ?? null,
	'chapter'      => $GLOBALS['chapter'] ?? null,
	'prev_chapter' => null,
];

/**
 * @var array $args
 * @phpstan-ignore-next-line
 */
$args = \wp_parse_args( $args, $default_args );

[
	'product'      => $product,
	'chapter'      => $chapter,
	'prev_chapter' => $prev_chapter,
] = $args;

if ( ! ( $product instanceof \WC_Product ) ) {
	return;
}

if ( ! ( $chapter instanceof \WP_Post ) ) {
	return;
}

/** @var \WP_Post $chapter */
$chapter_id = $chapter->ID;

$prev_chapter_title = '';
$prev_chapter_url   = '';
if ( $prev_chapter instanceof \WP_Post ) {
	$prev_chapter_title = \esc_html( $prev_chapter->post_title );
	$prev_chapter_url   = \get_permalink( $prev_chapter->ID ) ?: '';
}

echo '<div id="pc-classroom-body" class="w-full bg-base-100 lg:pl-[25rem]">';

Plugin::load_template(
	'classroom/header',
	[
		'product' => $product,
		'chapter' => $chapter,
	]
);

echo /*html*/'
<div class="h-10 w-full bg-base-200 flex lg:tw-hidden items-center justify-between px-4 sticky top-[52px] left-0 z-30" style="border-bottom: 1px solid var(--fallback-bc,oklch(var(--bc)/.1))">
	<label for="pc-classroom-drawer" class="flex items-center gap-x-2 text-sm cursor-pointer">
		<svg class="size-4 stroke-base-content/30" viewBox="0 0 48 48" fill="none">
			<path d="M42 9H6" stroke="#78716c" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"></path>
			<path d="M34 19H6" stroke="#78716c" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"></path>
			<path d="M42 29H6" stroke="#78716c" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"></path>
			<path d="M34 39H6" stroke="#78716c" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"></path>
		</svg>
		選單
	</label>
</div>
';

printf(
	/*html*/'
<div class="flex flex-col items-center justify-center min-h-[60vh] px-6 py-12 text-center">
	<div class="mb-6">
		%1$s
	</div>
	<h2 class="text-2xl font-bold mb-4">此章節尚未解鎖</h2>
	<p class="text-base text-base-content/70 mb-8 max-w-md">請先完成「%2$s」章節，即可解鎖此章節</p>
	%3$s
</div>
',
	Plugin::load_template(
		'icon/lock',
		[
			'class' => 'size-20',
			'color' => '#9ca3af',
		],
		false
	),
	$prev_chapter_title,
	$prev_chapter_url
		? sprintf(
			/*html*/'<a href="%1$s" class="pc-btn pc-btn-primary text-white px-8">前往上一章節</a>',
			\esc_url( $prev_chapter_url )
		)
		: ''
);

echo '</div>';
