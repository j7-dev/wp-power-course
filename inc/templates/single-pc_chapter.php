<?php
/**
 * @deprecated 0.8.0 之後使用 chapter 自己的 template
 */

use J7\PowerCourse\Utils\Course as CourseUtils;
use J7\PowerCourse\Resources\Chapter\Utils\Utils as ChapterUtils;
use J7\PowerCourse\Plugin;
use J7\Powerhouse\Plugin as PowerhousePlugin;
use J7\Powerhouse\Theme\FrontEnd as Theme;
use J7\PowerCourse\Resources\Chapter\Models\Chapter;




if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$current_user_id = \get_current_user_id();

if ( ! $current_user_id ) {
	\wp_safe_redirect( \get_permalink( (int) \get_option( 'woocommerce_myaccount_page_id' ) ) ?: \site_url() );
	exit;
}

global $post;

$chapter_post = $post;
$chapter      = new Chapter( (int) $chapter_post->ID );
$product      = $chapter->get_course_product();

$is_expired = CourseUtils::is_expired($product, $current_user_id);

$is_avl = CourseUtils::is_avl();
if (!current_user_can('manage_options')) {
	if ( ! $is_avl ) {
		get_header();
		$GLOBALS['product'] = $product;
		$GLOBALS['chapter'] = $chapter_post;
		Plugin::load_template( '404/buy', null );
		get_footer();
		exit;
	} elseif ( ! CourseUtils::is_course_ready( $product ) ) {
		get_header();
		$GLOBALS['product'] = $product;
		$GLOBALS['chapter'] = $chapter_post;
		Plugin::load_template( '404/not-ready', null );
		get_footer();
		exit;
	} elseif ( $is_expired ) {
		get_header();
		$GLOBALS['product'] = $product;
		$GLOBALS['chapter'] = $chapter_post;
		Plugin::load_template( '404/expired', null );
		get_footer();
		exit;
	}
}

do_action('power_course_before_classroom_render');

// phpcs:disable
?>
<!doctype html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="UTF-8" />
			<meta name="viewport" content="width=device-width, initial-scale=1.0" />
			<?php
			\wp_head();
			// 如果有短代碼就載入 wp_head
			// if ( Base::has_shortcode( \get_the_content(null, false, $chapter_post) ) ) {
			// 	\wp_head();
			// }
			?>
			<link rel="stylesheet" href="<?php echo PowerhousePlugin::$url; ?>/js/dist/css/front.min.css?ver=<?php echo PowerhousePlugin::$version; ?>" media='all' /><?php //phpcs:ignore ?>
			<link rel="stylesheet" href="<?php echo PowerhousePlugin::$url; ?>/js/dist/css/blocknote.min.css?ver=<?php echo PowerhousePlugin::$version; ?>" media='all' />
			<script src="<?php echo site_url(); ?>/wp-includes/js/jquery/jquery.min.js?ver=3.7.1" id="jquery-core-js"></script>
		</head>

		<body class="!m-0 min-h-screen bg-base-100 classroom">
			<?php
			$GLOBALS['product'] = $product;
			$GLOBALS['chapter'] = $chapter_post;

			echo '<div id="pc-classroom-main">';
			Plugin::load_template( 'classroom/sider', null, true, false );
			Plugin::load_template( 'classroom/body', null, true, true );
			echo '</div>';



			// TODO 需要測試會不會重複載入 script
			// if ( Base::has_shortcode( \get_the_content(null, false, $chapter_post) ) ) {
			// 	\wp_footer();
			// }

			// \do_action( 'pc_classroom_footer' );

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
					}
				}
			</script>
			',
			\wp_create_nonce( 'wp_rest' ),
			Plugin::$url,
			(int) \get_option('pc_pdf_watermark_qty', 0),
			(string) \get_option('pc_pdf_watermark_color', 'rgba(255, 255, 255, 0.5)'),
			ChapterUtils::get_formatted_watermark_text('pdf')
			);
			Theme::render_button();
			\wp_footer();
			 ?>

</body>
</html>
<?php // phpcs:enabled
