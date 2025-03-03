<?php
/**
 * Body of the classroom page.
 */

use J7\PowerCourse\Plugin;
use J7\Powerhouse\Plugin as Powerhouse;
use J7\PowerCourse\Utils\Course as CourseUtils;

$default_args = [
	'product' => $GLOBALS['course'] ?? null,
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
// @phpstan-ignore-next-line
$video_info = \get_post_meta( $chapter_id, 'chapter_video', true );

echo '<div id="pc-classroom-body" class="w-full bg-base-100 lg:pl-[25rem] pt-[52px] lg:pt-16">';

Plugin::load_template(
	'classroom/header',
	[
		'product' => $product,
		'chapter' => $chapter,
	]
	);

$is_avl = CourseUtils::is_avl();
if (current_user_can('manage_options') && !$is_avl) {
	echo /*html*/'<div class="text-center text-sm text-white bg-orange-500 py-1 w-full sticky top-[52px] lg:top-16 z-30">此為管理員預覽模式</div>';
}

echo '<div class="pc-classroom-body__video z-[15] sticky top-[52px] lg:relative lg:top-[unset]">';
Plugin::load_template(
	'video',
	[
		'video_info' => $video_info,
		'class'      => 'rounded-none',
	]
);
echo '</div>';

echo '<div class="bg-base-200 px-4 lg:px-12 py-4">';
Plugin::load_template( 'progress' );
echo '</div>';



echo '<div class="tw-container mx-auto mt-8">';

Powerhouse::load_template('breadcrumb');

echo '<div class="bn-container">';
the_content();
echo '</div>';




// 留言
printf(
			/*html*/'<div id="comment-app" data-comment_type="comment" data-post_id="%1$s" data-show_list="%2$s" data-show_form="%3$s" data-user_id="%4$s" data-user_role="%5$s"></div>',
			$chapter_id,
			'yes', // $product->get_meta( 'show_comment_list' ) === 'yes' ? 'yes' : 'no',
			'yes',
			\get_current_user_id(),
			\current_user_can('manage_options') ? 'admin' : 'user',
);


echo /*html*/'<div class="pc-divider my-6"></div>';

Powerhouse::load_template('related-posts/children');
Plugin::load_template('related-posts/prev-next');

echo /*html*/'<div class="pc-divider mt-6"></div>';

printf(
/*html*/'<p class="text-sm text-base-content/75">最近修改：%1$s</p>
',
get_the_modified_time('Y-m-d H:i')
);

echo '</div>'; // end container
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
