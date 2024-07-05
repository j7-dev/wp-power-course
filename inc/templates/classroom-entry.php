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

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$current_user_id = get_current_user_id();

if ( ! $current_user_id ) {
	wp_safe_redirect( get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) );
	exit;
}


global $product, $chapter;

get_header();

echo '<div id="pc-classroom-main">';

if ( ! CourseUtils::is_avl() ) {
	Templates::get( '404/buy', null );
} elseif ( ! CourseUtils::is_course_ready( $product ) ) {
	Templates::get( '404/not-ready', null );
} else {
	Templates::get( 'classroom/sider', null, true, true );
	Templates::get( 'classroom/body', null, true, true );
}

echo '</div>';

get_footer();
