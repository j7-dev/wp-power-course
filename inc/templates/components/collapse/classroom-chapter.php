<?php
/**
 * Classroom > Sider > Chapters
 */

use J7\PowerCourse\Resources\Chapter\CPT as ChapterCPT;
use J7\PowerCourse\Plugin;
use J7\PowerCourse\Resources\Chapter\AVLChapter;
$default_args = [
	'product' => $GLOBALS['product'] ?? null,
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
	throw new \Exception( 'product 不是 WC_Product' );
}

if ( ! ( $chapter instanceof \WP_Post ) ) {
	throw new \Exception( 'chapter 不是 WP_Post' );
}

$product_id = $product->get_id();
$chapter_id = (int) $chapter->ID;

$args2 = [
	'posts_per_page' => - 1,
	'order'          => 'ASC',
	'orderby'        => 'menu_order',
	'post_parent'    => $product->get_id(),
	'post_status'    => 'publish',
	'post_type'      => ChapterCPT::POST_TYPE,
];
/** @var \WP_Post[] $chapters */
$chapters = get_children( $args2 );


foreach ( $chapters as $ch_chapter_id => $chapter ) :
	$args3 = [
		'posts_per_page' => - 1,
		'order'          => 'ASC',
		'orderby'        => 'menu_order',
		'post_parent'    => $ch_chapter_id,
		'post_status'    => 'publish',
		'post_type'      => ChapterCPT::POST_TYPE,
	];

	/** @var \WP_Post[] $sub_chapters */
	$sub_chapters  = get_children( $args3 );
	$children_html = '';
	foreach ( $sub_chapters as $sub_chapter ) :

		$avl_chapter    = new AVLChapter( (int) $sub_chapter->ID );
		$first_visit_at = $avl_chapter->first_visit_at;
		$finished_at    = $avl_chapter->finished_at;


		$icon_html = Plugin::get( 'icon/video', null, false );
		$tooltip   = '點擊觀看';
		if ( $first_visit_at ) {
			$icon_html = Plugin::get( 'icon/check', [ 'type' => 'outline' ], false );
			$tooltip   = "已於 {$first_visit_at} 開始觀看";
		}
		if ( $finished_at ) {
			$icon_html = Plugin::get( 'icon/check', null, false );
			$tooltip   = "已於 {$finished_at} 完成章節";
		}
		$icon_html_with_tooltip = sprintf(
			/*html*/'<div class="pc-tooltip pc-tooltip-right h-6" data-tip="%1$s">%2$s</div>',
			$tooltip,
			$icon_html
		);

		$children_html .= sprintf(
			/*html*/'
				<a data-chapter_id="%1$s" href="%2$s">
					<div class="text-sm border-t-0 border-x-0 border-b border-base-300 border-solid py-3 flex items-center gap-2 pl-8 pr-4 cursor-pointer hover:bg-primary/10 %3$s">
						<div class="%4$s w-8 flex justify-center items-start">%5$s</div>
						<div class="flex-1 text-base-content hover:text-gray-600">
							<p class="my-1 font-medium">%6$s</p>
							<p class="text-gray-400 text-xs m-0 font-light">%7$s</p>
						</div>
					</div>
				</a>
                    ',
			$sub_chapter->ID,
			site_url( "classroom/{$product->get_slug()}/{$sub_chapter->ID}" ),
			$sub_chapter->ID === $chapter_id ? 'bg-primary/10' : '',
			"classroom__sider-collapse__chapter-{$sub_chapter->ID}",
			$icon_html_with_tooltip,
			$sub_chapter->post_title,
			$avl_chapter->get_chapter_length( true )
		);
	endforeach;


	printf(
		/*html*/'
    <div class="pc-collapse pc-collapse-arrow rounded-none mb-1">
			<input type="checkbox" checked="checked"/>
			<div class="pc-collapse-title text-sm font-semibold bg-base-300 py-3 flex items-center justify-between">
				<span>%1$s</span>
				<span class="text-xs text-base-content">共 %2$s 個單元</span>
			</div>
			<div class="pc-collapse-content bg-base-200 p-0">
						%3$s
			</div>
		</div>
    ',
		$chapter->post_title,
		count( $sub_chapters ),
		$children_html
	);
endforeach;
