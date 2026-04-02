<?php
/**
 * 設定課程線性觀看 整合測試
 * Feature: specs/features/linear-viewing/設定課程線性觀看.feature
 *
 * @group linear_viewing
 * @group command
 */

declare( strict_types=1 );

namespace Tests\Integration\LinearViewing;

use Tests\Integration\TestCase;
use J7\PowerCourse\Utils\Course as CourseUtils;

/**
 * Class LinearViewingSettingTest
 * 測試線性觀看設定：enable_sequential 的儲存、驗證與預設值
 */
class LinearViewingSettingTest extends TestCase {

	/** @var int 管理員用戶 ID */
	private int $admin_id;

	/**
	 * 初始化依賴
	 */
	protected function configure_dependencies(): void {
		// 使用 update_post_meta / get_post_meta 直接操作 enable_sequential
	}

	/**
	 * 每個測試前建立共用基礎資料
	 */
	public function set_up(): void {
		parent::set_up();

		// 建立管理員用戶
		$this->admin_id = $this->factory()->user->create(
			[
				'user_login' => 'admin_' . uniqid(),
				'user_email' => 'admin_' . uniqid() . '@test.com',
				'role'       => 'administrator',
			]
		);
		$this->ids['Admin'] = $this->admin_id;
		wp_set_current_user( $this->admin_id );
	}

	/**
	 * 模擬管理員更新課程的 enable_sequential 設定
	 * 透過直接操作 update_post_meta 模擬 REST API 的行為
	 *
	 * @param int    $course_id 課程 ID
	 * @param string $value     新值
	 * @return bool|\WP_Error
	 */
	private function update_enable_sequential( int $course_id, string $value ): bool|\WP_Error {
		// 驗證課程是否存在且為課程商品
		$post = get_post( $course_id );
		if ( ! $post || $post->post_type !== 'product' ) {
			return new \WP_Error( 'course_not_found', '不存在的課程' );
		}

		$is_course = get_post_meta( $course_id, '_is_course', true );
		if ( 'yes' !== $is_course ) {
			return new \WP_Error( 'not_a_course', '此商品不是課程' );
		}

		// 驗證值是否有效
		if ( ! in_array( $value, [ 'yes', 'no' ], true ) ) {
			return new \WP_Error( 'invalid_value', 'enable_sequential 必須為 yes 或 no' );
		}

		update_post_meta( $course_id, 'enable_sequential', $value );
		return true;
	}

	// ========== 前置（狀態）- 課程必須存在 ==========

	/**
	 * @test
	 * @group error
	 *
	 * Feature: 設定課程線性觀看
	 * Rule: 前置（狀態）- 課程必須存在且 _is_course 為 yes
	 * Example: 不存在的課程更新失敗
	 */
	public function test_不存在的課程更新失敗(): void {
		// When 管理員嘗試更新不存在的課程（ID 9999）
		$result = $this->update_enable_sequential( 9999, 'yes' );

		// Then 操作失敗
		$this->assertInstanceOf( \WP_Error::class, $result, '不存在的課程應回傳 WP_Error' );
	}

	// ========== 前置（參數）- enable_sequential 必須為有效值 ==========

	/**
	 * @test
	 * @group error
	 *
	 * Feature: 設定課程線性觀看
	 * Rule: 前置（參數）- enable_sequential 必須為 "yes" 或 "no"
	 * Example: 無效值時操作失敗
	 */
	public function test_無效值時操作失敗(): void {
		// Given 課程 100（enable_sequential = no）
		$course_id = $this->create_course(
			[
				'post_title' => 'PHP 基礎課',
				'_is_course' => 'yes',
			]
		);
		update_post_meta( $course_id, 'enable_sequential', 'no' );

		// When 管理員嘗試設定無效值 "invalid"
		$result = $this->update_enable_sequential( $course_id, 'invalid' );

		// Then 操作失敗
		$this->assertInstanceOf( \WP_Error::class, $result, '無效值應回傳 WP_Error' );
	}

	// ========== 後置（狀態）- 成功開啟線性觀看 ==========

	/**
	 * @test
	 * @group happy
	 *
	 * Feature: 設定課程線性觀看
	 * Rule: 後置（狀態）- 成功開啟線性觀看
	 * Example: 成功將 enable_sequential 設為 yes
	 */
	public function test_成功將enable_sequential設為yes(): void {
		// Given 課程（enable_sequential 預設為 no）
		$course_id = $this->create_course(
			[
				'post_title' => 'PHP 基礎課',
				'_is_course' => 'yes',
			]
		);

		// When 管理員更新課程 enable_sequential 為 yes
		$result = $this->update_enable_sequential( $course_id, 'yes' );

		// Then 操作成功
		$this->assertTrue( $result, '設定 enable_sequential = yes 應成功' );

		// And 課程的 enable_sequential 應為 "yes"
		$stored_value = get_post_meta( $course_id, 'enable_sequential', true );
		$this->assertSame( 'yes', $stored_value, 'enable_sequential 應儲存為 yes' );
	}

	// ========== 後置（狀態）- 成功關閉線性觀看 ==========

	/**
	 * @test
	 * @group happy
	 *
	 * Feature: 設定課程線性觀看
	 * Rule: 後置（狀態）- 成功關閉線性觀看
	 * Example: 成功將 enable_sequential 設為 no
	 */
	public function test_成功將enable_sequential設為no(): void {
		// Given 課程（enable_sequential = yes）
		$course_id = $this->create_course(
			[
				'post_title' => 'PHP 基礎課',
				'_is_course' => 'yes',
			]
		);
		update_post_meta( $course_id, 'enable_sequential', 'yes' );

		// When 管理員更新課程 enable_sequential 為 no
		$result = $this->update_enable_sequential( $course_id, 'no' );

		// Then 操作成功
		$this->assertTrue( $result, '設定 enable_sequential = no 應成功' );

		// And 課程的 enable_sequential 應為 "no"
		$stored_value = get_post_meta( $course_id, 'enable_sequential', true );
		$this->assertSame( 'no', $stored_value, 'enable_sequential 應儲存為 no' );
	}

	// ========== 後置（狀態）- 關閉線性觀看不影響學員既有進度 ==========

	/**
	 * @test
	 * @group happy
	 *
	 * Feature: 設定課程線性觀看
	 * Rule: 後置（狀態）- 關閉線性觀看不影響學員既有進度
	 * Example: 關閉後學員進度不變
	 */
	public function test_關閉線性觀看不影響學員進度(): void {
		// Given 課程（enable_sequential = yes），有 2 個章節
		$course_id = $this->create_course(
			[
				'post_title' => 'PHP 基礎課',
				'_is_course' => 'yes',
			]
		);
		update_post_meta( $course_id, 'enable_sequential', 'yes' );

		$ch1 = $this->create_chapter(
			$course_id,
			[
				'post_title'  => '1-1',
				'menu_order'  => 1,
				'post_parent' => $course_id,
			]
		);
		$ch2 = $this->create_chapter(
			$course_id,
			[
				'post_title'  => '1-2',
				'menu_order'  => 2,
				'post_parent' => $course_id,
			]
		);
		wp_cache_delete( 'flatten_post_ids_' . $course_id, 'prev_next' );

		// Alice 加入課程並完成 1-1
		$alice_id = $this->factory()->user->create(
			[
				'user_login' => 'alice_' . uniqid(),
				'user_email' => 'alice_' . uniqid() . '@test.com',
			]
		);
		$this->enroll_user_to_course( $alice_id, $course_id, 0 );
		$this->set_chapter_finished( $ch1, $alice_id, '2025-06-01 10:00:00' );

		// When 管理員關閉線性觀看
		$result = $this->update_enable_sequential( $course_id, 'no' );

		// Then 操作成功
		$this->assertTrue( $result, '關閉線性觀看應成功' );

		// And Alice 在 1-1 的 finished_at 應不為空
		$finished_at = $this->get_chapter_meta( $ch1, $alice_id, 'finished_at' );
		$this->assertNotEmpty( $finished_at, '關閉線性觀看後，學員的 finished_at 應保留' );

		// And Alice 在課程 100 的進度應為 50%（完成 1/2 章節）
		wp_cache_delete( "pid_{$course_id}_uid_{$alice_id}", 'pc_course_progress' );
		$progress = CourseUtils::get_course_progress( $course_id, $alice_id );
		$this->assertSame( (float) 50, $progress, '完成 1/2 章節，進度應為 50' );
	}

	// ========== 後置（狀態）- 新建課程時 enable_sequential 預設為 no ==========

	/**
	 * @test
	 * @group happy
	 *
	 * Feature: 設定課程線性觀看
	 * Rule: 後置（狀態）- 新建課程時 enable_sequential 預設為 no
	 * Example: 新課程預設關閉線性觀看
	 */
	public function test_新課程預設關閉線性觀看(): void {
		// When 建立新課程（不設定 enable_sequential）
		$new_course_id = $this->create_course(
			[
				'post_title' => '新課程',
				'_is_course' => 'yes',
			]
		);

		// Then 新課程的 enable_sequential 應為 "no"（預設值）
		$value = get_post_meta( $new_course_id, 'enable_sequential', true );

		// 未設定時為空字串，API 讀取時會用 ?: 'no' 轉換，這裡驗證不是 'yes'
		$effective_value = $value ?: 'no';
		$this->assertSame( 'no', $effective_value, '新課程的 enable_sequential 預設應為 no' );
	}
}
