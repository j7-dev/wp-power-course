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

$chapter_post   = $post;
$chapter        = new Chapter( (int) $chapter_post->ID );
$course_product = $chapter->get_course_product();

$GLOBALS['product'] = $course_product;
$GLOBALS['chapter'] = $chapter_post;

$is_expired = CourseUtils::is_expired($course_product, $current_user_id);

$is_avl = CourseUtils::is_avl();
if (!current_user_can('manage_options')) {
	get_header();
	if ( ! $is_avl ) {
		Plugin::load_template( '404/buy', null );
	} elseif ( ! CourseUtils::is_course_ready( $product ) ) {
		Plugin::load_template( '404/not-ready', null );
	} elseif ( $is_expired ) {
		Plugin::load_template( '404/expired', null );
	}
	get_footer();
	exit;
}

do_action('power_course_before_classroom_render');

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
			echo '<div id="pc-classroom-main">';
			Plugin::load_template( 'classroom/sider', null, true, false );
			Plugin::load_template( 'classroom/bodyV2', null, true, true );
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
