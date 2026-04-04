<?php
/**
 * BundleProduct Helper 商品數量 整合測試
 * Feature: specs/features/bundle/銷售方案商品數量.feature
 *
 * @group bundle
 * @group bundle-quantity
 */

declare( strict_types=1 );

namespace Tests\Integration\BundleProduct;

use Tests\Integration\TestCase;
use J7\PowerCourse\BundleProduct\Helper;

/**
 * Class HelperQuantityTest
 * 測試銷售方案商品數量管理邏輯
 *
 * 涵蓋：
 * - get_product_quantities() 無 meta 時回傳空陣列
 * - get_product_quantities() 有 meta 時正確 decode JSON
 * - get_product_qty($product_id) 存在的商品回傳正確數量
 * - get_product_qty($product_id) 不存在的商品回傳 1
 * - set_product_quantities() 正確儲存 JSON
 * - set_product_quantities() 不合法值被 clamp（0→1, 1000→999, -1→1）
 */
class HelperQuantityTest extends TestCase {

	/** @var int 銷售方案（bundle product）ID */
	private int $bundle_product_id;

	/** @var int 課程商品 ID */
	private int $course_id;

	/** @var int 普通商品 ID（Python 講義）*/
	private int $product_200_id;

	/** @var \WC_Product 銷售方案商品 */
	private \WC_Product $bundle_product;

	/** @var Helper 銷售方案 Helper 實例 */
	private Helper $helper;

	/**
	 * 初始化依賴
	 */
	protected function configure_dependencies(): void {
		// 無特殊依賴，直接使用 Helper
	}

	/**
	 * 每個測試前建立測試資料
	 */
	public function set_up(): void {
		parent::set_up();

		// 建立課程商品（courseId=100 對應）
		$this->course_id = $this->create_course(
			[
				'post_title' => 'PHP 基礎課',
				'_is_course' => 'yes',
				'price'      => '3000',
			]
		);

		// 建立普通商品（Python 講義）
		$this->product_200_id = $this->factory()->post->create(
			[
				'post_title'  => 'Python 講義',
				'post_status' => 'publish',
				'post_type'   => 'product',
			]
		);
		\update_post_meta( $this->product_200_id, '_regular_price', '500' );
		\update_post_meta( $this->product_200_id, '_price', '500' );
		\update_post_meta( $this->product_200_id, '_stock_status', 'instock' );
		\update_post_meta( $this->product_200_id, '_manage_stock', 'yes' );
		\update_post_meta( $this->product_200_id, '_stock', '50' );

		// 建立銷售方案（bundle product）
		$this->bundle_product_id = $this->factory()->post->create(
			[
				'post_title'  => '超值學習包',
				'post_status' => 'publish',
				'post_type'   => 'product',
			]
		);
		\update_post_meta( $this->bundle_product_id, 'bundle_type', 'bundle' );
		\update_post_meta( $this->bundle_product_id, Helper::LINK_COURSE_IDS_META_KEY, (string) $this->course_id );
		\update_post_meta( $this->bundle_product_id, '_regular_price', '2000' );
		\update_post_meta( $this->bundle_product_id, '_price', '2000' );

		// 初始化銷售方案商品物件
		$product = \wc_get_product( $this->bundle_product_id );
		$this->assertInstanceOf( \WC_Product::class, $product, '銷售方案商品應可正確取得' );
		$this->bundle_product = $product;

		// 初始化 Helper
		$helper = Helper::instance( $this->bundle_product );
		$this->assertNotNull( $helper, 'Helper::instance() 不應回傳 null' );
		$this->helper = $helper;
	}

	// ========== get_product_quantities() 測試 ==========

	/**
	 * 無 pbp_product_quantities meta 時，get_product_quantities() 應回傳空陣列
	 *
	 * @group happy
	 */
	public function test_get_product_quantities_returns_empty_when_no_meta(): void {
		// 確保 meta 不存在
		\delete_post_meta( $this->bundle_product_id, Helper::PRODUCT_QUANTITIES_META_KEY );

		$quantities = $this->helper->get_product_quantities();

		$this->assertIsArray( $quantities, 'get_product_quantities() 應回傳陣列' );
		$this->assertEmpty( $quantities, '無 meta 時應回傳空陣列' );
	}

	/**
	 * 有 pbp_product_quantities JSON meta 時，get_product_quantities() 應正確 decode
	 *
	 * @group happy
	 */
	public function test_get_product_quantities_decodes_json_correctly(): void {
		$expected = [
			(string) $this->course_id      => 1,
			(string) $this->product_200_id => 3,
		];
		$json     = \wp_json_encode( $expected );

		\update_post_meta( $this->bundle_product_id, Helper::PRODUCT_QUANTITIES_META_KEY, $json );

		$quantities = $this->helper->get_product_quantities();

		$this->assertIsArray( $quantities, 'get_product_quantities() 應回傳陣列' );
		$this->assertSame(
			$expected[ (string) $this->course_id ],
			$quantities[ (string) $this->course_id ] ?? null,
			"課程商品數量應為 1"
		);
		$this->assertSame(
			$expected[ (string) $this->product_200_id ],
			$quantities[ (string) $this->product_200_id ] ?? null,
			"Python 講義數量應為 3"
		);
	}

	// ========== get_product_qty() 測試 ==========

	/**
	 * 有設定數量的商品，get_product_qty() 應回傳正確數量
	 *
	 * @group happy
	 */
	public function test_get_product_qty_returns_correct_qty_when_exists(): void {
		$quantities = [
			(string) $this->product_200_id => 3,
		];
		\update_post_meta(
			$this->bundle_product_id,
			Helper::PRODUCT_QUANTITIES_META_KEY,
			\wp_json_encode( $quantities )
		);

		$qty = $this->helper->get_product_qty( $this->product_200_id );

		$this->assertSame( 3, $qty, "商品 {$this->product_200_id} 的數量應為 3" );
	}

	/**
	 * 未設定數量的商品，get_product_qty() 應回傳 1（預設值）
	 *
	 * @group edge
	 */
	public function test_get_product_qty_returns_1_when_product_not_in_quantities(): void {
		// 設定其他商品的數量，不設定 product_200
		$quantities = [
			(string) $this->course_id => 1,
		];
		\update_post_meta(
			$this->bundle_product_id,
			Helper::PRODUCT_QUANTITIES_META_KEY,
			\wp_json_encode( $quantities )
		);

		$qty = $this->helper->get_product_qty( $this->product_200_id );

		$this->assertSame( 1, $qty, '未設定數量的商品應預設為 1' );
	}

	/**
	 * 無 pbp_product_quantities meta 時，任何商品的 get_product_qty() 應回傳 1
	 *
	 * @group edge
	 */
	public function test_get_product_qty_returns_1_when_no_meta(): void {
		\delete_post_meta( $this->bundle_product_id, Helper::PRODUCT_QUANTITIES_META_KEY );

		$qty = $this->helper->get_product_qty( $this->product_200_id );

		$this->assertSame( 1, $qty, '無 meta 時任何商品數量應預設為 1' );
	}

	// ========== set_product_quantities() 測試 ==========

	/**
	 * set_product_quantities() 應正確儲存 JSON 格式到 meta
	 *
	 * @group happy
	 */
	public function test_set_product_quantities_saves_json_correctly(): void {
		$quantities = [
			(string) $this->course_id      => 1,
			(string) $this->product_200_id => 3,
		];

		$this->helper->set_product_quantities( $quantities );

		$raw = \get_post_meta( $this->bundle_product_id, Helper::PRODUCT_QUANTITIES_META_KEY, true );
		$this->assertIsString( $raw, '儲存後 meta 應為 JSON string' );

		$decoded = \json_decode( $raw, true );
		$this->assertIsArray( $decoded, 'JSON decode 後應為陣列' );
		$this->assertSame(
			1,
			$decoded[ (string) $this->course_id ] ?? null,
			"課程商品數量應儲存為 1"
		);
		$this->assertSame(
			3,
			$decoded[ (string) $this->product_200_id ] ?? null,
			"Python 講義數量應儲存為 3"
		);
	}

	/**
	 * set_product_quantities() 數量 0 應自動修正為 1
	 *
	 * @group edge
	 */
	public function test_set_product_quantities_clamps_zero_to_1(): void {
		$quantities = [
			(string) $this->product_200_id => 0,
		];

		$this->helper->set_product_quantities( $quantities );

		$saved_qty = $this->helper->get_product_qty( $this->product_200_id );
		$this->assertSame( 1, $saved_qty, '數量 0 應自動修正為 1' );
	}

	/**
	 * set_product_quantities() 負數應自動修正為 1
	 *
	 * @group edge
	 */
	public function test_set_product_quantities_clamps_negative_to_1(): void {
		$quantities = [
			(string) $this->product_200_id => -1,
		];

		$this->helper->set_product_quantities( $quantities );

		$saved_qty = $this->helper->get_product_qty( $this->product_200_id );
		$this->assertSame( 1, $saved_qty, '負數應自動修正為 1' );
	}

	/**
	 * set_product_quantities() 數量超過 999 應自動修正為 999
	 *
	 * @group edge
	 */
	public function test_set_product_quantities_clamps_over_limit_to_999(): void {
		$quantities = [
			(string) $this->product_200_id => 1000,
		];

		$this->helper->set_product_quantities( $quantities );

		$saved_qty = $this->helper->get_product_qty( $this->product_200_id );
		$this->assertSame( 999, $saved_qty, '數量 1000 應自動修正為 999' );
	}

	/**
	 * set_product_quantities() 合法數量（1~999）應正確儲存
	 *
	 * @group happy
	 */
	public function test_set_product_quantities_saves_valid_quantity(): void {
		$quantities = [
			(string) $this->product_200_id => 3,
		];

		$this->helper->set_product_quantities( $quantities );

		$saved_qty = $this->helper->get_product_qty( $this->product_200_id );
		$this->assertSame( 3, $saved_qty, '合法數量 3 應正確儲存' );
	}

	/**
	 * set_product_quantities() 空陣列應清空 meta 中的數量
	 *
	 * @group edge
	 */
	public function test_set_product_quantities_saves_empty_array(): void {
		// 先設定一些數量
		\update_post_meta(
			$this->bundle_product_id,
			Helper::PRODUCT_QUANTITIES_META_KEY,
			\wp_json_encode( [ (string) $this->product_200_id => 3 ] )
		);

		// 設定空陣列
		$this->helper->set_product_quantities( [] );

		$quantities = $this->helper->get_product_quantities();
		$this->assertIsArray( $quantities, '結果應為陣列' );
		$this->assertEmpty( $quantities, '空陣列應清空 meta' );
	}

	// ========== PRODUCT_QUANTITIES_META_KEY 常數測試 ==========

	/**
	 * PRODUCT_QUANTITIES_META_KEY 常數應存在且值正確
	 *
	 * @group smoke
	 */
	public function test_product_quantities_meta_key_constant_exists(): void {
		$this->assertSame(
			'pbp_product_quantities',
			Helper::PRODUCT_QUANTITIES_META_KEY,
			"PRODUCT_QUANTITIES_META_KEY 應為 'pbp_product_quantities'"
		);
	}
}
