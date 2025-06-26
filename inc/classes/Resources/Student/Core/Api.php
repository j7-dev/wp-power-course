<?php

declare(strict_types=1);

namespace J7\PowerCourse\Resources\Student\Core;

use J7\PowerCourse\Resources\Student\Helper\ExportCSV;
use J7\WpUtils\Classes\ApiBase;


/** Class Api */
final class Api extends ApiBase {
	use \J7\WpUtils\Traits\SingletonTrait;

	/** @var string 命名空間 */
	protected $namespace = 'power-course';

	/**
	 * APIs
	 *
	 * @var array{endpoint: string, method: string, permission_callback: ?callable}[]
	 * - endpoint: string
	 * - method: 'get' | 'post' | 'patch' | 'delete'
	 * - permission_callback : callable
	 */
	protected $apis = [
		[
			'endpoint'            => 'students/export/(?P<id>\d+)',
			'method'              => 'get',
			'permission_callback' => null,
		],
	];

	/**
	 * 匯出學員名單
	 *
	 * @param \WP_REST_Request $request 包含課程 ID 的 REST 請求對象。
	 * @return \WP_REST_Response
	 */
	public function get_students_export_with_id_callback( \WP_REST_Request $request ): \WP_REST_Response {
		$course_id = (int) $request['id'];
		$export    = new ExportCSV($course_id);
		$export->export();
		return new \WP_REST_Response(
			[
				'code'    => 'get_students_export_success',
				'message' => '匯出成功',
				'data'    => null,
			]
			);
	}
}
