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
use J7\PowerCourse\Resources\Chapter\Models\Chapter;
use J7\PowerCourse\Plugin;
use J7\Powerhouse\Domains\Post\Utils as PostUtils;

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
		$args['post_title']    = $args['post_title'] ?? '新章節';
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
						'ID'         => 'DESC',
						'date'       => 'DESC',
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
				// 找出那些在 $to_node 裡面不存在的 id，要刪除這些 post
				$delete_ids[] = $id;
			}
		}
		foreach ($to_tree as $node) {
			$id   = $node['id'];
			$args = self::converter($node);

			if (!$node['depth']) {
				// 如果 depth 是 0，代表是頂層，不使用 post_parent ，而是用 meta_key parent_course_id
				// post_parent 要清空
				$args['post_parent'] = 0;
				$args['meta_input']  = [
					'parent_course_id' => $node['parent_id'],
				];

			} else {
				// 如果 depth 不是 0，代表是子章節，使用 post_parent 連接母章節
				// 要刪除 parent_course_id meta_key
				\delete_post_meta( $id, 'parent_course_id' );
			}

			$insert_result = self::update_chapter( (string) $id, $args);

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
	 *
	 * @return array
	 */
	public static function converter( array $args ): array {
		$fields_mapper = [
			'id'                => 'unset', // 不要把 id 回傳
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

		$formatted_args = [];
		foreach ($args as $key => $value) {
			if (in_array($key, array_keys($fields_mapper), true)) {
				// 標註為 unset 的，不要回傳
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
	 * @since 0.8.0
	 * 原:最上層的 post_parent 就是課程 id
	 * 改為: 最上層的 post_parent 是頂層章節，還要從 post_meta 取得 parent_course_id
	 */
	public static function get_course_id( int $chapter_id ): int|null {
		$top_parent_id    = PostUtils::get_top_post_id($chapter_id);
		$parent_course_id = (int) \get_post_meta( $top_parent_id, 'parent_course_id', true );
		return $parent_course_id ? $parent_course_id : null;
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

	/**
	 * 取得快取 key
	 *
	 * @param int    $post_id 章節 ID.
	 * @param string $key 快取 key.
	 * @return string
	 */
	public static function get_cache_key( int $post_id, string $key = 'get_children_posts_html' ): string {
		return "power_course_{$key}_{$post_id}";
	}

	/**
	 * 取得子章節的 HTML (判斷快取)
	 *
	 * @param int                       $post_id 章節 ID.
	 * @param array<int, \WP_Post>|null $children_posts 子章節.
	 * @param int                       $depth 深度.
	 * @return string
	 */
	public static function get_children_posts_html( int $post_id, array $children_posts = null, $depth = 0 ): string {
		$cache_key = self::get_cache_key( $post_id );
		$html      = \get_transient( $cache_key );

		if ( $html ) {
			return $html;
		}

		$html = self::get_children_posts_html_uncached( $post_id, $children_posts, $depth );
		\set_transient( $cache_key, $html, 60 * 60 * 24 );

		return $html;
	}

	/**
	 * 取得課程章節的 HTML
	 * TODO classroom__sider-collapse__chapter-{id} 做什麼用的?
	 *
	 * @param int                       $post_id 課程 id
	 * @param array<int, \WP_Post>|null $children_posts 子章節.
	 * @param int                       $depth 深度，預設從 0 (課程) 開始
	 * @return string
	 */
	public static function get_children_posts_html_uncached( int $post_id, array $children_posts = null, $depth = 0 ): string {
		global $post; // 當前文章

		$html           = '';
		$children_posts = $children_posts === null ? match ($depth) {
			// 因為課程 與章節關係不是 post_parent 而是 parent_course_id
			0 => \get_posts(
				[
					'post_type'      => CPT::POST_TYPE,
					'meta_key'       => 'parent_course_id',
					'meta_value'     => $post_id,
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'orderby'        => [
						'menu_order' => 'ASC',
						'ID'         => 'DESC',
						'date'       => 'DESC',
					],
				]
				),
			// 如果是抓取子章節的子章節
			default => \get_posts(
				[
					'post_type'      => CPT::POST_TYPE,
					'post_parent'    => $post_id,
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'orderby'        => [
						'menu_order' => 'ASC',
						'ID'         => 'DESC',
						'date'       => 'DESC',
					],
				]
				),
		} : $children_posts;

		if (!$children_posts) {
			return '';
		}

		$html .= sprintf(
		/*html*/'<ul class="m-0 p-0 list-none" %1$s>',
			$depth > 0 ? 'style="display: none;"' : ''
		);
		foreach ($children_posts as $child_post) {

			// 取得子章節的子章節
			$child_children_posts = \get_posts(
			[
				'post_type'      => CPT::POST_TYPE,
				'post_parent'    => $child_post->ID,
				'posts_per_page' => -1,
				'orderby'        => [
					'menu_order' => 'ASC',
					'ID'         => 'DESC',
					'date'       => 'DESC',
				],
			]
			);

			$avl_chapter          = new Chapter( (int) $child_post->ID );
			$child_children_count = count( $child_children_posts );

			$html .= sprintf(
			/*html*/'
			<li data-post-id="%6$s" class="hover:bg-primary/10 pr-2 transition-all duration-300 rounded-btn cursor-pointer flex items-center justify-between text-sm mb-1 %7$s" style="padding-left: %5$s;">
				<a class="py-2 flex gap-x-2 items-center flex-1" href="%1$s">
					<div class="w-full">
						%3$s
					</div>
				</a>
				<div class="flex items-center justify-end gap-x-0 w-14">
					%4$s
					%2$s
				</div>
			</li>
			',
			\get_the_permalink($child_post->ID),
			self::get_chapter_icon_html($child_post->ID),
			$child_post->post_title,
				// 如果有子章節，就顯示箭頭
			$child_children_posts ? /*html*/'
				<div class="p-2 icon-arrow flex items-center">
					<svg class="w-4 h-4 fill-base-content" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g stroke-width="0"></g><g stroke-linecap="round" stroke-linejoin="round"></g><g> <path fill-rule="evenodd" clip-rule="evenodd" d="M8.29289 4.29289C8.68342 3.90237 9.31658 3.90237 9.70711 4.29289L16.7071 11.2929C17.0976 11.6834 17.0976 12.3166 16.7071 12.7071L9.70711 19.7071C9.31658 20.0976 8.68342 20.0976 8.29289 19.7071C7.90237 19.3166 7.90237 18.6834 8.29289 18.2929L14.5858 12L8.29289 5.70711C7.90237 5.31658 7.90237 4.68342 8.29289 4.29289Z"></path> </g></svg>
				</div>
			' : '',
			( $depth + 1 ) . 'rem',
			$child_post->ID,
			$child_post->ID === $post->ID ? 'bg-primary/10 font-bold [&>a]:text-primary' : 'font-normal [&>a]:text-base-content' // 如果是當前文章，就顯示 primary 顏色
			);

			// 沒有子章節就結束
			if (!$child_children_posts) {
				continue;
			}

			// 有子章節就遞迴取得子章節的子章節
			$html .= self::get_children_posts_html_uncached($child_post->ID, $child_children_posts, $depth + 1);
		}
		$html .= /* html */'</ul>';

		return $html;
	}


	/**
	 * 取得章節的 icon html
	 *
	 * @param int $chapter_id 章節 ID.
	 * @return string
	 */
	public static function get_chapter_icon_html( int $chapter_id ): string {
		$avl_chapter    = new Chapter( (int) $chapter_id );
		$first_visit_at = $avl_chapter->first_visit_at;
		$finished_at    = $avl_chapter->finished_at;

		$icon_html = Plugin::load_template( 'icon/video', null, false );
		$tooltip   = '點擊觀看';
		if ( $first_visit_at ) {
			$icon_html = Plugin::load_template( 'icon/check', [ 'type' => 'outline' ], false );
			$tooltip   = "已於 {$first_visit_at} 開始觀看";
		}
		if ( $finished_at ) {
			$icon_html = Plugin::load_template( 'icon/check', null, false );
			$tooltip   = "已於 {$finished_at} 完成章節";
		}
		$icon_html_with_tooltip = sprintf(
			/*html*/'<div class="pc-tooltip pc-tooltip-left h-6" data-tip="%1$s">%2$s</div>',
			$tooltip,
			$icon_html
		);

		return $icon_html_with_tooltip;
	}
}
