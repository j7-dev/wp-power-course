<?php
/**
 * Course 生命週期相關
 */

declare( strict_types=1 );

namespace J7\PowerCourse\Resources\Course;

use J7\PowerCourse\Utils\Course as CourseUtils;
use J7\PowerCourse\Resources\Chapter\CPT as ChapterCPT;
use J7\PowerCourse\Utils\AVLCourseMeta;

/**
 * Class LifeCycle
 */
final class LifeCycle {
	use \J7\WpUtils\Traits\SingletonTrait;

	// 開通用戶權限的鉤子
	const ADD_STUDENT_TO_COURSE_ACTION = 'power_course_add_student_to_course';


	/**
	 * Constructor
	 */
	public function __construct() {
		\add_action( self::ADD_STUDENT_TO_COURSE_ACTION, [ __CLASS__, 'add_student_to_course' ], 10, 3 );

		\add_action('before_delete_post', [ __CLASS__, 'delete_course_and_related_items' ], 10, 2);
		\add_action('trashed_post', [ __CLASS__, 'delete_course_and_related_items' ], 10, 2);
		\add_action('untrash_post', [ __CLASS__, 'untrash_course_and_related_items' ], 10, 2);
	}

	/**
	 * 新增學員到課程，開通用戶課程權限
	 *
	 * @param int $user_id 用戶 id
	 * @param int $course_id 課程 id
	 * @param int $expire_date 到期日 10位 timestamp
	 * @return void
	 * @throws \Exception 新增學員失敗
	 */
	public static function add_student_to_course( int $user_id, int $course_id, int $expire_date ): void {
		$current_avl_course_ids = \get_user_meta( $user_id, 'avl_course_ids' );
		if (!\is_array($current_avl_course_ids)) {
			$current_avl_course_ids = [];
		}
		// 先檢查用戶有沒有買過，沒買過才新增 user_meta
		if (!\in_array($course_id, $current_avl_course_ids)) {
			\add_user_meta( $user_id, 'avl_course_ids', $course_id, false );
		}

		$update_success = AVLCourseMeta::update( (int) $course_id, (int) $user_id, 'expire_date', $expire_date );
		if ( false === $update_success) {
			throw new \Exception('新增學員失敗');
		}
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

		if ( !$is_course && $post_type !== ChapterCPT::POST_TYPE ) {
			return [];
		}

		$chapter_ids      = (array) CourseUtils::get_all_chapters( $id, true, [ 'any', 'trash' ] );
		$bundle_ids       = (array) CourseUtils::get_linked_products_by_product_id(  $id, true, [ 'any', 'trash' ] );
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
		if ( !$is_course && $post_type !== ChapterCPT::POST_TYPE ) {
			return [];
		}

		$chapter_ids = (array) CourseUtils::get_all_chapters( $id, true, [ 'any', 'trash' ] );
		$bundle_ids  = (array) CourseUtils::get_linked_products_by_product_id(  $id, true, [ 'any', 'trash' ] );

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
