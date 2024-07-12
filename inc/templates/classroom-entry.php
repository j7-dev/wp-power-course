<?php
/**
 * The Template for displaying all single products
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://woo.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     1.6.4
 */

use J7\PowerCourse\Templates\Templates;
use J7\PowerCourse\Utils\Course as CourseUtils;
use J7\PowerCourse\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$current_user_id = get_current_user_id();

if ( ! $current_user_id ) {
	wp_safe_redirect( get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) );
	exit;
}
// phpcs:disable
?>
<!doctype html>
		<html lang="zh_tw">

		<head>
			<meta charset="UTF-8" />
			<meta name="viewport" content="width=device-width, initial-scale=1.0" />
			<title>Power Course | 可能是 WordPress 最好用的課程外掛</title>
			<link rel="stylesheet" id="wp-power-course-css" href="<?php echo Plugin::$url . '/inc/assets/dist/css/index.css?ver=' . Plugin::$version; ?>"  media='all' />
			<script src="<?php echo site_url(); ?>/wp-includes/js/jquery/jquery.min.js?ver=3.7.1" id="jquery-core-js"></script>
		</head>

		<body class="!m-0 min-h-screen">
			<?php
			global $product;
			echo '<div id="pc-classroom-main">';

			if ( ! CourseUtils::is_avl() ) {
				Templates::get( '404/buy', null );
			} elseif ( ! CourseUtils::is_course_ready( $product ) ) {
				Templates::get( '404/not-ready', null );
			} else {
				Templates::get( 'classroom/sider', null, true, false );
				Templates::get( 'classroom/body', null, true, true );
			}

			echo '</div>';

			printf(
			/*html*/'
			<script>
				window.pc_data = {
					"nonce": "%1$s",
				}
			</script>
			',
			\wp_create_nonce( 'wp_rest' )
			);
			?>
	<script id="wp-power-course-js" src="<?php echo Plugin::$url . '/inc/assets/dist/index.js?ver=' . Plugin::$version; ?>" ></script>
</body>
</html>
<?php // phpcs:enabled
