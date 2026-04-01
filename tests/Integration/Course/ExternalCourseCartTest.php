<?php
/**
 * 外部課程購物車阻擋整合測試
 * Feature: specs/features/external-course/外部課程購物車阻擋.feature
 *
 * @group external-course
 * @group course
 */

declare( strict_types=1 );

namespace Tests\Integration\Course;

use Tests\Integration\TestCase;

/**
 * Class ExternalCourseCartTest
 * 測試外部課程（WC_Product_External）天然不可購買
 */
class ExternalCourseCartTest extends TestCase {

	/** @var int 外部課程 ID */
	private int $external_course_id;

	/** @var int 站內課程 ID */
	private int $simple_course_id;

	/**
	 * 初始化依賴
	 */
	protected function configure_dependencies(): void {
		// 直接使用 WooCommerce APIs
	}

	/**
	 * 每個測試前建立測試資料
	 */
	public function set_up(): void {
		parent::set_up();

		// 建立外部課程 200（Python 資料科學）
		$this->external_course_id = $this->create_external_course(
			[
				'post_title'   => 'Python 資料科學',
				'post_status'  => 'publish',
				'_is_course'   => 'yes',
				'external_url' => 'https://hahow.in/courses/12345',
			]
		);

		// 建立站內課程 100（PHP 基礎課）
		$this->simple_course_id = $this->create_course(
			[
				'post_title'  => 'PHP 基礎課',
				'post_status' => 'publish',
				'_is_course'  => 'yes',
				'price'       => '1200',
			]
		);
	}

	// ========== 購物車阻擋 ==========

	/**
	 * Feature: 外部課程購物車阻擋
	 * Rule: 外部課程不可被加入購物車（WC_Product_External::is_purchasable = false）
	 * Example: 透過 WC AJAX 加入購物車被阻擋
	 *
	 * TODO: [事件風暴部位: Constraint - WC_Product_External::is_purchasable]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.command 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.aggregate-then 實作 Then
	 */
	public function test_外部課程購物車阻擋_外部課程is_purchasable為false(): void {
		// Given 外部課程 200（Python 資料科學）存在

		// When 取得課程 200 的 WC_Product 物件

		// Then WC_Product_External::is_purchasable() 應為 false

		$this->markTestIncomplete( '尚未實作' );
	}

	/**
	 * Feature: 外部課程購物車阻擋
	 * Rule: 外部課程不可被加入購物車（WC_Product_External::is_purchasable = false）
	 * Example: 透過 WC AJAX 加入購物車被阻擋
	 *
	 * TODO: [事件風暴部位: Constraint - WC cart add_to_cart]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.command 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.aggregate-then 實作 Then
	 */
	public function test_外部課程購物車阻擋_AJAX加入購物車被阻擋(): void {
		// Given 外部課程 200（Python 資料科學）存在

		// When 訪客嘗試以 AJAX 方式加入課程 200 到購物車

		// Then 操作失敗
		// And 課程 200 不在購物車中

		$this->markTestIncomplete( '尚未實作' );
	}

	/**
	 * Feature: 外部課程購物車阻擋
	 * Rule: 外部課程不可被加入購物車（WC_Product_External::is_purchasable = false）
	 * Example: 透過 URL 參數加入購物車被阻擋
	 *
	 * TODO: [事件風暴部位: Constraint - WC add-to-cart URL param]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.command 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.aggregate-then 實作 Then
	 */
	public function test_外部課程購物車阻擋_URL參數加入購物車被阻擋(): void {
		// Given 外部課程 200（Python 資料科學）存在

		// When 訪客嘗試以 URL 參數 "?add-to-cart=200" 加入購物車

		// Then 課程 200 不在購物車中
		// And WooCommerce 應顯示無法購買提示

		$this->markTestIncomplete( '尚未實作' );
	}

	/**
	 * Feature: 外部課程購物車阻擋
	 * Rule: 外部課程不出現在 WooCommerce 結帳流程
	 * Example: 購物車中不會有外部課程
	 *
	 * TODO: [事件風暴部位: Constraint - WC checkout]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.aggregate-given 實作 Given
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.readmodel-then 實作 Then
	 */
	public function test_外部課程購物車阻擋_外部課程不出現在結帳流程(): void {
		// Given 訪客購物車中有站內課程 100

		// When 訪客瀏覽結帳頁

		// Then 結帳頁不應包含課程 200

		$this->markTestIncomplete( '尚未實作' );
	}

	// ========== Helper Methods ==========

	/**
	 * 建立外部課程（WC_Product_External）
	 *
	 * @param array<string, mixed> $args 課程參數
	 * @return int 課程 ID
	 */
	private function create_external_course( array $args = [] ): int {
		$course_id = $this->create_course( $args );

		// 設定為 external 產品類型
		\wp_set_object_terms( $course_id, 'external', 'product_type' );

		if ( isset( $args['external_url'] ) ) {
			update_post_meta( $course_id, '_product_url', $args['external_url'] );
		}

		return $course_id;
	}
}
