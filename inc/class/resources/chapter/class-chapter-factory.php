<?php
/**
 * ChapterFactory
 * 我希望 new ChapterFactory() 時，能夠創建一個新的 Chapter 物件
 */

declare(strict_types=1);

namespace J7\PowerCourse\Resources\Chapter;

use J7\WpUtils\Classes\WP;

/**
 * Class ChapterFactory
 */
final class ChapterFactory {

	const TEMPLATE = '';

	/**
	 * Constructor
	 *
	 * @param array $args Arguments.
	 */
	public function __construct( $args = array() ) {
		self::create_chapter( $args );
	}

	/**
	 * Format Chapter details
	 * WP_Post 轉 array
	 *
	 * @param \WP_Post $post Chapter.
	 * @param bool     $with_description With description.
	 * @param int      $depth Depth.
	 * @return array
	 */
	public static function format_chapter_details( \WP_Post $post, ?bool $with_description = true, ?int $depth = 0 ){ // phpcs:ignore

		if ( ! ( $post instanceof \WP_Post ) ) {
			return array();
		}

		$date_created  = $post->post_date;
		$date_modified = $post->post_modified;

		$image_id  = \get_post_thumbnail_id( $post->ID );
		$image_ids = array( $image_id );
		$images    = array_map( array( 'J7\WpUtils\Classes\WP', 'get_image_info' ), $image_ids );

		$description_array = $with_description ? array(
			'description'       => $post->post_content,
			'short_description' => $post->post_excerpt,
		) : array();

		$chapters = array_values(
			\get_children(
				array(
					'post_parent' => $post->ID,
					'post_type'   => RegisterCPT::POST_TYPE,
					'numberposts' => -1,
					'post_status' => 'any',
				)
			)
		);
		$chapters = array_map(
			array( __CLASS__, 'format_chapter_details' ),
			$chapters,
			array_fill( 0, count( $chapters ), false ),
			array_fill( 0, count( $chapters ), $depth + 1 )
		);

		$children = ! ! $chapters ? array(
			'children' => $chapters,
		) : array();

		$base_array = array(
			// Get Product General Info
			'id'                 => (string) $post->ID,
			'type'               => 'chapter',
			'depth'              => $depth,
			'name'               => $post->post_title,
			'slug'               => $post->post_name,
			'date_created'       => $date_created,
			'date_modified'      => $date_modified,
			'status'             => $post->post_status,
			// 'featured'           => false,
			'catalog_visibility' => '',
			// 'sku'                => '',
			'menu_order'         => (int) $post->menu_order,
			// 'virtual'            => false,
			// 'downloadable'       => false,
			'permalink'          => \get_permalink( $post->ID ),

			// Get Product Prices
			// 'price_html'         => '',
			// 'regular_price'      => '',
			// 'sale_price'         => '',
			// 'on_sale'            => '',
			// 'date_on_sale_from'  => '',
			// 'date_on_sale_to'    => '',
			// 'total_sales'        => '',

			// Get Product Stock
			// 'stock'              => '',
			// 'stock_status'       => '',
			// 'manage_stock'       => '',
			// 'stock_quantity'     => '',
			// 'backorders'         => '',
			// 'backorders_allowed' => '',
			// 'backordered'        => '',
			// 'low_stock_amount'   => '',

			// Get Linked Products
			// 'upsell_ids'         => array(),
			// 'cross_sell_ids'     => array(),

			// Get Product Variations and Attributes
			// 'attributes'         => array(),

			// Get Product Taxonomies
			'category_ids'       => array(),
			'tag_ids'            => array(),

			// Get Product Images
			'images'             => $images,

			'parent_id'          => $post->post_parent,
		) + $children;

		return array_merge(
			$description_array,
			$base_array
		);
	}

	/**
	 * Converter 轉換器
	 * 把 key 轉換/重新命名，將 前端傳過來的欄位轉換成 wp_update_post 能吃的參數
	 *
	 * 前端圖片欄位就傳 'image_ids' string[] 就好
	 *
	 * @param array $args Arguments.
	 *
	 * @return array
	 */
	public static function converter( array $args ): array {
		$fields_mapper = array(
			'id'                => 'unset',
			'name'              => 'post_title',
			'slug'              => 'post_name',
			'description'       => 'post_content',
			'short_description' => 'post_excerpt',
			'status'            => 'post_status',
			'category_ids'      => 'post_category',
			'tag_ids'           => 'tags_input',
			'parent_id'         => 'post_parent',
		);

		$formatted_args = array();
		foreach ( $args as $key => $value ) {
			if ( 'unset' === $fields_mapper[ $key ] ) {
				continue;
			}
			if ( in_array( $key, array_keys( $fields_mapper ), true ) ) {
				$formatted_args[ $fields_mapper[ $key ] ] = $value;
			} else {
				$formatted_args[ $key ] = $value;
			}
		}

		return $formatted_args;
	}

	/**
	 * Create a new chapter
	 *
	 * @see https://developer.wordpress.org/reference/functions/wp_insert_post/
	 *
	 * 簡單的新增，沒有太多參數，所以不使用 Converter
	 *
	 * @param array $args Arguments.
	 *
	 * @return int|\WP_Error
	 */
	public static function create_chapter( array $args ): int|\WP_Error {

		WP::include_required_params(
			$args,
			array(
				'post_parent',
			),
		);

		$args['post_title']    = $params['post_title'] ?? '新章節';
		$args['post_status']   = 'draft';
		$args['post_author']   = \get_current_user_id();
		$args['post_type']     = RegisterCPT::POST_TYPE;
		$args['page_template'] = self::TEMPLATE;

		$new_post_id = \wp_insert_post( $args );

		return $new_post_id;
	}


	/**
	 * Update a chapter
	 *
	 * @param string $id chapter id.
	 * @param array  $args Arguments.
	 * @return integer|\WP_Error
	 */
	public static function update_chapter( string $id, array $args ): int|\WP_Error {

		// 將資料拆成 data 與 meta_data
		[
			'data' => $data,
			'meta_data' => $meta_data,
		] = WP::separator( $args );

		if ( isset( $meta_data['image_ids'] ) ) {
			\set_post_thumbnail( $id, $meta_data['image_ids'][0] );
			unset( $meta_data['image_ids'] );
		}

		$data['ID']         = $id;
		$data['meta_input'] = $meta_data;

		$update_result = \wp_update_post( $data );
		return $update_result;
	}

	/**
	 * Delete a chapter
	 *
	 * @param string $id chapter id.
	 * @param bool   $force_delete Force delete.
	 *
	 * @return \WP_Post|false|null
	 */
	public static function delete_chapter( string $id, ?bool $force_delete = false ): \WP_Post|false|null {
		$delete_result = \wp_delete_post( $id, $force_delete );
		return $delete_result;
	}
}
