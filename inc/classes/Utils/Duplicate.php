<?php
/**
 * Duplicate
 * 複製文章功能的抽象類
 */

declare ( strict_types=1 );

namespace J7\PowerCourse\Utils;

use J7\PowerCourse\PowerEmail\Resources\Email\CPT as EmailCPT;
use J7\PowerCourse\Resources\Chapter\Core\CPT as ChapterCPT;
use J7\PowerCourse\BundleProduct\Helper;
use J7\PowerCourse\Utils\Course as CourseUtils;


/**
 * Class Duplicate
 */
final class Duplicate {

	/**
	 * 要排除的 meta key
	 *
	 * @var array<string>
	 */
	protected static array $exclude_meta_keys = [
		'total_sales',
		'_edit_lock',
		'_edit_last',
	];

	/** Constructor */
	public function __construct() {
		\add_action( 'power_course_after_duplicate_post', [ __CLASS__, 'duplicate_children_post' ], 10, 5 );
		\add_action( 'power_course_after_duplicate_post', [ __CLASS__, 'duplicate_children_chapter' ], 10, 5 );
		\add_action( 'power_course_after_duplicate_post', [ __CLASS__, 'duplicate_bundle_product' ], 10, 5 );
	}


	/**
	 * 複製文章/Email
	 *
	 * @param int      $post_id 要複製的文章 ID
	 * @param bool     $copy_terms 是否複製分類
	 * @param int|bool $new_parent 覆寫 post_parent, false 則不複製當前文章的子文章, true 會複製當前文章的子文章但當前文章 post_parent 不變
	 * @param int      $depth 遞迴深度
	 * @return int 複製後的文章 ID
	 * @throws \Exception Exception
	 */
	public function process( int $post_id, ?bool $copy_terms = true, int|bool $new_parent = false, int $depth = 0 ): int {

		$object_type = self::get_object_type( $post_id );

		$new_id = match ( $object_type ) {
			'product' => self::duplicate_product( $post_id, $copy_terms, $new_parent, $depth ),
			default => self::duplicate_post( $post_id, $copy_terms, $new_parent, $depth ),
		};

		\do_action( 'power_course_after_duplicate_post', $this, $post_id, $new_id, $new_parent, $depth );

		return $new_id;
	}

	/**
	 * 取得物件類型
	 *
	 * @param int $post_id 文章 ID
	 *
	 * @return string 物件類型 email, chapter, course 或 post_type
	 */
	private static function get_object_type( int $post_id ): string {
		$post = \get_post( $post_id );
		if (!$post) {
			return '';
		}

		$is_course = \get_post_meta( $post_id, '_is_course', true ) === 'yes';

		/** @var \WP_Post $post */
		return match ($post->post_type) {
			EmailCPT::POST_TYPE => 'email',
			ChapterCPT::POST_TYPE => 'chapter',
			'product' => $is_course ? 'course' : 'product',
			default => $post->post_type,
		};
	}

	/**
	 * 複製文章/Email
	 *
	 * @param int      $post_id 要複製的文章 ID
	 * @param bool     $copy_terms 是否複製分類
	 * @param int|bool $new_parent 覆寫 post_parent, false 則不複製當前文章的子文章, true 會複製當前文章的子文章但當前文章 post_parent 不變
	 * @param int      $depth 遞迴深度
	 *
	 * @return int 複製後的文章 ID
	 * @throws \Exception Exception
	 */
	public static function duplicate_post( int $post_id, ?bool $copy_terms = true, int|bool $new_parent = false, int $depth = 0 ): int {
		$post = \get_post($post_id);
		if (!$post) {
			throw new \Exception(__('文章不存在', 'power-course'));
		}

		// 複製文章並設為草稿
		/** @var \WP_Post $post */
		// @phpstan-ignore-next-line
		$post->ID = null;
		if (0 === $depth) {
			$post->post_title .= ' (複製)';
		}

		// 在插入前處理 post_content
		if (isset($post->post_excerpt)) {
			$post->post_excerpt = \wp_slash($post->post_excerpt);
		}

		// 插入新文章
		// @phpstan-ignore-next-line
		$new_id = \wp_insert_post( (array) $post );

		// @phpstan-ignore-next-line
		if (!\is_numeric($new_id)) {
			throw new \Exception(__('複製文章失敗', 'power-course') . ' ' . $new_id->get_error_message());
		}

		// 複製 meta
		/** @var array<string, array<int, string>> $metas */
		$metas = \get_post_meta($post_id);
		foreach ($metas as $key => $values) {
			foreach ($values as $value) {
				if (in_array($key, self::$exclude_meta_keys, true)) {
					continue;
				}

				\add_post_meta($new_id, $key, \wp_slash(\maybe_unserialize($value)));
			}
		}

		// 複製文章 terms
		if ($copy_terms) {
			$success = self::duplicate_terms($post_id, $new_id);
		}

		$args = [
			'ID'         => $new_id,
			'menu_order' => $post->menu_order + 1,
		];
		if (\is_numeric($new_parent)) {
			$args['post_parent'] = $new_parent;
		}

		\wp_update_post($args);

		return $new_id;
	}

	/**
	 * 複製產品
	 *
	 * @param int      $post_id 要複製的文章 ID
	 * @param bool     $copy_terms 是否複製分類
	 * @param int|bool $new_parent 覆寫 post_parent, false 則不複製當前文章的子文章, true 會複製當前文章的子文章但當前文章 post_parent 不變
	 * @param int      $depth 遞迴深度
	 *
	 * @return int 複製後的商品 ID
	 * @throws \Exception Exception
	 */
	public static function duplicate_product( int $post_id, ?bool $copy_terms = true, int|bool $new_parent = false, int $depth = 0 ): int {
		$product = \wc_get_product($post_id);
		if (!$product) {
			throw new \Exception(__('產品不存在', 'power-course'));
		}

		// 使用 WC_Admin_Duplicate_Product 複製產品
		$duplicate      = new \WC_Admin_Duplicate_Product();
		$new_product    = $duplicate->product_duplicate($product);
		$new_product_id = $new_product->get_id();

		// 如果需要複製分類
		if ($copy_terms) {
			self::duplicate_terms($post_id, $new_product_id);
		}

		if (is_numeric($new_parent)) {
			// 更新銷售方案的的 link_course_ids
			$new_product->update_meta_data(Helper::LINK_COURSE_IDS_META_KEY, (string) $new_parent);
			$new_product->save_meta_data();
		}

		return $new_product_id;
	}

	/**
	 * 複製項目的分類關係
	 *
	 * @param int|\WP_Post|\WC_Product $source 來源項目（可以是 ID、Post 物件或 Product 物件）
	 * @param int                      $target_id 目標項目 ID
	 *
	 * @return bool 設定 term 是否成功
	 * @throws \Exception Exception
	 */
	public static function duplicate_terms( $source, int $target_id ): bool {
		// 取得來源 ID 和類型
		$source_id = 0;
		$post_type = '';

		if (is_numeric($source)) {
			$source_id = (int) $source;
			$post      = \get_post($source_id);
			// @phpstan-ignore-next-line
			$post_type = $post ? $post->post_type : '';
		} elseif ($source instanceof \WC_Product) {
			$source_id = $source->get_id();
			$post_type = 'product';
		} elseif ($source instanceof \WP_Post) {
			$source_id = $source->ID;
			$post_type = $source->post_type;
		}

		if (!$source_id || !$post_type) {
			return false;
		}

		// 取得該類型的所有分類法
		$taxonomies = \get_object_taxonomies($post_type);

		foreach ($taxonomies as $taxonomy) {
			$terms = \wp_get_object_terms($source_id, $taxonomy);
			if (!empty($terms) && !\is_wp_error($terms)) {
				$term_ids = \wp_list_pluck($terms, 'term_id');
				$result   = \wp_set_object_terms($target_id, $term_ids, $taxonomy);
				if (\is_wp_error($result)) {
					throw new \Exception($result->get_error_message());
				}
			}
		}

		return true;
	}

	/**
	 * 複製子文章
	 *
	 * @param self $duplicate 複製物件
	 * @param int  $post_id 文章 ID
	 * @param int  $new_id 複製後的文章 ID
	 * @param int  $new_parent 覆寫 post_parent, false 則不複製當前文章的子文章, true 會複製當前文章的子文章但當前文章 post_parent 不變
	 * @param int  $depth 遞迴深度
	 *
	 * @return void
	 */
	public static function duplicate_children_post( self $duplicate, int $post_id, int $new_id, ?int $new_parent = 0, int $depth = 0 ): void {
		if (!$new_parent) {
			return;
		}

		$allowed_post_types = [
			'post',
			'product',
			ChapterCPT::POST_TYPE,
			EmailCPT::POST_TYPE,
		];

		/** @var array<int> $children_ids */
		$children_ids = \get_children(
			[
				'post_parent' => $post_id,
				'post_type'   => $allowed_post_types,
				'numberposts' => -1,
				'fields'      => 'ids',
			]
			);

		foreach ($children_ids as $child_id) {
			$duplicate->process($child_id, true, $new_id, $depth + 1);
		}
	}

	/**
	 * 複製子章節
	 *
	 * @param self $duplicate 複製物件
	 * @param int  $post_id 文章 ID
	 * @param int  $new_id 複製後的文章 ID
	 * @param int  $new_parent 覆寫 post_parent, false 則不複製當前文章的子文章, true 會複製當前文章的子文章但當前文章 post_parent 不變
	 * @param int  $depth 遞迴深度
	 *
	 * @return void
	 */
	public static function duplicate_children_chapter( self $duplicate, int $post_id, int $new_id, ?int $new_parent = 0, int $depth = 0 ): void {
		if (!$new_parent) {
			return;
		}
		// 只有複製課程時，才處理頂層子章節的複製
		$is_course_product = CourseUtils::is_course_product( $new_id );

		if (!$is_course_product) {
			return;
		}

		$allowed_post_types = [
			ChapterCPT::POST_TYPE,
		];

		/** @var array<int> $children_ids 原本課程的頂層 id */
		$children_ids = \get_children(
			[
				'post_type'   => $allowed_post_types,
				'numberposts' => -1,
				'fields'      => 'ids',
				'meta_key'    => 'parent_course_id',
				'meta_value'  => $post_id,
			]
			);

		$copied_ids = [];
		foreach ($children_ids as $child_id) {
			$copied_ids[] = $duplicate->process($child_id, true, true, $depth + 1);
		}

		// 把這貼複製後的頂層 chapter meta_key parent_course_id 更新為新的課程 id
		foreach ($copied_ids as $copied_id) {
			$post = \get_post($copied_id);
			if (!$post) {
				continue;
			}

			\wp_update_post(
				[
					'ID'         => $copied_id,
					'menu_order' => $post->menu_order + 1,
					'meta_input' => [
						'parent_course_id' => $new_id,
					],
				]
				);
		}
	}


	/**
	 * 複製銷售方案
	 *
	 * @param self $duplicate 複製物件
	 * @param int  $post_id 文章 ID
	 * @param int  $new_id 複製後的文章 ID
	 * @param int  $new_parent 覆寫 post_parent, false 則不複製當前文章的子文章, true 會複製當前文章的子文章但當前文章 post_parent 不變
	 * @param int  $depth 遞迴深度
	 *
	 * @return void
	 */
	public static function duplicate_bundle_product( self $duplicate, int $post_id, int $new_id, ?int $new_parent = 0, int $depth = 0 ): void {
		if (!$new_parent) {
			return;
		}

		// 原課程身上的銷售方案
		$bundle_product_ids = \get_posts(
			// @phpstan-ignore-next-line
			[
				'post_type'   => 'product',
				'numberposts' => -1,
				'meta_key'    => Helper::LINK_COURSE_IDS_META_KEY,
				'meta_value'  => $post_id,
				'fields'      => 'ids',
			]
		);

		// @phpstan-ignore-next-line
		if (!is_array($bundle_product_ids)) {
			return;
		}

		foreach ($bundle_product_ids as $bundle_product_id) {
			$duplicate->process($bundle_product_id, true, $new_id, $depth + 1);
		}
	}
}
