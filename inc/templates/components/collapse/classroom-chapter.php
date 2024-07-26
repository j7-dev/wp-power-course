<?php
/**
 * Classroom > Sider > Chapters
 */

use J7\PowerCourse\Resources\Chapter\RegisterCPT;
use J7\PowerCourse\Templates\Templates;
use J7\PowerCourse\Utils\Base;
use J7\PowerCourse\Utils\Course as CourseUtils;

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

$product_id           = $product->get_id();
$chapter_id           = (int) $chapter->ID;
$finished_chapter_ids = CourseUtils::get_finished_chapters( $product_id, return_ids: true );

$args2 = [
	'posts_per_page' => - 1,
	'order'          => 'ASC',
	'orderby'        => 'menu_order',
	'post_parent'    => $product->get_id(),
	'post_status'    => 'publish',
	'post_type'      => RegisterCPT::POST_TYPE,
];

$chapters = get_children( $args2 );

foreach ( $chapters as $ch_chapter_id => $chapter ) :
	$args3 = [
		'posts_per_page' => - 1,
		'order'          => 'ASC',
		'orderby'        => 'menu_order',
		'post_parent'    => $ch_chapter_id,
		'post_status'    => 'publish',
		'post_type'      => RegisterCPT::POST_TYPE,
	];

	$sub_chapters  = get_children( $args3 );
	$children_html = '';
	foreach ( $sub_chapters as $sub_chapter ) :
		$chapter_length = (int) get_post_meta( $sub_chapter->ID, 'chapter_length', true );
		$is_finished    = in_array( (string) $sub_chapter->ID, $finished_chapter_ids, true );
		$icon           = $is_finished ? 'icon/check' : 'icon/video';

		$children_html .= sprintf(
			/*html*/'
				<a href="%1$s">
					<div class="text-sm border-t-0 border-x-0 border-b border-gray-100 border-solid py-3 flex items-center gap-2 pl-8 pr-4 cursor-pointer hover:bg-primary/10 %2$s">
						<div id="%3$s" class="w-8 flex justify-center items-start">%4$s</div>
						<div class="flex-1 text-gray-800 hover:text-gray-600">
							<p class="my-1 font-medium">%5$s</p>
							<p class="text-gray-400 text-xs m-0 font-light">%6$s</p>
						</div>
					</div>
				</a>
                    ',
			site_url( "classroom/{$product->get_slug()}/{$sub_chapter->ID}" ),
			$sub_chapter->ID === $chapter_id ? 'bg-primary/10' : '',
			"classroom__sider-collapse__chapter-{$sub_chapter->ID}",
			Templates::get( $icon, null, false ),
			$sub_chapter->post_title,
			Base::get_video_length_by_seconds( $chapter_length )
		);
	endforeach;


	printf(
		/*html*/'
    <div class="pc-collapse pc-collapse-arrow rounded-none mb-1">
			<input type="checkbox" checked="checked"/>
			<div class="pc-collapse-title text-sm font-semibold bg-gray-100 py-3 flex items-center justify-between">
				<span>%1$s</span>
				<span class="text-xs text-gray-400">共 %2$s 個單元</span>
			</div>
			<div class="pc-collapse-content bg-gray-50 p-0">
						%3$s
			</div>
		</div>
    ',
		$chapter->post_title,
		count( $sub_chapters ),
		$children_html
	);
endforeach;
