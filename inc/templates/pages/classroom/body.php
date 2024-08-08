<?php
/**
 * Body of the classroom page.
 */

use J7\PowerCourse\Templates\Templates;
use J7\PowerCourse\Utils\Course as CourseUtils;

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

$content = \do_shortcode( \wpautop($chapter->post_content ) );

$course_tabs = [
	'chapter' => [
		'label'   => '章節',
		'content' => Templates::get( 'classroom/chapters', null, false ),
	],
	'description' => [
		'label'   => '單元內容',
		'content' => $content,
	],
	'discuss' => [
		'label'   => '討論',
		'content' => sprintf(
			/*html*/'<div id="comment-app" data-comment_type="comment" data-post_id="%1$s" data-show_list="%2$s" data-show_form="%3$s" data-user_id="%4$s" data-user_role="%5$s"></div>',
			$chapter_id,
			'yes', // $product->get_meta( 'show_comment_list' ) === 'yes' ? 'yes' : 'no',
			'yes',
			\get_current_user_id(),
			\current_user_can('manage_options') ? 'admin' : 'user',
			),
	],
];

if (!$content) {
	unset($course_tabs['description']);
}

echo '<div id="pc-classroom-body" class="w-full bg-white pt-[3.25rem] lg:pt-16">';

Templates::get( 'classroom/header' );

$is_avl = CourseUtils::is_avl();
if (current_user_can('manage_options') && !$is_avl) {
	echo /*html*/'<div class="text-center text-sm text-white bg-orange-500 py-1 w-full">此為管理員預覽模式</div>';
}

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
		'default_active_key' => $content ? 'description' : 'discuss',
	]
	);

echo '</div>';




echo '<div class="px-4 lg:px-12">';
Templates::get(
'tabs/content',
[
	'course_tabs'        => $course_tabs,
	'default_active_key' => $content ? 'description' : 'discuss',
]
);
echo '</div>';

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
