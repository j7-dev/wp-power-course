<?php
/**
 * @deprecated 0.8.0 之後使用 chapter 自己的 template
 */

use J7\PowerCourse\Utils\Course as CourseUtils;
use J7\PowerCourse\Resources\Chapter\Utils\Utils as ChapterUtils;
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

	// 線性觀看模式：章節鎖定檢查（在所有基本權限驗證之後）
	$product_id = $course_product ? $course_product->get_id() : 0;
	if ( $product_id && CourseUtils::is_linear_chapter_mode( $product_id ) ) {
		if ( ! ChapterUtils::is_chapter_unlocked( (int) $chapter_post->ID, $current_user_id, $product_id ) ) {
			$target_chapter_id = ChapterUtils::get_first_unlocked_chapter_id( $current_user_id, $product_id );
			if ( $target_chapter_id ) {
				\wp_safe_redirect( (string) \get_permalink( $target_chapter_id ) );
				exit;
			}
		}
	}
}


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
					"linear_chapter_mode": "%6$s",
					"is_admin_preview": %7$s
				}
			</script>
			',
			\wp_create_nonce( 'wp_rest' ),
			Plugin::$url,
			$settings->pc_pdf_watermark_qty,
			$settings->pc_pdf_watermark_color,
			ChapterUtils::get_formatted_watermark_text('pdf'),
			\esc_js( $course_product ? ( (string) $course_product->get_meta( 'linear_chapter_mode' ) ?: 'no' ) : 'no' ),
			\current_user_can( 'manage_woocommerce' ) ? 'true' : 'false'
			);
			Theme::render_button();
			\wp_footer();
			 ?>

</body>
</html>
<?php // phpcs:enabled
