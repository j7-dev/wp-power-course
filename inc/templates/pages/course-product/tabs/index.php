<?php
/**
 * Course Tabs component
 */

use J7\PowerCourse\Templates\Templates;
use J7\PowerCourse\Utils\Comment as CommentUtils;

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

$description = $product->get_description();
$accordion   = Templates::get(
	'collapse/chapter',
	[
		'product' => $product,
	],
	false
	);
$qa          = Templates::get(
	'collapse/qa',
	[
		'product' => $product,
	],
	false
	);

$review = Templates::get(
		'review',
		[
			'product' => $product,
		],
		false
		);


// 檢查能不能評價商品
$can_comment = CommentUtils::can_comment($product);


$course_tabs = [
	'description' => [
		'label'   => '簡介',
		'content' => \do_shortcode( \wpautop($description) ),
	],
	'chapter' => [
		'label'   => '章節',
		'content' => $accordion,
	],
	'qa' => [
		'label'   => '問答',
		'content' => $qa,
	],
	'comment' => [
		'label'   => '留言',
		'content' => sprintf(
		/*html*/'<div id="comment-app" data-comment_type="comment" data-post_id="%1$s" data-show_list="%2$s" data-show_form="%3$s" data-user_id="%4$s"></div>',
		$product->get_id(),
		$product->get_meta( 'show_review_list' ) === 'yes' ? 'yes' : 'no',
		'yes',
		\get_current_user_id()
		),
	],
	'review' => [
		'label'   => '評價',
		'content' => sprintf(
			/*html*/'<div id="review-app" data-comment_type="review" data-post_id="%1$s" data-show_list="%2$s" data-show_form="%3$s" data-user_id="%4$s"></div>',
			$product->get_id(),
			$product->get_meta( 'show_review_list' ) === 'yes' ? 'yes' : 'no',
			\is_user_logged_in() ? ( $can_comment === true ? 'yes' : $can_comment ) : '您尚未登入',
			\get_current_user_id()
			),
	],
	// 'announcement' => [
	// 'label'   => '公告',
	// 'content' => '🚧 施工中... 🚧',
	// ],
];






$show_review_tab = 'yes' === $product->get_meta( 'show_review_tab' );


if (!$show_review_tab) {
	unset($course_tabs['review']);
}

echo '<div id="courses-product__tabs-nav" class="z-30 w-full">';
Templates::get(
	'tabs/nav',
	[
		'course_tabs'        => $course_tabs,
		'default_active_key' => 'description',
	]
);
echo '</div>';

Templates::get(
	'tabs/content',
	[
		'course_tabs'        => $course_tabs,
		'default_active_key' => 'description',
	]
);
