<?php
/**
 * Bootstrap
 */

declare (strict_types = 1);

namespace J7\PowerCourse;

use J7\PowerCourse\Utils\Base;
use Kucrut\Vite;

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
		// 之後要獨立成一個 plugin
		require_once __DIR__ . '/../modules/power-bundle-product/plugin.php';

		\add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_script' ], 99 );
		\add_action( 'wp_enqueue_scripts', [ $this, 'frontend_enqueue_script' ], 99 );
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
					'siteUrl'          => \untrailingslashit( \site_url() ),
					'ajaxUrl'          => \untrailingslashit( \admin_url( 'admin-ajax.php' ) ),
					'userId'           => \wp_get_current_user()->data->ID ?? null,
					'postId'           => $post_id,
					'permalink'        => \untrailingslashit( $permalink ),
					'APP_NAME'         => Plugin::$app_name,
					'KEBAB'            => Plugin::$kebab,
					'SNAKE'            => Plugin::$snake,
					'BASE_URL'         => Base::BASE_URL,
					'APP1_SELECTOR'    => Base::APP1_SELECTOR,
					'APP2_SELECTOR'    => Base::APP2_SELECTOR,
					'API_TIMEOUT'      => Base::API_TIMEOUT,
					'nonce'            => \wp_create_nonce( Plugin::$kebab ),
					'bunny_library_id' => \get_option( 'bunny_library_id', '' ),
					/*
					TODO 下面的資料稍後補上
					{
						libraryId: 244459, // OK
						name: 'cloud luke',
						apiKey: '192d0f46-75b7-4148-8645a8530673-9081-40fb',
						enabledResolutions: ['1080p', '720p', '480p', '360p'],
					}
					 */
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
