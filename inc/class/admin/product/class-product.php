<?php
/**
 * Product related features
 */

declare(strict_types=1);

namespace J7\PowerCourse\Admin;

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
		\add_filter( 'product_type_options', array( $this, 'add_product_type_options' ) );
		\add_action( 'save_post_product', array( $this, 'save_product_type_options' ), 10, 3 );
	}



	/**
	 * Add product type options
	 *
	 * @param array $product_type_options - Product type options
	 *
	 * @return array
	 */
	public function add_product_type_options( $product_type_options ): array {

		$option = self::PRODUCT_OPTION_NAME;

		$product_type_options[ $option ] = array(
			'id'            => "_{$option}",
			'wrapper_class' => 'show_if_simple',
			'label'         => '課程',
			'description'   => '是否為課程商品',
			'default'       => 'no',
		);

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
	public function save_product_type_options( $post_id, $product_post, $update ): void {
		$option = self::PRODUCT_OPTION_NAME;
		$option = "_{$option}";
		\update_post_meta( $post_id, $option, isset( $_POST[ $option ] ) ? 'yes' : 'no' ); // phpcs:ignore
	}
}

Product::instance();
