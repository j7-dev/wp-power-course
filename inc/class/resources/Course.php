<?php
/**
 * Course 相關
 */

declare( strict_types=1 );

namespace J7\PowerCourse\Resources;

use J7\PowerCourse\Utils\Course as CourseUtils;
use J7\PowerCourse\Resources\Chapter\RegisterCPT;

/**
 * Class Course
 */
final class Course {
	use \J7\WpUtils\Traits\SingletonTrait;


	/**
	 * Constructor
	 */
	public function __construct() {
		\add_action('before_delete_post', [ __CLASS__, 'delete_course_and_related_items' ], 10, 2);
		\add_action('trashed_post', [ __CLASS__, 'delete_course_and_related_items' ], 10, 2);
		\add_action('untrash_post', [ __CLASS__, 'untrash_course_and_related_items' ], 10, 2);
	}

	/**
	 * 刪除課程與相關項目
	 * 刪除課程時連帶刪除子章節以及銷售方案(bundle product)
	 *
	 * @param int                $id 課程 id
	 * @param ?string | \WP_Post $post_or_previous_status WP_Post(delete_post) 或 前一個狀態(trashed_post)
	 * @return array<int> 刪除的 post id
	 */
	public static function delete_course_and_related_items( int $id, $post_or_previous_status = null ): array {
		$post_type = \get_post_type( $id );
		$is_course = \get_post_meta( $id, '_is_course', true ) === 'yes';

		if ( !$is_course && $post_type !== RegisterCPT::POST_TYPE ) {
			return [];
		}

		$chapter_ids      = (array) CourseUtils::get_all_chapters( $id, true, [ 'any', 'trash' ] );
		$bundle_ids       = (array) CourseUtils::get_bundles_by_product(  $id, true, [ 'any', 'trash' ] );
		$deleted_post_ids = [];

		foreach ([ ...$chapter_ids, ...$bundle_ids ] as $post_id) {
			$result = \wp_trash_post( $post_id );
			if ( $result ) {
				$deleted_post_ids[] = $post_id;
			}
		}

		self::delete_avl_course_by_course_id( $id );

		self::delete_bind_course_data_by_course_id( $id );

		return $deleted_post_ids;
	}

	/**
	 * 移除所有能上 $course_id 的用戶權限
	 * 刪除課程時，連動刪除
	 *
	 * @param int $course_id 課程 id
	 */
	private static function delete_avl_course_by_course_id( int $course_id ): void {
		global $wpdb;

		$wpdb->delete(
			$wpdb->prefix . 'usermeta',
			[
				'meta_key'   => 'avl_course_ids' ,
				'meta_value' => $course_id,
			],
			[ '%s', '%d' ]
		);
	}


	/**
	 * 移除所有連動 $course_id 的商品 post_meta
	 * 刪除課程時，刪除連動商品的 post_meta
	 *
	 * @param int $course_id 課程 id
	 */
	private static function delete_bind_course_data_by_course_id( int $course_id ): void {
		$product_ids = \get_posts(
			[
				'post_type'      => [ 'product', 'product_variation' ],
				'meta_key'       => 'bind_course_ids',
				'meta_value'     => $course_id,
				'posts_per_page' => -1,
				'fields'         => 'ids',
			]
			);

		foreach ($product_ids as $product_id) {
			\delete_post_meta($product_id, 'bind_course_id', $course_id);
			$bind_courses_data = \get_post_meta($product_id, 'bind_courses_data', true) ?: [];
			$bind_courses_data = array_filter($bind_courses_data, fn( $item ) => ( (int) $item['id'] ) !== $course_id);
			\update_post_meta($product_id, 'bind_courses_data', $bind_courses_data);
		}
	}

	/**
	 * 還原課程與相關項目
	 * 還原課程時連帶還原子章節以及組合商品(bundle product)
	 *
	 * @param int     $id 課程 id
	 * @param ?string $previous_status 前一個狀態
	 * @return array<int> 還原的 post id
	 */
	public static function untrash_course_and_related_items( int $id, ?string $previous_status = null ): array {
		$post_type = \get_post_type( $id );
		$is_course = \get_post_meta( $id, '_is_course', true ) === 'yes';
		if ( !$is_course && $post_type !== RegisterCPT::POST_TYPE ) {
			return [];
		}

		$chapter_ids = (array) CourseUtils::get_all_chapters( $id, true, [ 'any', 'trash' ] );
		$bundle_ids  = (array) CourseUtils::get_bundles_by_product(  $id, true, [ 'any', 'trash' ] );

		$restored_post_ids = [];

		foreach ([ ...$chapter_ids, ...$bundle_ids ] as $post_id) {
			$post_status = \get_post_status( $post_id );
			$result      = \wp_update_post(
				[
					'ID'          => $post_id,
					'post_status' => $post_status === 'trash' ? 'publish' : $post_status,
				]
			);
			if (!\is_wp_error($result)) {
				$restored_post_ids[] = $post_id;
			}
		}

		return $restored_post_ids;
	}
}

Course::instance();
