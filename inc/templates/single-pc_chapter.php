<?php
/**
 * @deprecated 0.8.0 之後使用 chapter 自己的 template
 */

use J7\PowerCourse\Utils\Course as CourseUtils;
use J7\PowerCourse\Resources\Chapter\Utils\Utils as ChapterUtils;
use J7\PowerCourse\Utils\LinearViewing;
use J7\PowerCourse\Plugin;
use J7\Powerhouse\Theme\Core\FrontEnd as Theme;
use J7\PowerCourse\Resources\Chapter\Model\Chapter;
use J7\PowerCourse\Resources\Settings\Model\Settings;





if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$current_user_id = \get_current_user_id();

if ( ! $current_user_id ) {
	\wp_safe_redirect( \get_permalink( (int) \get_option( 'woocommerce_myaccount_page_id' ) ) ?: \site_url() );
	exit;
}

global $post;

$chapter_post   = $post;
$chapter        = new Chapter( (int) $chapter_post->ID );
$course_product = $chapter->get_course_product();


$GLOBALS['course']  = $course_product;
$GLOBALS['chapter'] = $chapter_post;

$is_expired = CourseUtils::is_expired($course_product, $current_user_id);

$is_avl = CourseUtils::is_avl($course_product?->get_id());

if (!current_user_can('manage_woocommerce')) {
	if ( ! $is_avl ) {
		get_header();
		Plugin::load_template( '404/buy', null );
		get_footer();
		exit;
	} elseif ( ! $course_product || ! CourseUtils::is_course_ready( $course_product ) ) {
		get_header();
		Plugin::load_template( '404/not-ready', null );
		get_footer();
		exit;
	} elseif ( $is_expired ) {
		get_header();
		Plugin::load_template( '404/expired', null );
		get_footer();
		exit;
	}

	if ('publish' !== $post->post_status) {
		\wp_safe_redirect(site_url('404'));
		exit;
	}
}

// 線性觀看：鎖定章節 redirect 到當前應觀看的章節
$linear_state    = null;
$linear_flash_message = '';
if ( !current_user_can('manage_woocommerce') && $course_product ) {
	$linear_state = LinearViewing::get_unlock_state(
		$course_product->get_id(),
		$current_user_id
	);

	if ( $linear_state['enabled']
		&& !in_array( $chapter_post->ID, $linear_state['unlocked_chapter_ids'], true )
	) {
		$redirect_chapter_id = $linear_state['current_chapter_id']
			?? ( $linear_state['unlocked_chapter_ids'][0] ?? null );

		if ( $redirect_chapter_id ) {
			// 設定一次性 flash message（用 transient，5 秒過期）
			$transient_key = "pc_linear_redirect_{$current_user_id}";
			\set_transient( $transient_key, '請先完成前面的章節才能觀看此內容', 5 );
			\wp_safe_redirect( \get_permalink( $redirect_chapter_id ) ?: \site_url() );
			exit;
		}
	}
}

// 讀取 redirect flash message（redirect 目標頁面讀取後顯示 toast）
$transient_key = "pc_linear_redirect_{$current_user_id}";
$flash_message = \get_transient( $transient_key );
if ( $flash_message ) {
	$linear_flash_message = (string) $flash_message;
	\delete_transient( $transient_key );
}

// 若尚未計算 linear_state，現在計算（供模板使用）
if ( null === $linear_state && $course_product ) {
	$linear_state = LinearViewing::get_unlock_state(
		$course_product->get_id(),
		$current_user_id
	);
}

// 注入 linear_state 為全域變數，供子模板使用
$GLOBALS['pc_linear_state'] = $linear_state;

do_action('power_course_before_classroom_render');

$settings = Settings::instance();

// phpcs:disable
?>
<!doctype html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>">
			<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, viewport-fit=cover">
			<link rel="profile" href="https://gmpg.org/xfn/11">
			<?php wp_head(); ?>
		</head>

		<body class="!m-0 min-h-screen bg-base-100 classroom">
			<?php

			Plugin::load_template('classroom/sider');
			echo '<div id="pc-classroom-main">';
			Plugin::load_template( 'classroom/body', null, true, true );
			echo '</div>';


			printf(
			/*html*/'
			<script>
				window.pc_data = {
					"nonce": "%1$s",
					"plugin_url": "%2$s",
					"pdf_watermark": {
						"qty": "%3$d",
						"color": "%4$s",
						"text": "%5$s"
					},
					"linear_viewing": %6$s,
					"linear_flash_message": "%7$s"
				}
			</script>
			',
			\wp_create_nonce( 'wp_rest' ),
			Plugin::$url,
			$settings->pc_pdf_watermark_qty,
			$settings->pc_pdf_watermark_color,
			ChapterUtils::get_formatted_watermark_text('pdf'),
			\wp_json_encode( $linear_state ),
			\esc_js( $linear_flash_message )
			);
			Theme::render_button();
			\wp_footer();
			 ?>

</body>
</html>
<?php // phpcs:enabled
