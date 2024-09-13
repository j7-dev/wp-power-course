<?php
/**
 * Bootstrap
 */

declare (strict_types = 1);

namespace J7\PowerCourse;

use J7\PowerCourse\Utils\Base;
use Kucrut\Vite;
use J7\PowerCourse\Utils\Course as CourseUtils;

/**
 * Class Bootstrap
 */
final class Bootstrap {
	use \J7\WpUtils\Traits\SingletonTrait;


	/**
	 * Constructor
	 */
	public function __construct() {
		require_once __DIR__ . '/utils/index.php';
		require_once __DIR__ . '/resources/index.php';
		require_once __DIR__ . '/admin/index.php';
		require_once __DIR__ . '/front-end/index.php';
		require_once __DIR__ . '/api/index.php';
		require_once __DIR__ . '/../templates/index.php';

		\add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_script' ], 99 );
		\add_action( 'wp_enqueue_scripts', [ $this, 'frontend_enqueue_script' ], 99 );

		// 讓 action scheduler 同時執行的數量增加
		\add_filter( 'action_scheduler_queue_runner_concurrent_batches', fn() => 10 );

		\add_action( 'wp_loaded', [ __CLASS__, 'prevent_guest_checkout' ], 99 );
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
			$product_id = $cart_item['product_id'] ?? 0;
			if (!$product_id) {
				continue;
			}
			$is_course_product = CourseUtils::is_course_product( $product_id );
			if ($is_course_product) {
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
		$permalink = \get_permalink( $post_id );

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
					'course_permalink_structure' => \get_option('course_permalink_structure', 'courses'),
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
}
