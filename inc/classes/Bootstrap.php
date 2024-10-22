<?php
/**
 * Bootstrap
 */

declare (strict_types = 1);

namespace J7\PowerCourse;

use J7\WpUtils\Classes\Meta;
use J7\PowerCourse\Utils\Base;
use Kucrut\Vite;
use J7\PowerCourse\Utils\Course as CourseUtils;
use J7\PowerCourse\BundleProduct\BundleProduct;



/**
 * Class Bootstrap
 */
final class Bootstrap {
	use \J7\WpUtils\Traits\SingletonTrait;
	use \J7\WpUtils\Traits\ContainerTrait;

	const AS_COMPATIBILITY_HOOK = 'pc_compatibility_action_scheduler';

	/**
	 * Constructor
	 */
	public function __construct() {
		Resources\Chapter\CPT::instance();
		Resources\Order::instance();
		Resources\Comment::instance();
		Resources\Course::instance();

		Admin\Entry::instance();
		Admin\Product::instance();
		Admin\ProductQuery::instance();

		FrontEnd\MyAccount::instance();

		Api\User::instance();
		Api\Product::instance();
		Api\Course::instance();
		Api\Chapter::instance();
		Api\Upload::instance();
		Api\Option::instance();
		Api\Comment::instance();

		Templates\Templates::instance();
		Templates\Ajax::instance();

		\add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_script' ], 99 );
		\add_action( 'wp_enqueue_scripts', [ $this, 'frontend_enqueue_script' ], 99 );

		// 讓 action scheduler 同時執行的數量增加
		\add_filter( 'action_scheduler_queue_runner_concurrent_batches', fn() => 10 );

		\add_action( 'wp_loaded', [ __CLASS__, 'prevent_guest_checkout' ], 99 );

		// 排程只執行一次的兼容設定
		\add_action( 'init', [ __CLASS__, 'compatibility_action_scheduler' ] );
		\add_action( self::AS_COMPATIBILITY_HOOK, [ __CLASS__, 'compatibility' ]);
	}


	/**
	 * Prevent guest checkout
	 * 當購物車中有課程商品時，不允許訪客結帳
	 *
	 * @return void
	 */
	public static function prevent_guest_checkout(): void {

		if ( ! \WC() || ! \WC()?->cart ) {
			return;
		}
		$cart_items     = \WC()->cart->get_cart_contents();
		$include_course = false;
		foreach ($cart_items as $cart_item) {
			$product_id = (int) ( $cart_item['product_id'] ?? 0 );
			if (!$product_id) {
				continue;
			}
			$is_course_product = CourseUtils::is_course_product( $product_id );
			$is_bundle_product = BundleProduct::is_bundle_product( $product_id );

			if ($is_course_product || $is_bundle_product) {
				$include_course = true;
				break;
			}
		}

		if (!$include_course) {
			return;
		}

		// 不允許訪客結帳
		$prevent_guest_checkout_options = [
			'woocommerce_enable_guest_checkout'          => 'no',
			'woocommerce_enable_checkout_login_reminder' => 'yes',
			'woocommerce_enable_signup_and_login_from_checkout' => 'yes',
			'woocommerce_registration_generate_password' => 'no',
		];
		foreach ($prevent_guest_checkout_options as $option_name => $option_value) {
			\add_filter("option_{$option_name}", fn() => $option_value, 99);
		}
	}



	/**
	 * Admin Enqueue script
	 * You can load the script on demand
	 *
	 * @param string $hook current page hook
	 *
	 * @return void
	 */
	public function admin_enqueue_script( $hook ): void {
		self::enqueue_script();
	}


	/**
	 * Front-end Enqueue script
	 * You can load the script on demand
	 *
	 * @return void
	 */
	public function frontend_enqueue_script(): void {
		self::enqueue_script();
	}

	/**
	 * Enqueue script
	 * You can load the script on demand
	 *
	 * @return void
	 */
	public static function enqueue_script(): void {
		Vite\enqueue_asset(
			Plugin::$dir . '/js/dist',
			'js/src/main.tsx',
			[
				'handle'    => Plugin::$kebab,
				'in-footer' => true,
			]
		);

		$post_id   = \get_the_ID();
		$permalink = $post_id ? \get_permalink( $post_id ) : '';

		\wp_localize_script(
			Plugin::$kebab,
			Plugin::$snake . '_data',
			[
				'env' => [
					'siteUrl'                    => \untrailingslashit( \site_url() ),
					'ajaxUrl'                    => \untrailingslashit( \admin_url( 'admin-ajax.php' ) ),
					'userId'                     => \wp_get_current_user()->data->ID ?? null,
					'postId'                     => $post_id,
					'permalink'                  => \untrailingslashit( $permalink ),
					'course_permalink_structure' => CourseUtils::get_course_permalink_structure(),
					'APP_NAME'                   => Plugin::$app_name,
					'KEBAB'                      => Plugin::$kebab,
					'SNAKE'                      => Plugin::$snake,
					'BASE_URL'                   => Base::BASE_URL,
					'APP1_SELECTOR'              => Base::APP1_SELECTOR,
					'APP2_SELECTOR'              => Base::APP2_SELECTOR,
					'API_TIMEOUT'                => Base::API_TIMEOUT,
					'nonce'                      => \wp_create_nonce( Plugin::$kebab ),
					'bunny_library_id'           => \get_option( 'bunny_library_id', '' ),
					'bunny_cdn_hostname'         => \get_option( 'bunny_cdn_hostname', '' ),
					'bunny_stream_api_key'       => \get_option( 'bunny_stream_api_key', '' ),
				],
			]
		);

		\wp_localize_script(
			Plugin::$kebab,
			'wpApiSettings',
			[
				'root'  => \untrailingslashit( \esc_url_raw( rest_url() ) ),
				'nonce' => \wp_create_nonce( 'wp_rest' ),
			]
		);
	}

	/**
	 * 排程只執行一次的兼容設定
	 *
	 * @return void
	 */
	public static function compatibility_action_scheduler(): void {
		$scheduled_version = \get_option('pc_compatibility_action_scheduled');
		if ($scheduled_version === Plugin::$version) {
			return;
		}
		\as_enqueue_async_action( self::AS_COMPATIBILITY_HOOK, [] );
	}

	/**
	 * 執行排程
	 *
	 * @return void
	 */
	public static function compatibility(): void {
		$args = [
			'post_type'    => 'product',
			'numberposts'  => -1,
			'meta_key'     => BundleProduct::INCLUDE_PRODUCT_IDS_META_KEY,
			'meta_compare' => 'EXISTS',
			'fields'       => 'ids',
		];
		// 找出所有的銷售方案
		$product_ids = \get_posts($args);

		foreach ($product_ids as $product_id) {
			// 1. 找出所有的銷售方案，加上 bundle_type_label
			\update_post_meta($product_id, 'bundle_type_label', '合購優惠');

			// 找出所有銷售方案包含商品 ids
			$pbp_product_ids = (array) \get_post_meta($product_id, BundleProduct::INCLUDE_PRODUCT_IDS_META_KEY);

			$course_ids = [];
			foreach ($pbp_product_ids as $pbp_product_id) {
				// 找出課程商品 id
				if (CourseUtils::is_course_product( (int) $pbp_product_id)) {
					$course_ids[] = $pbp_product_id;
				}
			}
			$product = new Meta( (int) $product_id);

			$product->add_array('link_course_ids', $course_ids);
		}

		// 註記已經執行過相容設定
		\update_option('pc_compatibility_action_scheduled', Plugin::$version);
	}
}