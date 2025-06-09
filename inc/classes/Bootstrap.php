<?php

declare (strict_types = 1);

namespace J7\PowerCourse;

use Kucrut\Vite;
use J7\WpUtils\Classes\General;
use J7\PowerCourse\Utils\Base;
use J7\PowerCourse\Utils\Course as CourseUtils;
use J7\PowerCourse\BundleProduct\Helper;
use J7\Powerhouse\Settings\Model\Settings;
use J7\Powerhouse\Utils\Base as PowerhouseUtils;



/**
 * Class Bootstrap
 */
final class Bootstrap {
	use \J7\WpUtils\Traits\SingletonTrait;

	const SCHEDULE_ACTION          = 'power_course_schedule_action';
	const SCHEDULE_ACTION_INTERVAL = 10 * MINUTE_IN_SECONDS;

	/** Constructor */
	public function __construct() {
		Compatibility\Compatibility::instance();

		Resources\Chapter\Core\Loader::instance();
		Resources\Order::instance();
		Resources\Comment::instance();
		Resources\Course\LifeCycle::instance();

		Admin\Entry::instance();
		Admin\Product::instance();
		Admin\ProductQuery::instance();

		FrontEnd\MyAccount::instance();

		Api\User::instance();
		Api\Product::instance();
		Api\Course::instance();
		Api\Upload::instance();
		Api\Option::instance();
		Api\Shortcode::instance();
		Api\Comment::instance();
		Api\Reports\Revenue\Api::instance();

		Templates\Templates::instance();
		Templates\Ajax::instance();

		Shortcodes\General::instance();

		PowerEmail\Bootstrap::instance();

		\add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_script' ], 99 );
		\add_action( 'wp_enqueue_scripts', [ $this, 'frontend_enqueue_script' ], 99 );

		// 讓 action scheduler 同時執行的數量增加
		\add_filter( 'action_scheduler_queue_runner_concurrent_batches', fn() => 10 );

		\add_action( 'wp_loaded', [ __CLASS__, 'prevent_guest_checkout' ], 99 );

		// 註冊每5分鐘執行一次的 action scheduler
		\add_action( 'init', [ __CLASS__, 'register_power_course_cron' ] );
	}

	/**
	 * Prevent guest checkout
	 * 當購物車中有課程商品時，不允許訪客結帳
	 *
	 * @return void
	 */
	public static function prevent_guest_checkout(): void {
		// @phpstan-ignore-next-line
		if ( ! \WC() || ! \WC()->cart ) {
			return;
		}
		$cart_items     = \WC()->cart->get_cart_contents();
		$include_course = false;
		foreach ($cart_items as $cart_item) {
			$product_id = (int) ( $cart_item['product_id'] ?? 0 );
			if (!$product_id) {
				continue;
			}

			// 是否為課程商品
			$is_course_product = CourseUtils::is_course_product( $product_id );

			// 是否為銷售方案
			$is_bundle_product = ( Helper::instance( $product_id ) )?->is_bundle_product;

			// 是否有綁開課權限
			$bind_courses_data = \get_post_meta( $product_id, 'bind_courses_data', true );

			if ($is_course_product || $is_bundle_product || $bind_courses_data) {
				// 滿足任何一個條件就避免訪客結帳
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
		if (!General::in_url([ 'power-course' ])) {
			return;
		}
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

		/** @var array<string> $active_plugins */
		$active_plugins = \get_option( 'active_plugins', [] );

		$encrypt_env = PowerhouseUtils::simple_encrypt(
			[
				'SITE_URL'                => \untrailingslashit( \site_url() ),
				'API_URL'                 => \untrailingslashit( \esc_url_raw( rest_url() ) ),
				'CURRENT_USER_ID'         => \get_current_user_id(),
				'CURRENT_POST_ID'         => $post_id,
				'PERMALINK'               => \untrailingslashit( $permalink ),
				'APP_NAME'                => Plugin::$app_name,
				'KEBAB'                   => Plugin::$kebab,
				'SNAKE'                   => Plugin::$snake,
				'BUNNY_LIBRARY_ID'        => Settings::instance()->bunny_library_id,
				'BUNNY_CDN_HOSTNAME'      => Settings::instance()->bunny_cdn_hostname,
				'BUNNY_STREAM_API_KEY'    => Settings::instance()->bunny_stream_api_key,
				'NONCE'                   => \wp_create_nonce( 'wp_rest' ),
				'APP1_SELECTOR'           => Base::APP1_SELECTOR,
				'APP2_SELECTOR'           => Base::APP2_SELECTOR,
				'ELEMENTOR_ENABLED'       => \in_array( 'elementor/elementor.php', $active_plugins, true ), // 檢查 elementor 是否啟用
				/** @deprecated 用 woocommerce API */
				'NOTIFY_LOW_STOCK_AMOUNT' => \get_option( 'woocommerce_notify_low_stock_amount' ),
			]
		);

		\wp_localize_script(
			Plugin::$kebab,
			Plugin::$snake . '_data',
			[
				'env' => $encrypt_env,
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
	 * 註冊每5分鐘執行一次的 action scheduler
	 *
	 * @return void
	 */
	public static function register_power_course_cron(): void {
		if ( !\function_exists( 'as_schedule_recurring_action' ) ) {
			return;
		}

		if ( !\as_next_scheduled_action( self::SCHEDULE_ACTION ) ) {
			\as_schedule_recurring_action( time(), self::SCHEDULE_ACTION_INTERVAL, self::SCHEDULE_ACTION );
		}
	}
}
