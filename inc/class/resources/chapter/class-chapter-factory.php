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
	public function __construct( $args = [] ) {
		self::create_chapter($args);
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
			[
				'post_parent',
			],
		);

		$args['post_title']    = $args['post_title'] ?? '新章節';
		$args['post_status']   = 'publish';
		$args['post_author']   = \get_current_user_id();
		$args['post_type']     = RegisterCPT::POST_TYPE;
		$args['page_template'] = self::TEMPLATE;

		return \wp_insert_post($args);
	}

	/**
	 * Format Chapter details
	 * WP_Post 轉 array
	 *
	 * @param \WP_Post $post             Chapter.
	 * @param bool     $with_description With description.
	 * @param int      $depth            Depth.
	 *
	 * @return array
	 */
	public static function format_chapter_details(
		\WP_Post $post,
		?bool $with_description = true,
		?int $depth = 0
	) {
		$date_created  = $post->post_date;
		$date_modified = $post->post_modified;

		$image_id  = \get_post_thumbnail_id($post->ID);
		$image_ids = [ $image_id ];
		$images    = array_map([ WP::class, 'get_image_info' ], $image_ids);

		$description_array = $with_description ? [
			'description'       => $post->post_content,
			'short_description' => $post->post_excerpt,
		] : [];

		$chapters = array_values(
			\get_children(
				[
					'post_parent' => $post->ID,
					'post_type'   => RegisterCPT::POST_TYPE,
					'numberposts' => -1,
					'post_status' => 'any',
					'orderby'     => 'menu_order',
					'order'       => 'ASC',
				]
			)
		);
		$chapters = array_map(
			[ __CLASS__, 'format_chapter_details' ],
			$chapters,
			array_fill(0, count($chapters), false),
			array_fill(0, count($chapters), $depth + 1)
		);

		$children = !!$chapters ? [
			'chapters' => $chapters,
		] : [];

		$base_array = [
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
			'permalink'          => \get_permalink($post->ID),
			'chapter_length'     => (int) \get_post_meta($post->ID, 'chapter_length', true),

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
			'category_ids'       => [],
			'tag_ids'            => [],

			// Get Product Images
			'images'             => $images,

			'parent_id'          => (string) $post->post_parent,
			'chapter_video'      => \get_post_meta($post->ID, 'chapter_video', true),
		] + $children;

		return array_merge(
			$description_array,
			$base_array
		);
	}

	/**
	 * Sort chapters
	 * 改變章節順序
	 *
	 * @param array $params Parameters.
	 *
	 * @return true|\WP_Error
	 */
	public static function sort_chapters( array $params ): bool|\WP_Error {
		$from_tree = $params['from_tree'] ?? [];
		$to_tree   = $params['to_tree'] ?? [];

		$delete_ids = [];
		foreach ($from_tree as $from_node) {
			$id      = $from_node['id'];
			$to_node = array_filter($to_tree, fn ( $node ) => $node['id'] === $id);
			if (empty($to_node)) {
				$delete_ids[] = $id;
			}
		}
		foreach ($to_tree as $node) {
			$id             = $node['id'];
			$is_new_chapter = strpos($id, 'new-') === 0;
			$args           = self::converter($node, keep_id: !$is_new_chapter);

			if ($is_new_chapter) {
				$insert_result = self::create_chapter($args);
			} else {
				$insert_result = self::update_chapter($id, $args);
			}
			if (\is_wp_error($insert_result)) {
				return $insert_result;
			}
		}

		foreach ($delete_ids as $id) {
			self::delete_chapter( (int) $id);
		}

		return true;
	}

	/**
	 * Converter 轉換器
	 * 把 key 轉換/重新命名，將 前端傳過來的欄位轉換成 wp_update_post 能吃的參數
	 *
	 * 前端圖片欄位就傳 'image_ids' string[] 就好
	 *
	 * @param array $args    Arguments.
	 * @param bool  $keep_id Keep id.
	 *
	 * @return array
	 */
	public static function converter( array $args, ?bool $keep_id = false ): array {
		$fields_mapper = [
			'id'                => 'unset',
			'name'              => 'post_title',
			'slug'              => 'post_name',
			'description'       => 'post_content',
			'short_description' => 'post_excerpt',
			'status'            => 'post_status',
			'category_ids'      => 'post_category',
			'tag_ids'           => 'tags_input',
			'parent_id'         => 'post_parent',
			'depth'             => 'unset',
		];

		if ($keep_id) {
			unset($fields_mapper['id']);
		}

		$formatted_args = [];
		foreach ($args as $key => $value) {
			if (in_array($key, array_keys($fields_mapper), true)) {
				if ('unset' === $fields_mapper[ $key ]) {
					continue;
				}
				$formatted_args[ $fields_mapper[ $key ] ] = $value;
			} else {
				$formatted_args[ $key ] = $value;
			}
		}

		return $formatted_args;
	}

	/**
	 * Update a chapter
	 *
	 * @param string $id   chapter id.
	 * @param array  $args Arguments.
	 *
	 * @return integer|\WP_Error
	 */
	public static function update_chapter( string $id, array $args ): int|\WP_Error {
		// 將資料拆成 data 與 meta_data
		[
			'data' => $data,
			'meta_data' => $meta_data,
		] = WP::separator(args: $args, obj: 'post', files: []);

		if (isset($meta_data['image_ids'])) {
			\set_post_thumbnail($id, $meta_data['image_ids'][0]);
			unset($meta_data['image_ids']);
		}

		$data['ID']         = $id;
		$data['meta_input'] = $meta_data;

		$update_result = \wp_update_post($data);

		return $update_result;
	}

	/**
	 * 刪除章節跟其子章節
	 *
	 * @param int  $id           chapter id.
	 * @param bool $force_delete Force delete.
	 *
	 * @return array<int> 刪除的章節 IDs
	 * @throws \Exception 刪除失敗時，會丟出錯誤
	 */
	public static function delete_chapter( int $id, ?bool $force_delete = false ): array {
		$sub_args = [
			'posts_per_page' => - 1,
			'order'          => 'ASC',
			'orderby'        => 'menu_order',
			'post_parent'    => $id,
			'post_status'    => 'publish',
			'post_type'      => RegisterCPT::POST_TYPE,
			'fields'         => 'ids',
		];

		$sub_chapter_ids = (array) \get_children( $sub_args );

		$chapter_ids = [ $id, ...$sub_chapter_ids ];

		$deleted_ids = [];
		foreach ( $chapter_ids as $chapter_id ) :
			$delete_result = \wp_delete_post($chapter_id, $force_delete);
			if ( ! $delete_result ) {
				throw new \Exception( "Chapter #{$chapter_id} delete failed" );
			}
			$deleted_ids[] = $chapter_id;
		endforeach;

		return $deleted_ids;
	}
}
