<?php
/**
 * Product related features
 */

declare(strict_types=1);

namespace J7\PowerCourse\Admin;

use J7\PowerCourse\Utils\Course as CourseUtils;
use J7\PowerCourse\BundleProduct\Helper;

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
		// \add_action( 'save_post_product', [ __CLASS__, 'save_product_type_options' ], 10, 3 );
		\add_filter( 'display_post_states', [ __CLASS__, 'custom_display_post_states' ], 10, 2 );

		if (class_exists('\J7\PowerPartnerServer\Bootstrap')) {
			return;
		}
		\add_filter( 'post_row_actions', [ __CLASS__, 'modify_list_row_actions' ], 10, 2 );
		\add_filter( 'get_edit_post_link', [ __CLASS__, 'modify_edit_post_link' ], 10, 3);
		\add_filter( 'woocommerce_admin_html_order_item_class', [ __CLASS__, 'add_order_item_class' ], 10, 3);
	}



	/**
	 * Add product type options
	 *
	 * @param array{string:array{id:string,wrapper_class:string,label:string,description:string,default:string}} $product_type_options - Product type options
	 *
	 * @return array{string:array{id:string,wrapper_class:string,label:string,description:string,default:string}}
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
	 * @deprecated 已經有 J7\PowerCourse\FrontEnd\Product::add_post_meta_to_course_product
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
	 * Custom display post states
	 *
	 * @param array{string:string} $post_states - Post states
	 * @param \WP_Post             $post - Post object
	 *
	 * @return array{string:string}
	 */
	public static function custom_display_post_states( array $post_states, $post ): array {
		if ( CourseUtils::is_course_product( $post->ID ) ) {
			$post_states['course'] = '課程商品';
		}
		$helper = Helper::instance( $post->ID );
		if ( $helper?->is_bundle_product ) {
			$post_states['bundle'] = '銷售方案商品';
		}
		return $post_states;
	}

	/**
	 * Modify list row actions
	 *
	 * @param array{'inline hide-if-no-js':string,trash:string, edit:string} $actions - Actions
	 * @param \WP_Post                                                       $post - Post object
	 *
	 * @return array{edit:string}
	 */
	public static function modify_list_row_actions( array $actions, \WP_Post $post ): array {

		$helper = Helper::instance( $post->ID );
		if ( CourseUtils::is_course_product( $post->ID ) || $helper?->is_bundle_product ) {
			unset( $actions['inline hide-if-no-js'] );
			unset( $actions['trash'] );
			$actions['edit'] = sprintf(
			/*html*/'<a href="%s" aria-label="編輯〈課程〉" target="_blank">編輯</a>',
			\admin_url("admin.php?page=power-course#/courses/edit/{$post->ID}")
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
		$helper = Helper::instance( $post_id );
		if ( CourseUtils::is_course_product( $post_id ) || $helper?->is_bundle_product ) {
			$link = \admin_url("admin.php?page=power-course#/courses/edit/{$post_id}");
		}

		return $link;
	}

	/**
	 * 針對課程商品 || 銷售方案商品在訂單 detail 添加額外的 class
	 * 不然 WC 會把編輯的連結顯示在畫面上
	 *
	 * @param string                 $class - Class
	 * @param \WC_Order_Item_Product $item - Order item
	 * @param \WC_Order              $order - Order
	 *
	 * @return string
	 */
	public static function add_order_item_class( string $class, \WC_Order_Item_Product $item, \WC_Order $order ): string {
		$product_id = $item->get_product_id();
		$helper     = Helper::instance( $product_id );
		if ( CourseUtils::is_course_product( $product_id ) || $helper?->is_bundle_product ) {
			$class .= ' [&_.wc-order-item-name]:pointer-events-none';
		}

		return $class;
	}
}
