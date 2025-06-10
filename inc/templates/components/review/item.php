<?php
/**
 * Review Item
 */

use J7\PowerCourse\Plugin;

$default_args = [
	'comment' => null,
	'class'   => 'p-6 mt-2 rounded',
	'depth'   => 0,
];

$args = wp_parse_args( $args, $default_args );

[
	'comment' => $product_comment,
	'class' => $class,
	'depth' => $depth,
] = $args;

if ( ! $product_comment instanceof WP_Comment ) {
	echo '$product_comment 不是 WP_Comment 實例';
	return;
}
$comment_id      = $product_comment->comment_ID;
$rating          = \get_comment_meta( $comment_id, 'rating', true );
$user_id         = $product_comment->user_id;
$user            = \get_user_by( 'ID', $user_id );
$user_name       = $user ? $user->display_name : '訪客';
$user_avatar_url = \get_user_meta($user_id, 'user_avatar_url', true);
$user_avatar_url = $user_avatar_url ? $user_avatar_url : \get_avatar_url( $user_id  );
$comment_date    = \get_comment_date( 'Y-m-d h:i:s', $comment_id );
$comment_content = wpautop( $product_comment->comment_content );

$children      = $product_comment->get_children();
$children_html = '';
foreach ($children as $child) {
	$children_html .= Plugin::load_template(
		'review/item',
		[
			'comment' => $child,
			'depth'   => $depth + 1,
		],
		false
		);
}

printf(
/*html*/'
<div class="%1$s">
	<div class="flex gap-4">
		<div class="w-10">
			<div class="size-10 rounded-full overflow-hidden relative">
				<img src="%2$s" class="w-full h-full object-cover relative z-20" loading="lazy" decoding="async">
				<div class="absolute top-0 left-0 w-full h-full bg-gray-400 animate-pulse z-10"></div>
			</div>
		</div>
		<div class="flex-1">
			<div class="flex justify-between text-sm">
				<div class="">%4$s</div>
				<div>%5$s</div>
			</div>
			<p class="text-gray-400 text-xs mb-4">%6$s</p>
			<div class="mb-4 text-sm [&_p]:mb-0">%7$s</div>
			%8$s
		</div>
	</div>
</div>
',
	$class . ( $depth % 2 === 0 ? ' bg-gray-100' : ' bg-gray-50' ), // 不同深度灰白相間
	$user_avatar_url,
	$user_name,
	$user_name,
	$rating ? Plugin::load_template(
		'rate',
		[
			'value' => $rating,
		],
		false
		) : '',
	$comment_date,
	$comment_content,
	$children_html
);
