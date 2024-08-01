<?php
/**
 * Product related features
 */

declare(strict_types=1);

namespace J7\PowerCourse\Admin;

use J7\PowerCourse\Utils\Course as CourseUtils;

/**
 * Class Product
 */
final class Product {
	use \J7\WpUtils\Traits\SingletonTrait;

	const PRODUCT_OPTION_NAME = 'is_course';


	/**
	 * Constructor
	 */
	public function __construct() {
		\add_filter( 'product_type_options', [ __CLASS__, 'add_product_type_options' ] );
		\add_action( 'save_post_product', [ __CLASS__, 'save_product_type_options' ], 10, 3 );
		\add_filter('post_type_link', [ __CLASS__, 'change_product_permalink' ], 10, 2);
		\add_filter( 'display_post_states', [ __CLASS__, 'custom_display_post_states' ], 10, 2 );
		\add_filter( 'post_row_actions', [ __CLASS__, 'modify_list_row_actions' ], 10, 2 );
		\add_filter('get_edit_post_link', [ __CLASS__, 'modify_edit_post_link' ], 10, 3);
	}



	/**
	 * Add product type options
	 *
	 * @param array $product_type_options - Product type options
	 *
	 * @return array
	 */
	public static function add_product_type_options( $product_type_options ): array {

		$option = self::PRODUCT_OPTION_NAME;

		$product_type_options[ $option ] = [
			'id'            => "_{$option}",
			'wrapper_class' => 'show_if_simple',
			'label'         => '課程',
			'description'   => '是否為課程商品',
			'default'       => 'no',
		];

		return $product_type_options;
	}

	/**
	 * Save product type options
	 *
	 * @param int      $post_id - Post ID
	 * @param \WP_Post $product_post - Post object
	 * @param bool     $update - Update flag
	 *
	 * @return void
	 */
	public static function save_product_type_options( $post_id, $product_post, $update ): void {
		$option = self::PRODUCT_OPTION_NAME;
		$option = "_{$option}";
		\update_post_meta( $post_id, $option, isset( $_POST[ $option ] ) ? 'yes' : 'no' ); // phpcs:ignore
	}


	/**
	 * Change product permalink
	 *
	 * @param string   $permalink - Permalink
	 * @param \WP_Post $post - Post object
	 *
	 * @return string
	 */
	public static function change_product_permalink( string $permalink, $post ): string {
		$is_course_product = CourseUtils::is_course_product( $post->ID );
		$override          = \get_option('override_course_product_permalink', 'yes') === 'yes';
		if ( $is_course_product && $override ) {
			$permalink = str_replace('product/', 'courses/', $permalink);
		}
		return $permalink;
	}

	/**
	 * Custom display post states
	 *
	 * @param array    $post_states - Post states
	 * @param \WP_Post $post - Post object
	 *
	 * @return array
	 */
	public static function custom_display_post_states( array $post_states, $post ): array {
		if ( CourseUtils::is_course_product( $post->ID ) ) {
			$post_states['course'] = '課程商品';
		}
		return $post_states;
	}

	/**
	 * Modify list row actions
	 *
	 * @param array    $actions - Actions
	 * @param \WP_Post $post - Post object
	 *
	 * @return array
	 */
	public static function modify_list_row_actions( array $actions, $post ): array {
		if ( CourseUtils::is_course_product( $post->ID ) ) {
			unset( $actions['inline hide-if-no-js'] );
			$actions['edit'] = sprintf(
			/*html*/'<a href="%s" aria-label="編輯〈課程〉" target="_blank">編輯</a>',
			\admin_url('admin.php?page=power-course')
			);
		}
		return $actions;
	}

	/**
	 * Modify edit post link
	 *
	 * @param string $link - Link
	 * @param int    $post_id - Post ID
	 * @param string $context - Context
	 *
	 * @return string
	 */
	public static function modify_edit_post_link( string $link, int $post_id, $context ): string {
		if ( CourseUtils::is_course_product( $post_id ) ) {
			$link = \admin_url('admin.php?page=power-course');
		}
		return $link;
	}
}

Product::instance();
