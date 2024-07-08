<?php
/**
 * Template for AJAX
 */

declare( strict_types=1 );

namespace J7\PowerCourse\Templates;

use J7\PowerCourse\Plugin;
use J7\WpUtils\Classes\WP;
use J7\PowerCourse\Utils\AVLCourseMeta;


/**
 * Class FrontEnd
 */
final class TemplateAjax {
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
		\add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );

		$actions = $this->actions;
		foreach ( $actions as $action ) {
			\add_action( "wp_ajax_{$action}", [ $this, "{$action}_callback" ] );
			\add_action( "wp_ajax_nopriv_{$action}", [ $this, "{$action}_callback" ] );
		}
	}

	/**
	 * Enqueue assets
	 *
	 * @return void
	 */
	public static function enqueue_assets(): void {
		\wp_enqueue_script(
			Plugin::$kebab . '-template',
			Plugin::$url . '/inc/assets/dist/index.js',
			[ 'jquery' ],
			Plugin::$version,
			[
				'strategy'  => 'async',
				'in_footer' => true,
			]
		);

		\wp_enqueue_style(
			Plugin::$kebab . '-template',
			Plugin::$url . '/inc/assets/dist/css/index.css',
			[],
			Plugin::$version
		);

		\wp_localize_script(
			Plugin::$kebab . '-template',
			'pc_data',
			[
				'ajax_url' => \admin_url('admin-ajax.php'),
				'nonce'    => \wp_create_nonce(Plugin::$snake),
			]
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
		$data       = WP::sanitize_text_field_deep($_POST);
		$chapter_id = (int) $data['data']['chapter_id'];
		$course_id  = (int) $data['data']['course_id'];
		if ( ! $chapter_id || ! $course_id) {
			\wp_send_json_error(
				[
					'code'    => '400',
					'message' => '缺少 chapter ID 或 course ID.',
				]
			);

		}

		AVLCourseMeta::add(
			$course_id,
			\get_current_user_id(),
			'finished_chapter_ids',
			$chapter_id
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

TemplateAjax::instance();
