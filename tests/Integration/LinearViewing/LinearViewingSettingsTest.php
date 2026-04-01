<?php
/**
 * 設定課程線性觀看模式 整合測試
 * Feature: specs/features/linear-viewing/設定線性觀看.feature
 *
 * @group linear-viewing
 * @group command
 */

declare( strict_types=1 );

namespace Tests\Integration\LinearViewing;

use Tests\Integration\TestCase;
use J7\PowerCourse\Resources\Chapter\Utils\MetaCRUD as AVLChapterMeta;

/**
 * Class LinearViewingSettingsTest
 * 測試管理員設定課程線性觀看模式的業務邏輯
 */
class LinearViewingSettingsTest extends TestCase {

	/** @var int 管理員用戶 ID */
	private int $admin_id;

	/** @var int 測試課程 ID */
	private int $course_id;

	/**
	 * 初始化依賴
	 */
	protected function configure_dependencies(): void {
		// 直接使用 WordPress APIs 與 update_post_meta
	}

	/**
	 * 每個測試前建立測試資料
	 */
	public function set_up(): void {
		parent::set_up();

		// 建立管理員
		$this->admin_id = $this->factory()->user->create(
			[
				'user_login' => 'admin_' . uniqid(),
				'user_email' => 'admin_' . uniqid() . '@test.com',
				'role'       => 'administrator',
			]
		);
		$this->ids['Admin'] = $this->admin_id;

		// 建立測試課程
		$this->course_id = $this->create_course(
			[
				'post_title'  => 'PHP 基礎課',
				'post_status' => 'publish',
				'_is_course'  => 'yes',
			]
		);
		$this->ids['course_100'] = $this->course_id;
	}

	// ========== 後置（狀態）預設值 ==========

	/**
	 * @test
	 * @group linear-viewing
	 * Feature: 設定課程線性觀看模式
	 * Rule: 後置（狀態）- 預設值為 no（未啟用）
	 * Example: 未設定過的課程預設為關閉
	 */
	public function test_未設定過的課程預設為關閉(): void {
		// When 查詢課程的 enable_linear_viewing
		$value = get_post_meta( $this->course_id, 'enable_linear_viewing', true );

		// Then 值應為 "no" 或空字串（皆視為未啟用）
		$this->assertContains(
			$value,
			[ 'no', '' ],
			'未設定的課程 enable_linear_viewing 應預設為 no 或空字串'
		);
	}

	// ========== 後置（狀態）成功啟用 ==========

	/**
	 * @test
	 * @group linear-viewing
	 * Feature: 設定課程線性觀看模式
	 * Rule: 後置（狀態）- 啟用線性觀看後，課程 product meta 寫入 enable_linear_viewing = yes
	 * Example: 成功啟用線性觀看
	 */
	public function test_成功啟用線性觀看(): void {
		// Given 管理員已登入
		wp_set_current_user( $this->admin_id );

		// When 設定課程的 enable_linear_viewing 為 "yes"
		update_post_meta( $this->course_id, 'enable_linear_viewing', 'yes' );

		// Then 課程的 product meta enable_linear_viewing 應為 "yes"
		$value = get_post_meta( $this->course_id, 'enable_linear_viewing', true );
		$this->assertSame( 'yes', $value, '啟用線性觀看後，meta 值應為 yes' );
	}

	/**
	 * @test
	 * @group linear-viewing
	 * Feature: 設定課程線性觀看模式
	 * Rule: 後置（狀態）- 關閉線性觀看後，課程 product meta 寫入 enable_linear_viewing = no
	 * Example: 成功關閉線性觀看
	 */
	public function test_成功關閉線性觀看(): void {
		// Given 課程的 enable_linear_viewing 已設為 "yes"
		update_post_meta( $this->course_id, 'enable_linear_viewing', 'yes' );
		wp_set_current_user( $this->admin_id );

		// When 設定課程的 enable_linear_viewing 為 "no"
		update_post_meta( $this->course_id, 'enable_linear_viewing', 'no' );

		// Then 課程的 product meta enable_linear_viewing 應為 "no"
		$value = get_post_meta( $this->course_id, 'enable_linear_viewing', true );
		$this->assertSame( 'no', $value, '關閉線性觀看後，meta 值應為 no' );
	}

	// ========== 後置（狀態）關閉不影響已有完成紀錄 ==========

	/**
	 * @test
	 * @group linear-viewing
	 * Feature: 設定課程線性觀看模式
	 * Rule: 後置（狀態）- 關閉線性觀看不影響學員已有的完成紀錄
	 * Example: 關閉後學員的 finished_at 紀錄保留
	 */
	public function test_關閉後學員的finished_at紀錄保留(): void {
		// Given 課程的 enable_linear_viewing 為 "yes"
		update_post_meta( $this->course_id, 'enable_linear_viewing', 'yes' );

		// And 系統中有 Alice 用戶
		$alice_id = $this->factory()->user->create(
			[
				'user_login' => 'alice_' . uniqid(),
				'user_email' => 'alice_' . uniqid() . '@test.com',
			]
		);
		$this->enroll_user_to_course( $alice_id, $this->course_id );

		// And 課程有章節
		$chapter_200 = $this->create_chapter( $this->course_id, [ 'post_title' => '第一章', 'menu_order' => 10 ] );
		$this->create_chapter( $this->course_id, [ 'post_title' => '第二章', 'menu_order' => 20 ] );

		// And 用戶 "Alice" 在章節 200 的 finished_at 為 "2025-06-01 10:00:00"
		$this->set_chapter_finished( $chapter_200, $alice_id, '2025-06-01 10:00:00' );

		// When 關閉線性觀看
		wp_set_current_user( $this->admin_id );
		update_post_meta( $this->course_id, 'enable_linear_viewing', 'no' );

		// Then 章節 finished_at 紀錄應仍然存在
		$finished_at = $this->get_chapter_meta( $chapter_200, $alice_id, 'finished_at' );
		$this->assertSame(
			'2025-06-01 10:00:00',
			$finished_at,
			'關閉線性觀看後，學員的 finished_at 紀錄應保留不受影響'
		);
	}

	// ========== enable_linear_viewing meta 讀寫 ==========

	/**
	 * @test
	 * @group linear-viewing
	 * enable_linear_viewing 可以被正確讀取與寫入
	 */
	public function test_enable_linear_viewing_meta可以正確讀寫(): void {
		// 初始為空或 no
		$initial = get_post_meta( $this->course_id, 'enable_linear_viewing', true );
		$this->assertContains( $initial, [ '', 'no' ], '初始值應為空或 no' );

		// 設為 yes
		update_post_meta( $this->course_id, 'enable_linear_viewing', 'yes' );
		$this->assertSame( 'yes', get_post_meta( $this->course_id, 'enable_linear_viewing', true ) );

		// 設回 no
		update_post_meta( $this->course_id, 'enable_linear_viewing', 'no' );
		$this->assertSame( 'no', get_post_meta( $this->course_id, 'enable_linear_viewing', true ) );
	}
}
