<?php
/**
 * 課程銷售頁
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $product;

\add_filter(
	'body_class',
	function ( $classes ) {
		$classes[] = 'bg-gray-50 pc'; // 添加背景色, pc 代表 power course
		return $classes;
	}
);

\add_filter(
	'document_title_parts',
	function ( $title_parts_array ) use ( $product ) {
		$title_parts_array['title'] = $product->get_name() . ' - ' . get_bloginfo('name'); // 修改 doc title
		return $title_parts_array;
	},
	2000
	);

// 如果有使用 rank math
\add_filter(
	'rank_math/frontend/title',
	function ( $title ) use ( $product ) {
		return $product->get_name() . ' - ' . get_bloginfo('name'); // 修改 doc title
	},
	2000
	);

use J7\PowerCourse\Templates\Templates;

$product_status = $product->get_status();
$can_edit       = \current_user_can( 'edit_product', $product->get_id() );

if ('draft' === $product_status && !$can_edit) {
	// 如果商品為草稿且用戶沒有權限編輯，就 redirect
	\wp_safe_redirect( home_url('/404') );
}

$keep_product = $product; // 某些情況下，全域變數在 get_header 後會莫名消失，所以先存起來
get_header();
$GLOBALS['product'] = $keep_product;


if ('draft' === $product_status) {
	echo /*html*/'
	<div role="alert" class="flex justify-center items-center text-white text-xs bg-primary py-2">
		<svg
		xmlns="http://www.w3.org/2000/svg"
		fill="none"
		viewBox="0 0 24 24"
		class="h-4 w-4 shrink-0 stroke-current mr-2">
		<path
			stroke-linecap="round"
			stroke-linejoin="round"
			stroke-width="2"
			d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
		</svg>
		<span>課程尚未發布，目前為預覽模式</span>
	</div>';
}

echo '<div class="leading-7 text-gray-800 w-full max-w-[1138px] mx-auto  px-0 md:px-6 text-base font-normal pt-0 lg:pt-[5rem] pb-[10rem]">';

// Header
Templates::get( 'course-product/header', null, true, true );

echo '<div class="flex flex-col md:flex-row gap-8">';

// Body
Templates::get( 'course-product/body', null, true, true );

// Sider
Templates::get( 'course-product/sider', null, true, true );

echo '</div>';
echo '</div>';

printf(
/*html*/'
<dialog class="pc-already-bought-modal pc-modal">
  <div class="pc-modal-box">
    <h3 class="text-lg font-bold">%1$s</h3>
    <p>%2$s</p>
		<div class="pc-modal-action">
			<button class="pc-already-bought-modal__cancel pc-btn">取消</button>
			<button class="pc-already-bought-modal__confirm pc-btn pc-btn-primary text-white">確認購買</button>
		</div>
	</div>
  <form method="dialog" class="pc-modal-backdrop">
    <button class="opacity-0">close</button>
  </form>
</dialog>
',
'您已經購買過此課程',
'還是要購買嗎?'
);

get_footer();
