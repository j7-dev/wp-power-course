<?php

use J7\PowerCourse\Resources\Chapter\RegisterCPT;
use J7\PowerCourse\Templates\Templates;

/**
 * @var WC_Product $args
 */
$product = $args;

if ( ! ( $product instanceof \WC_Product ) ) {
	throw new \Exception( 'Invalid Product' );
}

$args = [
	'posts_per_page' => - 1,
	'order'          => 'ASC',
	'orderby'        => 'menu_order',
	'post_parent'    => $product->get_id(),
	'post_status'    => 'publish',
	'post_type'      => RegisterCPT::POST_TYPE,
];

$chapters = \get_children( $args );

foreach ( $chapters as $chapter_id => $chapter ) :
	$args = [
		'posts_per_page' => - 1,
		'order'          => 'ASC',
		'orderby'        => 'menu_order',
		'post_parent'    => $chapter_id,
		'post_status'    => 'publish',
		'post_type'      => RegisterCPT::POST_TYPE,
	];

	$sub_chapters  = \get_children( $args );
	$children_html = '';
	foreach ( $sub_chapters as $sub_chapter ) :
		$children_html .= sprintf(
			'
					<a>
	                    <div class="text-sm border-t-0 border-x-0 border-b border-gray-100 border-solid py-3 flex items-center gap-2 pl-8 pr-4 cursor-pointer">
	                        <div class="w-8 flex justify-center items-start">%1$s</div>
	                        <div class="flex-1 text-gray-800 hover:text-gray-600">%2$s</div>
	                    </div>
                    </a>
                    ',
			Templates::get( 'icon/video', null, false, false ),
			$sub_chapter->post_title,
		);
	endforeach;


	printf(
		'
    <div class="pc-collapse pc-collapse-arrow rounded-none mb-1">
		<input type="checkbox"/>
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
