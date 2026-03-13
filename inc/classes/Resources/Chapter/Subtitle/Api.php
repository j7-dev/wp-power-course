<?php

declare(strict_types=1);

namespace J7\PowerCourse\Resources\Chapter\Subtitle;

use J7\WpUtils\Classes\ApiBase;
use J7\PowerCourse\Resources\Chapter\Core\CPT as ChapterCPT;

/**
 * 章節字幕 API
 */
final class Api extends ApiBase
{
	use \J7\WpUtils\Traits\SingletonTrait;

	/**
	 * 語言標籤映射
	 *
	 * @var array<string, string>
	 */
	private const LANGUAGE_LABELS = [
		'zh-TW' => '繁體中文',
		'zh-CN' => '简体中文',
		'en'    => 'English',
		'ja'    => '日本語',
		'ko'    => '한국어',
		'th'    => 'ไทย',
		'vi'    => 'Tiếng Việt',
		'fr'    => 'Français',
		'de'    => 'Deutsch',
		'es'    => 'Español',
		'pt'    => 'Português',
		'id'    => 'Bahasa Indonesia',
		'ms'    => 'Bahasa Melayu',
		'ar'    => 'العربية',
	];

	/**
	 * 命名空間
	 *
	 * @var string
	 */
	protected $namespace = 'power-course';

	/**
	 * APIs
	 *
	 * @var array{endpoint: string, method: string, permission_callback: ?callable}[]
	 */
	protected $apis = [
		[
			'endpoint'            => 'chapters/(?P<id>\d+)/subtitles',
			'method'              => 'get',
			'permission_callback' => null,
		],
		[
			'endpoint'            => 'chapters/(?P<id>\d+)/subtitles',
			'method'              => 'post',
			'permission_callback' => null,
		],
		[
			'endpoint'            => 'chapters/(?P<id>\d+)/subtitles/(?P<srclang>[a-zA-Z\-]+)',
			'method'              => 'delete',
			'permission_callback' => null,
		],
	];

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
		\add_filter('upload_mimes', [$this, 'allow_subtitle_mimes']);
	}

	/**
	 * 允許字幕檔案 MIME 類型
	 *
	 * @param array<string, string> $mimes 原始 MIME 類型
	 * @return array<string, string>
	 */
	public function allow_subtitle_mimes(array $mimes): array
	{
		$mimes['vtt'] = 'text/vtt';
		$mimes['srt'] = 'application/x-subrip';
		return $mimes;
	}

	/**
	 * 取得章節字幕列表
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_chapters_with_id_subtitles_callback(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
	{
		$chapter_id = (int) $request['id'];
		$chapter    = $this->get_chapter_or_null($chapter_id);
		if (!$chapter) {
			return new \WP_Error(
				'rest_not_found',
				'章節不存在',
				[
					'status' => 404,
				]
			);
		}

		$subtitles = \get_post_meta($chapter_id, 'chapter_subtitles', true);
		$subtitles = \is_array($subtitles) ? $subtitles : [];

		return new \WP_REST_Response($subtitles);
	}

	/**
	 * 上傳章節字幕
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function post_chapters_with_id_subtitles_callback(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
	{
		$chapter_id = (int) $request['id'];
		$chapter    = $this->get_chapter_or_null($chapter_id);
		if (!$chapter) {
			return new \WP_Error(
				'rest_not_found',
				'章節不存在',
				[
					'status' => 404,
				]
			);
		}

		$body_params = $request->get_body_params();
		$file_params = $request->get_file_params();
		$srclang_raw = (string) ($body_params['srclang'] ?? '');
		$srclang     = $this->normalize_srclang(\sanitize_text_field($srclang_raw));

		if (!$srclang) {
			return new \WP_Error(
				'rest_invalid_param',
				'必須指定字幕語言',
				[
					'status' => 400,
				]
			);
		}

		if (!isset(self::LANGUAGE_LABELS[$srclang])) {
			return new \WP_Error(
				'rest_invalid_param',
				'無效的語言代碼',
				[
					'status' => 400,
				]
			);
		}

		/** @var array{name?: string, type?: string, tmp_name?: string, error?: int, size?: int}|null $file */
		$file = \is_array($file_params['file'] ?? null) ? $file_params['file'] : null;
		if (!$file || empty($file['tmp_name'])) {
			return new \WP_Error(
				'rest_invalid_param',
				'必須提供字幕檔案',
				[
					'status' => 400,
				]
			);
		}

		$original_name = (string) ($file['name'] ?? '');
		$extension     = \strtolower((string) \pathinfo($original_name, PATHINFO_EXTENSION));
		if (!\in_array($extension, ['srt', 'vtt'], true)) {
			return new \WP_Error(
				'rest_invalid_param',
				'僅支援 .srt 和 .vtt 格式',
				[
					'status' => 400,
				]
			);
		}

		$subtitles = \get_post_meta($chapter_id, 'chapter_subtitles', true);
		$subtitles = \is_array($subtitles) ? $subtitles : [];
		$srclang_list = \array_map(
			fn($subtitle) => $this->normalize_srclang((string) ($subtitle['srclang'] ?? '')),
			$subtitles
		);
		if (\in_array($srclang, $srclang_list, true)) {
			return new \WP_Error(
				'rest_forbidden',
				'該語言字幕已存在，請先刪除再上傳',
				[
					'status' => 422,
				]
			);
		}

		if (!\function_exists('media_handle_upload')) {
			/** @var string $abspath */
			$abspath = ABSPATH;
			require_once $abspath . 'wp-admin/includes/image.php';
			require_once $abspath . 'wp-admin/includes/file.php';
			require_once $abspath . 'wp-admin/includes/media.php';
		}


		if ('srt' === $extension) {
			$srt_content = \file_get_contents((string) $file['tmp_name']);
			if ($srt_content === false) {
				return new \WP_Error(
					'rest_invalid_param',
					'讀取字幕檔案失敗',
					[
						'status' => 400,
					]
				);
			}

			$vtt_content = $this->convert_srt_to_vtt($srt_content);
			$temp_vtt_file = \wp_tempnam($original_name);

			$write_result = \file_put_contents((string) $file['tmp_name'], $vtt_content);
			if ($write_result === false) {
				return new \WP_Error(
					'rest_upload_unknown_error',
					'寫入暫存檔案失敗',
					[
						'status' => 500,
					]
				);
			}
			$file['name'] = (string) \preg_replace('/\.srt$/i', '.vtt', $original_name);
			$file['type'] = 'text/vtt';
			$file['size'] = \strlen($vtt_content);
		}

		$original_subtitle_file = $_FILES['subtitle_file'] ?? null;
		try {
			$_FILES['subtitle_file'] = $file;
			$attachment_id           = \media_handle_upload('subtitle_file', $chapter_id);
		} finally {
			if (null !== $original_subtitle_file) {
				$_FILES['subtitle_file'] = $original_subtitle_file;
			} else {
				unset($_FILES['subtitle_file']);
			}
			if ($temp_vtt_file && \file_exists($temp_vtt_file)) {
				\unlink($temp_vtt_file);
			}
		}


		if (\is_wp_error($attachment_id)) {
			return new \WP_Error(
				'rest_upload_unknown_error',
				$attachment_id->get_error_message(),
				[
					'status' => 400,
				]
			);
		}

		$subtitle_data = [
			'srclang'       => $srclang,
			'label'         => self::LANGUAGE_LABELS[$srclang],
			'url'           => (string) \wp_get_attachment_url($attachment_id),
			'attachment_id' => (int) $attachment_id,
		];

		$subtitles[] = $subtitle_data;
		\update_post_meta($chapter_id, 'chapter_subtitles', $subtitles);

		return new \WP_REST_Response($subtitle_data, 200);
	}

	/**
	 * 刪除章節字幕
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function delete_chapters_with_id_subtitles_with_srclang_callback(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
	{
		$chapter_id = (int) $request['id'];
		$chapter    = $this->get_chapter_or_null($chapter_id);
		if (!$chapter) {
			return new \WP_Error(
				'rest_not_found',
				'章節不存在',
				[
					'status' => 404,
				]
			);
		}

		$srclang = $this->normalize_srclang(\sanitize_text_field((string) $request['srclang']));
		if (!$srclang) {
			return new \WP_Error(
				'rest_invalid_param',
				'srclang 為必填',
				[
					'status' => 400,
				]
			);
		}

		$subtitles = \get_post_meta($chapter_id, 'chapter_subtitles', true);
		$subtitles = \is_array($subtitles) ? $subtitles : [];

		$delete_index = null;
		$delete_item  = null;
		foreach ($subtitles as $index => $subtitle) {
			$current_srclang = $this->normalize_srclang((string) ($subtitle['srclang'] ?? ''));
			if ($current_srclang !== $srclang) {
				continue;
			}
			$delete_index = $index;
			$delete_item  = $subtitle;
			break;
		}

		if ($delete_index === null || !\is_array($delete_item)) {
			return new \WP_Error(
				'rest_not_found',
				'該語言字幕不存在',
				[
					'status' => 404,
				]
			);
		}

		$attachment_id = (int) ($delete_item['attachment_id'] ?? 0);
		if ($attachment_id > 0) {
			\wp_delete_attachment($attachment_id, true);
		}

		unset($subtitles[$delete_index]);
		$subtitles = \array_values($subtitles);
		\update_post_meta($chapter_id, 'chapter_subtitles', $subtitles);

		return new \WP_REST_Response(
			[
				'success'         => true,
				'deleted_srclang' => $srclang,
			],
			200
		);
	}

	/**
	 * 取得章節資料
	 *
	 * @param int $chapter_id 章節 ID
	 * @return \WP_Post|null
	 */
	private function get_chapter_or_null(int $chapter_id): ?\WP_Post
	{
		$chapter = \get_post($chapter_id);
		if (!$chapter instanceof \WP_Post) {
			return null;
		}
		if (ChapterCPT::POST_TYPE !== $chapter->post_type) {
			return null;
		}
		return $chapter;
	}

	/**
	 * 正規化語言代碼
	 *
	 * @param string $srclang 原始語言代碼
	 * @return string
	 */
	private function normalize_srclang(string $srclang): string
	{
		$srclang = \trim($srclang);
		if (!$srclang) {
			return '';
		}

		foreach (\array_keys(self::LANGUAGE_LABELS) as $allowed_srclang) {
			if (\strtolower($allowed_srclang) === \strtolower($srclang)) {
				return $allowed_srclang;
			}
		}

		return $srclang;
	}

	/**
	 * 將 SRT 內容轉為 WebVTT
	 *
	 * @param string $srt_content SRT 原始內容
	 * @return string
	 */
	private function convert_srt_to_vtt(string $srt_content): string
	{
		$content = \str_replace(["\r\n", "\r"], "\n", $srt_content);

		$without_indexes = \preg_replace(
			'/^\d+\n(?=\d{2}:\d{2}:\d{2}[,.]\d{3}\s+-->\s+\d{2}:\d{2}:\d{2}[,.]\d{3})/m',
			'',
			$content
		);
		$content = \is_string($without_indexes) ? $without_indexes : $content;

		$converted_timestamps = \preg_replace(
			'/(\d{2}:\d{2}:\d{2}),(\d{3})/',
			'$1.$2',
			$content
		);
		$content = \is_string($converted_timestamps) ? $converted_timestamps : $content;

		return "WEBVTT\n\n{$content}";
	}
}
