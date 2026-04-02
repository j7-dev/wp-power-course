<?php
/**
 * 章節被線性觀看鎖定時的提示頁面
 *
 * 當學員未按照順序完成前置章節時，顯示此鎖定提示頁面。
 * 告知學員需要先完成哪個章節，並提供前一章節的連結。
 */

use J7\PowerCourse\Plugin;
use J7\PowerCourse\Resources\Chapter\Utils\Utils as ChapterUtils;

$default_args = [
	'product'           => $GLOBALS['course'] ?? null,
	'chapter'           => $GLOBALS['chapter'] ?? null,
	'required_title'    => '',
	'prev_chapter_id'   => null,
];

/**
 * @var array $args
 * @phpstan-ignore-next-line
 */
$args = wp_parse_args( $args, $default_args );

[
	'product'         => $product,
	'chapter'         => $chapter,
	'required_title'  => $required_title,
	'prev_chapter_id' => $prev_chapter_id,
] = $args;

if ( ! ( $product instanceof \WC_Product ) ) {
	return;
}

/** @var \WP_Post|null $chapter */
$chapter_title   = $chapter ? $chapter->post_title : '';
$prev_chapter_url = $prev_chapter_id ? ( \get_permalink( (int) $prev_chapter_id ) ?: '' ) : '';

$message = $required_title
	? sprintf( '請先完成「%s」章節才能觀看此內容', \esc_html( $required_title ) )
	: '請先完成前置章節才能觀看此內容';

echo '<div class="leading-7 text-base-content w-full mx-auto px-0 md:px-6 text-base font-normal pt-0 lg:pt-[5rem] pb-[10rem]" style="max-width: 1200px;">';

Plugin::load_template(
	'alert',
	[
		'type'    => 'warning',
		'message' => $message,
	]
);

if ( $prev_chapter_url && $required_title ) {
	printf(
		/*html*/'<div class="mt-4 text-center"><a href="%1$s" class="pc-btn pc-btn-primary text-white">前往學習「%2$s」</a></div>',
		\esc_url( $prev_chapter_url ),
		\esc_html( $required_title )
	);
}

Plugin::load_template(
	'course-product/header',
	[
		'show_link' => true,
	]
);

echo '</div>';
