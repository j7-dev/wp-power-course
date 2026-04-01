<?php
/**
 * 章節被鎖定（線性觀看模式）
 */

use J7\PowerCourse\Plugin;
use J7\PowerCourse\Resources\Chapter\Utils\Utils as ChapterUtils;

$default_args = [
	'product' => $GLOBALS['course'] ?? null,
	'chapter' => $GLOBALS['chapter'] ?? null,
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
	return;
}

// 找到學員應該觀看的最後一個未鎖定章節（即目前應完成的章節）
$chapter_ids = ChapterUtils::get_flatten_post_ids( $product->get_id() );
$last_unlocked_url = '';
$last_unlocked_title = '';

if ( ! empty( $chapter_ids ) ) {
	$user_id = \get_current_user_id();
	foreach ( $chapter_ids as $cid ) {
		if ( ! ChapterUtils::is_chapter_locked( (int) $cid, $user_id ) ) {
			// 持續更新，最後會停在最後一個未鎖定的章節
			$last_unlocked_url   = \get_permalink( $cid ) ?: '';
			$last_unlocked_title = \get_the_title( $cid );
		} else {
			// 遇到第一個鎖定章節就停止
			break;
		}
	}
}

$buttons = '';
if ( $last_unlocked_url ) {
	$buttons = sprintf(
		/*html*/'<a href="%1$s" class="pc-btn pc-btn-sm pc-btn-primary text-white">前往「%2$s」繼續學習</a>',
		\esc_url( $last_unlocked_url ),
		\esc_html( $last_unlocked_title )
	);
}

echo '<div class="leading-7 text-base-content w-full mx-auto  px-0 md:px-6 text-base font-normal pt-0 lg:pt-[5rem] pb-[10rem]" style="max-width: 1200px;">';
Plugin::load_template(
	'alert',
	[
		'type'    => 'warning',
		'message' => '此章節目前已鎖定，請先完成前面的章節才能觀看',
		'buttons' => $buttons,
	]
);
Plugin::load_template(
	'course-product/header',
	[
		'show_link' => true,
	]
	);
echo '</div>';
