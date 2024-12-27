<?php
/**
 * Template for AJAX
 */

declare( strict_types=1 );

namespace J7\PowerCourse\Templates;

use J7\PowerCourse\Plugin;
use J7\WpUtils\Classes\WP;
use J7\PowerCourse\Resources\Course\MetaCRUD as AVLCourseMeta;
use J7\PowerCourse\Resources\Chapter\MetaCRUD as AVLChapterMeta;
use J7\PowerCourse\Utils\Course as CourseUtils;




/**
 * Class Ajax
 */
final class Ajax {
	use \J7\WpUtils\Traits\SingletonTrait;

	/**
	 * Ajax Actions
	 *
	 * @var string[]
	 */
	public $actions = [ 'finish_chapter' ];

	/**
	 * Constructor
	 */
	public function __construct() {
		\add_action( 'wp_enqueue_scripts', [ __CLASS__, 'wp_enqueue_scripts' ], -10 );
		\add_action( 'admin_enqueue_scripts', [ __CLASS__, 'admin_enqueue_scripts' ] );

		$actions = $this->actions;
		foreach ( $actions as $action ) {
			\add_action( "wp_ajax_{$action}", [ $this, "{$action}_callback" ] ); // @phpstan-ignore-line
			\add_action( "wp_ajax_nopriv_{$action}", [ $this, "{$action}_callback" ] ); // @phpstan-ignore-line
		}
	}

	/**
	 * Enqueue assets
	 *
	 * @return void
	 */
	public static function wp_enqueue_scripts(): void {
		\wp_enqueue_script( 'wc-add-to-cart' );
		\wp_enqueue_script(
			Plugin::$kebab . '-template',
			Plugin::$url . '/inc/assets/dist/index.js',
			[ 'jquery' ],
			Plugin::$version,
			[
				'strategy' => 'defer',
			]
		);

		$is_avl = CourseUtils::is_avl();

		\wp_localize_script(
			Plugin::$kebab . '-template',
			'pc_data',
			[
				'ajax_url'                  => \admin_url('admin-ajax.php'),
				'nonce'                     => \wp_create_nonce( 'wp_rest' ),
				'is_avl'                    => $is_avl,
				'header_offset'             => \get_option('pc_header_offset', 0),
				'fix_video_and_tabs_mobile' => \get_option('fix_video_and_tabs_mobile', 'yes'),
			]
			);

		\wp_enqueue_style(
			Plugin::$kebab . '-template',
			Plugin::$url . '/inc/assets/dist/css/index.css',
			[],
			Plugin::$version
		);

		// \wp_enqueue_style(
		// 'blocknote-mantine',
		// Plugin::$url . '/inc/assets/dist/css/BlockNote-mantine.css',
		// [],
		// Plugin::$version
		// );
	}

	/**
	 * Enqueue assets
	 *
	 * @return void
	 */
	public static function admin_enqueue_scripts(): void {
		$screen = \get_current_screen();

		if ('shop_order' !== $screen?->id) {
			return;
		}

		\wp_enqueue_style(
			Plugin::$kebab . '-template',
			Plugin::$url . '/inc/assets/dist/css/index.css',
			[],
			Plugin::$version
		);
	}

	/**
	 * Finish Chapter Callback
	 *
	 * @return void
	 */
	public function finish_chapter_callback(): void {
		// 確保請求來自有效的來源
		\check_ajax_referer(Plugin::$snake, 'security');

		// 獲取從前端發送的數據
		$data = WP::sanitize_text_field_deep($_POST);

		// @phpstan-ignore-next-line
		$chapter_id = (int) @$data['data']['chapter_id'];

		if ( ! $chapter_id) {
			\wp_send_json_error(
				[
					'code'    => '400',
					'message' => '缺少 chapter ID 或 course ID.',
				]
			);

		}

		AVLChapterMeta::add(
			(int) $chapter_id,
			\get_current_user_id(),
			'finished_at',
			\wp_date('Y-m-d H:i:s')
		);

		// 發送回應
		\wp_send_json_success(
			[
				'code'    => '200',
				'message' => '章節已完成',
			]
		);

		// 重要：總是在 AJAX 函數結束時調用 wp_die()
		// @phpstan-ignore-next-line
		\wp_die();
	}
}
