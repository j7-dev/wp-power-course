<?php

declare (strict_types = 1);

namespace J7\PowerCourse\Resources\Student\Helper;

use J7\Powerhouse\Utils\ExportCSV as ExportCSVBase;

/**
 * ExportCSV 匯出 CSV
 * 使用方式
 * 1. 定義 $rows 資料源
 * 2. 定義 $filename, $columns
 * 3. 呼叫 export()
 * */
final class ExportCSV extends ExportCSVBase {

	/** @var string 檔案名稱 */
	protected string $filename;

	/** @var array<object> 資料 */
	protected array $rows;

	/** @var array<string, string> 欄位名稱，預設會從 $row 身上拿屬性 */
	protected array $columns = [];

	/** @var string 課程名稱 */
	private string $course_name;

	/** @var int 課程 ID */
	public function __construct( private int $course_id ) {
		$this->course_name = \get_the_title($this->course_id);
		$this->filename    = "{$this->course_name}學員名單";

		$this->rows = $this->get_rows();

		$this->columns = [
			'user_id'      => '學員 ID',
			'display_name' => '學員名稱',
			'user_email'   => '學員 Email',
			'course_name'  => '課程名稱',
			'course_id'    => '課程 ID',
			'progress'     => '學習進度',
		];
	}

	/**
	 * 取得學員資料
	 *
	 * @return array{user_id: int, display_name: string, user_email: string, course_name: string, course_id: int, progress: string}
	 * */
	private function get_rows(): array {
		$query = new Query(
			[
				'posts_per_page' => -1,
				'meta_key'       => 'avl_course_ids',
				'meta_value'     => $this->course_id,
			]
			);

		$users = $query->get_users();

		$rows = [];

		foreach ($users as $user) {
			$rows[] = (object) [
				'user_id'      => $user->ID,
				'display_name' => $user->display_name,
				'user_email'   => $user->user_email,
				'course_name'  => $this->course_name,
				'course_id'    => $this->course_id,
				'progress'     => '',
			];
		}
		return $rows;
	}
}
