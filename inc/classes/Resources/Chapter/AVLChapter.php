<?php
/**
 * AVLChapter
 * 用戶可以上的章節
 */

declare( strict_types=1 );

namespace J7\PowerCourse\Resources\Chapter;

use J7\PowerCourse\Utils\Base;

/**
 * Class AVLChapter
 */
final class AVLChapter {

	/**
	 * Chapter ID
	 *
	 * @var int
	 */
	public int $id;

	/**
	 * User ID
	 *
	 * @var int
	 */
	public int $user_id;

	/**
	 * Course ID
	 *
	 * @var int|null
	 */
	public int|null $course_id;

	/**
	 * 第一次進入章節時間
	 *
	 * @var string|null
	 */
	public string|null $first_visit_at;

	/**
	 * 完成章節時間
	 *
	 * @var string|null
	 */
	public string|null $finished_at;

	/**
	 * Constructor
	 *
	 * @param int      $id Chapter ID.
	 * @param int|null $user_id User ID.
	 */
	public function __construct( int $id, ?int $user_id = null ) {
		$this->id             = $id;
		$this->user_id        = $user_id ? $user_id : \get_current_user_id();
		$this->course_id      = Utils::get_course_id( $id );
		$this->first_visit_at = (string) MetaCRUD::get( $id, $this->user_id, 'first_visit_at', true );
		$this->finished_at    = (string) MetaCRUD::get( $id, $this->user_id, 'finished_at', true );
	}

	/**
	 * 取得章節長度
	 *
	 * @param bool $human_readable 是否要人類可讀的格式.
	 * @return int|string 秒數 或 時:分:秒
	 */
	public function get_chapter_length( bool $human_readable = false ): int|string {
		$length = (int) get_post_meta( $this->id, 'chapter_length', true );
		if ( $human_readable ) {
			return Base::get_video_length_by_seconds( $length );
		}
		return $length;
	}
}
