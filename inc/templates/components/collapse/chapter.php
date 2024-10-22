<?php
/**
 * Collapse component for chapter.
 */

use J7\PowerCourse\Plugin;
use J7\PowerCourse\Resources\Chapter\CPT as ChapterCPT;
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

$args2 = [
	'posts_per_page' => - 1,
	'order'          => 'ASC',
	'orderby'        => 'menu_order',
	'post_parent'    => $product->get_id(),
	'post_status'    => 'publish',
	'post_type'      => ChapterCPT::POST_TYPE,
];

$chapters = \get_children( $args2 );

$is_avl = CourseUtils::is_avl( $product->get_id() );

foreach ( $chapters as $chapter_id => $chapter ) :
	$args3 = [
		'posts_per_page' => - 1,
		'order'          => 'ASC',
		'orderby'        => 'menu_order',
		'post_parent'    => $chapter_id,
		'post_status'    => 'publish',
		'post_type'      => ChapterCPT::POST_TYPE,
	];

	$sub_chapters  = \get_children( $args3 );
	$children_html = '';
	foreach ( $sub_chapters as $sub_chapter ) :
		$classroom_link      = \site_url("classroom/{$product->get_slug()}/{$sub_chapter->ID}");
		$classroom_link_html = sprintf(
		/*html*/'<a href="%1$s" target="_blank" title="前往教室 - %2$s" class="text-primary">%2$s %3$s</a>',
		$classroom_link,
		$sub_chapter->post_title,
		Plugin::get(
			'icon/external-link',
			[
				'class' => 'w-4 h-4 relative top-0.5 ml-2',
			],
			false
			),
		);

		$children_html .= sprintf(
			/*html*/'
			<div class="text-sm border-t-0 border-x-0 border-b border-gray-100 border-solid py-3 flex pl-8 pr-4">
					<div class="w-8 flex justify-center items-start">•</div>
					<div class="flex-1">%1$s</div>
			</div>
			',
			$is_avl ? $classroom_link_html : $sub_chapter->post_title,
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
