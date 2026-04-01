<?php
/**
 * 查詢外部課程列表整合測試
 * Feature: specs/features/external-course/查詢外部課程列表.feature
 *
 * @group external-course
 * @group course
 */

declare( strict_types=1 );

namespace Tests\Integration\Course;

use Tests\Integration\TestCase;

/**
 * Class ExternalCourseQueryTest
 * 測試課程列表 API 混合回傳站內與外部課程、支援 product_type 篩選
 */
class ExternalCourseQueryTest extends TestCase {

	/** @var int 站內課程 100 */
	private int $simple_course_100;

	/** @var int 站內課程 101 */
	private int $simple_course_101;

	/** @var int 外部課程 200 */
	private int $external_course_200;

	/** @var int 外部課程 201 */
	private int $external_course_201;

	/**
	 * 初始化依賴
	 */
	protected function configure_dependencies(): void {
		// 直接使用 WordPress / WooCommerce APIs
	}

	/**
	 * 每個測試前建立測試資料
	 */
	public function set_up(): void {
		parent::set_up();

		// 建立站內課程 100（PHP 基礎課）
		$this->simple_course_100 = $this->create_course(
			[
				'post_title'  => 'PHP 基礎課',
				'post_status' => 'publish',
				'_is_course'  => 'yes',
				'price'       => '1200',
			]
		);

		// 建立站內課程 101（React 實戰課）
		$this->simple_course_101 = $this->create_course(
			[
				'post_title'  => 'React 實戰課',
				'post_status' => 'publish',
				'_is_course'  => 'yes',
				'price'       => '2000',
			]
		);

		// 建立外部課程 200（Python 資料科學）
		$this->external_course_200 = $this->create_external_course(
			[
				'post_title'   => 'Python 資料科學',
				'post_status'  => 'publish',
				'_is_course'   => 'yes',
				'price'        => '2400',
				'external_url' => 'https://hahow.in/courses/12345',
				'button_text'  => '前往 Hahow',
			]
		);

		// 建立外部課程 201（UX 設計入門）
		$this->external_course_201 = $this->create_external_course(
			[
				'post_title'   => 'UX 設計入門',
				'post_status'  => 'draft',
				'_is_course'   => 'yes',
				'external_url' => 'https://pressplay.cc/courses/1',
				'button_text'  => '前往課程',
			]
		);
	}

	// ========== 後置（回應）==========

	/**
	 * Feature: 查詢外部課程列表
	 * Rule: 後置（回應）- 預設查詢回傳所有課程（含外部課程）
	 * Example: 預設查詢混合回傳站內與外部課程
	 *
	 * TODO: [事件風暴部位: Query - GET /courses]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.query 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.readmodel-then 實作 Then
	 */
	public function test_查詢外部課程列表_預設查詢混合回傳站內與外部課程(): void {
		// Given 系統中有站內課程 100（PHP 基礎課）、101（React 實戰課）
		// And 系統中有外部課程 200（Python 資料科學）、201（UX 設計入門）

		// When 管理員 "Admin" 查詢課程列表，參數如下：
		//   | posts_per_page |
		//   | 10             |

		// Then 操作成功
		// And 回應課程數量為 4
		// And 回應中應包含課程 "PHP 基礎課"
		// And 回應中應包含課程 "Python 資料科學"

		$this->markTestIncomplete( '尚未實作' );
	}

	/**
	 * Feature: 查詢外部課程列表
	 * Rule: 後置（回應）- 支援依 product_type 篩選外部課程
	 * Example: 篩選僅外部課程
	 *
	 * TODO: [事件風暴部位: Query - GET /courses?product_type=external]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.query 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.readmodel-then 實作 Then
	 */
	public function test_查詢外部課程列表_篩選僅外部課程(): void {
		// Given 系統中有站內課程 100、101 和外部課程 200、201

		// When 管理員 "Admin" 查詢課程列表，參數如下：
		//   | product_type |
		//   | external     |

		// Then 操作成功
		// And 回應課程數量為 2
		// And 回應中應包含課程 "Python 資料科學"
		// And 回應中應包含課程 "UX 設計入門"

		$this->markTestIncomplete( '尚未實作' );
	}

	/**
	 * Feature: 查詢外部課程列表
	 * Rule: 後置（回應）- 支援依 product_type 篩選外部課程
	 * Example: 篩選僅站內課程
	 *
	 * TODO: [事件風暴部位: Query - GET /courses?product_type=simple]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.query 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.readmodel-then 實作 Then
	 */
	public function test_查詢外部課程列表_篩選僅站內課程(): void {
		// Given 系統中有站內課程 100、101 和外部課程 200、201

		// When 管理員 "Admin" 查詢課程列表，參數如下：
		//   | product_type |
		//   | simple       |

		// Then 操作成功
		// And 回應課程數量為 2
		// And 回應中應包含課程 "PHP 基礎課"
		// And 回應中應包含課程 "React 實戰課"

		$this->markTestIncomplete( '尚未實作' );
	}

	/**
	 * Feature: 查詢外部課程列表
	 * Rule: 後置（回應）- 外部課程回應包含 external_url 與 button_text
	 * Example: 外部課程列表回應含外部連結欄位
	 *
	 * TODO: [事件風暴部位: Query - GET /courses?product_type=external]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.query 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.readmodel-then 實作 Then
	 */
	public function test_查詢外部課程列表_外部課程回應含外部連結欄位(): void {
		// Given 系統中有外部課程 200（Python 資料科學，external_url=https://hahow.in/courses/12345，button_text=前往 Hahow）

		// When 管理員 "Admin" 查詢課程列表，參數如下：
		//   | product_type |
		//   | external     |

		// Then 操作成功
		// And 回應中課程 "Python 資料科學" 的 external_url 應為 "https://hahow.in/courses/12345"
		// And 回應中課程 "Python 資料科學" 的 button_text 應為 "前往 Hahow"

		$this->markTestIncomplete( '尚未實作' );
	}

	/**
	 * Feature: 查詢外部課程列表
	 * Rule: 後置（回應）- 站內課程回應不包含 external_url 欄位（或為空）
	 * Example: 站內課程無外部連結欄位
	 *
	 * TODO: [事件風暴部位: Query - GET /courses?product_type=simple]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.query 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.readmodel-then 實作 Then
	 */
	public function test_查詢外部課程列表_站內課程無外部連結欄位(): void {
		// Given 系統中有站內課程 100（PHP 基礎課）

		// When 管理員 "Admin" 查詢課程列表，參數如下：
		//   | product_type |
		//   | simple       |

		// Then 操作成功
		// And 回應中課程 "PHP 基礎課" 的 external_url 應為空

		$this->markTestIncomplete( '尚未實作' );
	}

	/**
	 * Feature: 查詢外部課程列表
	 * Rule: 後置（回應）- 支援跨類型排序
	 * Example: 依建立日期排序時站內與外部課程混合排列
	 *
	 * TODO: [事件風暴部位: Query - GET /courses?orderby=date&order=DESC]
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.query 實作 When
	 * TODO: 參考 /wp-workflows:aibdd.auto.php.it.handlers.readmodel-then 實作 Then
	 */
	public function test_查詢外部課程列表_依建立日期排序時站內與外部課程混合排列(): void {
		// Given 系統中有站內課程 100、101 和外部課程 200、201

		// When 管理員 "Admin" 查詢課程列表，參數如下：
		//   | orderby | order |
		//   | date    | DESC  |

		// Then 操作成功
		// And 回應課程數量為 4

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
		if ( isset( $args['button_text'] ) ) {
			update_post_meta( $course_id, '_button_text', $args['button_text'] );
		}

		return $course_id;
	}
}
