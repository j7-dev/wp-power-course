<?php
/**
 * TODO 分頁
 */

use J7\PowerCourse\Templates\Templates;

/**
 * @var array $args
 */

$default_props = [
	'type' => 'all', // all, launched, un_launched
];

$props = \wp_parse_args( $args, $default_props );

$course_type = $props['type'];

$current_user_id = \get_current_user_id();
$user_course_ids = (array) \get_user_meta( $current_user_id, 'course_ids', false );

$user_course_ids = [ 2030, 2031, 2035, 2038, 2294 ];// DELETE

$filtered_course_ids = array_filter(
	$user_course_ids,
	function ( $course_id ) use ( $course_type ) {
		$course = \wc_get_product( $course_id );
		if ( ! $course ) {
			return false;
		}

		$is_launched = $course->get_meta( 'is_launched' ) === 'yes';

		if ( 'launched' === $course_type && ! $is_launched ) {
			return false;
		}

		if ( 'un_launched' === $course_type && $is_launched ) {
			return false;
		}

		return true;
	}
);

if ( empty( $filtered_course_ids ) ) {
	echo '沒有課程，去逛逛?';

	return;
}

$all_course_html = '<div class="grid grid-cols-3 gap-6">';
foreach ( $filtered_course_ids as $course_id ) {
	$course           = \wc_get_product( $course_id );
	$all_course_html .= Templates::get( 'card/base', $course, false, false );
}
$all_course_html .= '</div>';

echo $all_course_html;
