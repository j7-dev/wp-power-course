<?php
/**
 * Chapter Utils
 */

declare(strict_types=1);

namespace J7\PowerCourse\Resources\Chapter\Utils;

use J7\WpUtils\Classes\WP;
use J7\PowerCourse\Utils\Course as CourseUtils;
use J7\WpUtils\Classes\General;
use J7\PowerCourse\Resources\Chapter\Core\CPT;

/**
 * Class Utils
 */
abstract class Utils {

	const TEMPLATE = '';

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
	public static function create_chapter( array $args = [] ): int|\WP_Error {
		WP::include_required_params(
			$args,
			[
				'post_parent',
			],
		);

		$post_parent        = (int) $args['post_parent'];
		$parent_post_type   = \get_post_type($post_parent);
		$default_post_title = $parent_post_type === CPT::POST_TYPE ? '新單元' : '新章節';

		$args['post_title']    = $args['post_title'] ?? $default_post_title;
		$args['post_status']   = 'publish';
		$args['post_author']   = \get_current_user_id();
		$args['post_type']     = CPT::POST_TYPE;
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
					'post_type'   => CPT::POST_TYPE,
					'numberposts' => -1,
					'post_status' => 'any',
					'orderby'     => [
						'menu_order' => 'ASC',
						'ID'         => 'ASC',
						'date'       => 'ASC',
					],
				]
			)
		);
		$chapters = array_values(
			array_map(
			[ __CLASS__, 'format_chapter_details' ],
			$chapters,
			array_fill(0, count($chapters), false),
				array_fill(0, count($chapters), $depth + 1)
			)
		);

		$children = $chapters ? [
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
			'description'        => $post->post_content,

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
			$args           = self::converter($node, !$is_new_chapter);

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
			\wp_trash_post( $id );
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
	 * @param bool  $keep_id Keep id. 是否保留 id 欄位
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

		$args['ID']            = $id;
		$args['post_title']    = $args['post_title'] ?? '新章節';
		$args['post_status']   = $args['status'] ?? 'publish';
		$args['post_author']   = \get_current_user_id();
		$args['post_type']     = CPT::POST_TYPE;
		$args['page_template'] = self::TEMPLATE;

		$update_result = \wp_update_post($args);

		return $update_result;
	}

	/**
	 * 取得章節的課程 ID
	 *
	 * @param int $chapter_id 章節 ID.
	 * @return int|null
	 */
	public static function get_course_id( int $chapter_id ): int|null {
		$ancestors = \get_post_ancestors( $chapter_id );
		if ( empty( $ancestors ) ) {
			return null;
		}
		// 取最後一個
		return $ancestors[ count( $ancestors ) - 1 ];
	}

	/**
	 * 檢查章節是否可存取
	 *
	 * @param int|null $chapter_id 章節 ID.
	 * @param int|null $user_id 用戶 ID.
	 * @return bool
	 */
	public static function is_avl( ?int $chapter_id = 0, ?int $user_id = null ): bool {
		$user_id = $user_id ?? \get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}

		if (!$chapter_id) {
			global $chapter;
			if (!( $chapter instanceof \WP_Post )) {
				return false;
			}
			$chapter_id = $chapter->ID;
		}

		$course_id = self::get_course_id( $chapter_id );
		if ( !$course_id ) {
			return false;
		}

		$can_access_course = CourseUtils::is_avl( (int) $course_id, (int) $user_id);
		if ( !$can_access_course ) {
			return false;
		}

		return true; // 可能可以 apply filters
	}


	/**
	 * 取得格式化後的水印文字
	 *
	 * @param string|null $type 類型.
	 * @return string
	 */
	public static function get_formatted_watermark_text( ?string $type = 'video' ): string {
		/** @var string $watermark_text */
		$watermark_text = match ($type) {
			'pdf'   => \get_option( 'pc_pdf_watermark_text', '用戶 {display_name} 正在觀看 {post_title} IP:{ip} \n Email:{email}' ),
			default => \get_option( 'pc_watermark_text', '用戶 {display_name} 正在觀看 {post_title} IP:{ip} <br /> Email:{email}' ),
		};

		$wp_current_user = \wp_get_current_user();
		$email           = $wp_current_user->user_email ?: '';
		$display_name    = $wp_current_user->display_name ?: '';
		$username        = $wp_current_user->user_login ?: '';
		$ip              = General::get_client_ip() ?? '';

		global $chapter;
		$post_title = $chapter ? $chapter->post_title : '';

		$formatted_watermark_text = str_replace( [ '{email}', '{ip}', '{display_name}', '{username}', '{post_title}' ], [ $email, $ip, $display_name, $username, $post_title ], $watermark_text );

		return $formatted_watermark_text;
	}
}
