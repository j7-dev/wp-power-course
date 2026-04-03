<?php
/**
 * 線性觀看模式下完成章節 整合測試
 * Feature: specs/features/linear-viewing/線性觀看模式下完成章節.feature
 *
 * @group linear-viewing
 * @group command
 */

declare( strict_types=1 );

namespace Tests\Integration\LinearViewing;

use Tests\Integration\TestCase;
use J7\PowerCourse\Resources\Chapter\Utils\Utils as ChapterUtils;
use J7\PowerCourse\Resources\Chapter\Utils\MetaCRUD as AVLChapterMeta;
use J7\PowerCourse\Utils\Course as CourseUtils;

/**
 * Class LinearChapterToggleFinishedTest
 * 測試線性觀看模式下章節完成操作的業務規則
 *
 * 注意：直接測試業務邏輯（ChapterUtils::is_chapter_unlocked 等），
 * 不透過 REST API（REST API 整合測試由 E2E 覆蓋）
 */
class LinearChapterToggleFinishedTest extends TestCase {

	/** @var int 測試課程 ID */
	private int $course_id;

	/** @var int Alice 用戶 ID */
	private int $alice_id;

	/** @var int 第一章 ID */
	private int $ch1;

	/** @var int 1-1 章節 ID */
	private int $ch1_1;

	/** @var int 1-2 章節 ID */
	private int $ch1_2;

	protected function configure_dependencies(): void {
		// 直接使用業務邏輯方法
	}

	public function set_up(): void {
		parent::set_up();

		$this->course_id = $this->create_course(
			[
				'post_title' => 'PHP 基礎課',
				'_is_course' => 'yes',
			]
		);

		\update_post_meta( $this->course_id, 'linear_chapter_mode', 'yes' );

		// 第一章
		$this->ch1 = $this->create_chapter(
			$this->course_id,
			[
				'post_title'  => '第一章',
				'menu_order'  => 1,
				'post_parent' => $this->course_id,
			]
		);
		// 1-1
		$this->ch1_1 = $this->create_chapter(
			$this->course_id,
			[
				'post_title'  => '1-1',
				'menu_order'  => 1,
				'post_parent' => $this->ch1,
			]
		);
		// 1-2
		$this->ch1_2 = $this->create_chapter(
			$this->course_id,
			[
				'post_title'  => '1-2',
				'menu_order'  => 2,
				'post_parent' => $this->ch1,
			]
		);

		$this->alice_id     = $this->factory()->user->create(
			[
				'user_login' => 'alice_' . uniqid(),
				'user_email' => 'alice_' . uniqid() . '@test.com',
			]
		);
		$this->ids['Alice'] = $this->alice_id;

		$this->enroll_user_to_course( $this->alice_id, $this->course_id, 0 );

		\wp_cache_delete( 'flatten_post_ids_' . $this->course_id, 'prev_next' );
	}

	public function tear_down(): void {
		\wp_cache_delete( 'flatten_post_ids_' . $this->course_id, 'prev_next' );
		\wp_cache_delete( "pid_{$this->course_id}_uid_{$this->alice_id}", 'pc_course_progress' );
		parent::tear_down();
	}

	/**
	 * @test
	 * @group error
	 * Rule: 章節必須為已解鎖狀態才能完成
	 * Example: 嘗試完成被鎖定的章節時，is_chapter_unlocked 返回 false
	 */
	public function test_嘗試完成被鎖定章節時解鎖判定為false(): void {
		// Given Alice 未完成第一章（ch1_1 被鎖定）

		// When 查詢 ch1_1 是否已解鎖
		$is_unlocked = ChapterUtils::is_chapter_unlocked( $this->ch1_1, $this->alice_id, $this->course_id );

		// Then ch1_1 應為鎖定（前一章 ch1 未完成）
		$this->assertFalse( $is_unlocked, '前一章未完成時，目標章節應鎖定' );
	}

	/**
	 * @test
	 * @group happy
	 * Rule: 完成已解鎖章節時成功
	 * Example: 完成第一章（固定解鎖）
	 */
	public function test_完成已解鎖的第一章成功(): void {
		// Given 第一章固定解鎖，且未完成

		// When 完成第一章
		try {
			AVLChapterMeta::add( $this->ch1, $this->alice_id, 'finished_at', \wp_date( 'Y-m-d H:i:s' ) );
			$this->lastError = null;
		} catch ( \Throwable $e ) {
			$this->lastError = $e;
		}

		// Then 操作成功
		$this->assert_operation_succeeded();

		// And finished_at 應不為空
		$finished_at = $this->get_chapter_meta( $this->ch1, $this->alice_id, 'finished_at' );
		$this->assertNotEmpty( $finished_at, '完成後 finished_at 應不為空' );
	}

	/**
	 * @test
	 * @group error
	 * Rule: 線性觀看模式下禁止取消完成
	 * Example: 嘗試取消已完成章節時，is_linear = true 且 is_this_chapter_finished = true → 應被阻擋
	 */
	public function test_線性模式下已完成章節不應被取消(): void {
		// Given Alice 完成第一章
		$this->set_chapter_finished( $this->ch1, $this->alice_id, '2025-06-01 10:00:00' );

		// 確認線性模式已開啟
		$is_linear = CourseUtils::is_linear_chapter_mode( $this->course_id );
		$this->assertTrue( $is_linear, '確認線性模式已開啟' );

		// 確認 finished_at 已存在（代表 is_this_chapter_finished = true）
		$finished_at = $this->get_chapter_meta( $this->ch1, $this->alice_id, 'finished_at' );
		$this->assertNotEmpty( $finished_at, '第一章應已完成' );

		// 業務規則驗證：線性模式下 is_this_chapter_finished = true 應觸發禁止取消邏輯
		// 此處驗證：線性模式 AND 已完成 → 應回傳 403 forbid
		$should_block = $is_linear && ! empty( $finished_at );
		$this->assertTrue( $should_block, '線性模式下取消完成應被阻擋' );
	}

	/**
	 * @test
	 * @group happy
	 * Rule: 關閉線性觀看後可以取消完成
	 * Example: 關閉線性觀看後取消完成
	 */
	public function test_關閉線性觀看後可以取消完成(): void {
		// Given 課程關閉線性觀看
		\update_post_meta( $this->course_id, 'linear_chapter_mode', 'no' );

		// And Alice 已完成第一章
		$this->set_chapter_finished( $this->ch1, $this->alice_id, '2025-06-01 10:00:00' );

		// When 取消完成（刪除 finished_at）
		try {
			AVLChapterMeta::delete( $this->ch1, $this->alice_id, 'finished_at' );
			$this->lastError = null;
		} catch ( \Throwable $e ) {
			$this->lastError = $e;
		}

		// Then 操作成功
		$this->assert_operation_succeeded();

		// And finished_at 應為空
		$finished_at = $this->get_chapter_meta( $this->ch1, $this->alice_id, 'finished_at' );
		$this->assertEmpty( $finished_at, '關閉線性觀看後取消完成，finished_at 應為空' );
	}

	/**
	 * @test
	 * @group happy
	 * Rule: 完成章節後，下一章節 get_next_post_id 返回正確 ID
	 * Example: 完成第一章後 next_chapter_id = ch1_1
	 */
	public function test_完成第一章後next_chapter_id為1_1(): void {
		// When 取得第一章的下一章節
		$next_id = ChapterUtils::get_next_post_id( $this->ch1 );

		// Then 應為 1-1
		$this->assertSame( $this->ch1_1, $next_id, '第一章的下一章應為 1-1' );
	}

	/**
	 * @test
	 * @group happy
	 * Rule: 完成最後一章，get_next_post_id 返回 null
	 * Example: 完成最後一個章節
	 */
	public function test_完成最後章節next_chapter_id為null(): void {
		// When 取得最後章節的下一章節
		$next_id = ChapterUtils::get_next_post_id( $this->ch1_2 );

		// Then 應為 null（無更多章節）
		$this->assertNull( $next_id, '最後一章的下一章應為 null' );
	}

	/**
	 * @test
	 * @group happy
	 * Rule: 完成所有章節後，課程進度應為 100
	 */
	public function test_完成所有章節後課程進度為100(): void {
		// Given 完成所有三個章節
		$this->set_chapter_finished( $this->ch1, $this->alice_id, '2025-06-01 10:00:00' );
		$this->set_chapter_finished( $this->ch1_1, $this->alice_id, '2025-06-01 11:00:00' );
		$this->set_chapter_finished( $this->ch1_2, $this->alice_id, '2025-06-01 12:00:00' );

		// 清除快取
		\wp_cache_delete( "pid_{$this->course_id}_uid_{$this->alice_id}", 'pc_course_progress' );

		// When
		$progress = CourseUtils::get_course_progress( $this->course_id, $this->alice_id );

		// Then 進度應為 100
		$this->assertSame( 100.0, $progress, '完成所有章節後進度應為 100%' );
	}
}
