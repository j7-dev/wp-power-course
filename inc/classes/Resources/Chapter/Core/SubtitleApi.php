<?php
/**
 * 章節字幕 REST API 端點.
 *
 * @package J7\PowerCourse\Resources\Chapter\Core
 */

declare( strict_types=1 );

namespace J7\PowerCourse\Resources\Chapter\Core;

use J7\WpUtils\Classes\ApiBase;
use J7\WpUtils\Traits\SingletonTrait;
use J7\PowerCourse\Resources\Chapter\Service\Subtitle as SubtitleService;

/**
 * 章節字幕 REST API.
 * 提供字幕的 CRUD 端點: GET 列表、POST 上傳、DELETE 刪除.
 */
final class SubtitleApi extends ApiBase {
	use SingletonTrait;

	/**
	 * REST API 命名空間.
	 *
	 * @var string
	 */
	protected $namespace = 'power-course';

	/**
	 * API 路由定義.
	 *
	 * @var array{endpoint:string,method:string,permission_callback?:callable|null,callback?:callable|null}[]
	 */
	protected $apis = array(
		array(
			'endpoint' => 'chapters/(?P<id>\d+)/subtitles',
			'method'   => 'get',
		),
		array(
			'endpoint' => 'chapters/(?P<id>\d+)/subtitles',
			'method'   => 'post',
		),
	);

	/**
	 * Constructor.
	 * 註冊 API 路由與字幕 MIME 類型.
	 */
	public function __construct() {
		parent::__construct();

		// 註冊 DELETE 路由（含 srclang 路徑參數，ApiBase 無法自動轉換，需手動註冊）.
		\add_action(
			'rest_api_init',
			function () {
				\register_rest_route(
					$this->namespace,
					'chapters/(?P<id>\d+)/subtitles/(?P<srclang>[a-zA-Z-]+)',
					array(
						'methods'             => 'DELETE',
						'callback'            => function ( \WP_REST_Request $request ) {
							return $this->try( array( $this, 'delete_subtitle_callback' ), $request );
						},
						'permission_callback' => array( $this, 'permission_callback' ),
					)
				);
			}
		);

		\add_filter( 'upload_mimes', array( $this, 'add_subtitle_mime_types' ) );
	}

	/**
	 * GET 取得章節字幕列表.
	 *
	 * @param \WP_REST_Request $request REST 請求物件.
	 * @return \WP_REST_Response 字幕列表回應.
	 */
	public function get_chapters_with_id_subtitles_callback( $request ): \WP_REST_Response {
		$chapter_id = (int) $request['id'];

		try {
			$service   = new SubtitleService();
			$subtitles = $service->get_subtitles( $chapter_id );

			return new \WP_REST_Response( $subtitles, 200 );
		} catch ( \RuntimeException $e ) {
			return new \WP_REST_Response(
				array(
					'code'    => 'chapter_not_found',
					'message' => $e->getMessage(),
				),
				404
			);
		}
	}

	/**
	 * POST 上傳章節字幕.
	 *
	 * @param \WP_REST_Request $request REST 請求物件.
	 * @return \WP_REST_Response 上傳結果回應.
	 */
	public function post_chapters_with_id_subtitles_callback( $request ): \WP_REST_Response {
		$chapter_id  = (int) $request['id'];
		$file_params = $request->get_file_params();
		$file        = $file_params['file'] ?? null;
		$srclang     = (string) $request->get_param( 'srclang' );

		if ( ! $file || empty( $file['tmp_name'] ) ) {
			return new \WP_REST_Response(
				array(
					'code'    => 'missing_file',
					'message' => '必須提供字幕檔案',
				),
				400
			);
		}

		try {
			$service = new SubtitleService();
			$track   = $service->upload_subtitle(
				$chapter_id,
				(string) $file['tmp_name'],
				(string) $file['name'],
				$srclang
			);

			return new \WP_REST_Response( $track, 201 );
		} catch ( \InvalidArgumentException $e ) {
			return new \WP_REST_Response(
				array(
					'code'    => 'invalid_argument',
					'message' => $e->getMessage(),
				),
				400
			);
		} catch ( \RuntimeException $e ) {
			$message = $e->getMessage();

			if ( str_contains( $message, '不存在' ) ) {
				return new \WP_REST_Response(
					array(
						'code'    => 'chapter_not_found',
						'message' => $message,
					),
					404
				);
			}

			if ( str_contains( $message, '已存在' ) ) {
				return new \WP_REST_Response(
					array(
						'code'    => 'subtitle_exists',
						'message' => $message,
					),
					422
				);
			}

			return new \WP_REST_Response(
				array(
					'code'    => 'upload_error',
					'message' => $message,
				),
				500
			);
		}
	}

	/**
	 * DELETE 刪除章節字幕.
	 *
	 * @param \WP_REST_Request $request REST 請求物件.
	 * @return \WP_REST_Response 刪除結果回應.
	 */
	public function delete_subtitle_callback( \WP_REST_Request $request ): \WP_REST_Response {
		$chapter_id = (int) $request['id'];
		$srclang    = (string) $request['srclang'];

		try {
			$service = new SubtitleService();
			$service->delete_subtitle( $chapter_id, $srclang );

			return new \WP_REST_Response(
				array( 'deleted' => true ),
				200
			);
		} catch ( \InvalidArgumentException $e ) {
			return new \WP_REST_Response(
				array(
					'code'    => 'invalid_argument',
					'message' => $e->getMessage(),
				),
				400
			);
		} catch ( \RuntimeException $e ) {
			return new \WP_REST_Response(
				array(
					'code'    => 'not_found',
					'message' => $e->getMessage(),
				),
				404
			);
		}
	}

	/**
	 * 註冊字幕檔案的 MIME 類型.
	 *
	 * @param array<string, string> $mimes 現有的 MIME 類型列表.
	 * @return array<string, string> 擴充後的 MIME 類型列表.
	 */
	public function add_subtitle_mime_types( array $mimes ): array {
		$mimes['srt'] = 'application/x-subrip';
		$mimes['vtt'] = 'text/vtt';

		return $mimes;
	}
}
