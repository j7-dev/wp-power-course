<?php
/**
 * Chapter
 */

declare( strict_types=1 );

namespace J7\PowerCourse\Resources\Chapter;

/**
 * Class LifeCycle
 */
final class Chapter {

	/**
	 * Chapter ID
	 *
	 * @var int
	 */
	public int $id;

	/**
	 * Constructor
	 *
	 * @param int $id Chapter ID.
	 */
	public function __construct( int $id ) {
		$this->id = $id;
	}

	/**
	 * 取得章節的課程 ID
	 *
	 * @return int|null
	 */
	public function get_course_id(): int|null {
		$ancestors = \get_post_ancestors( $this->id );
		if ( empty( $ancestors ) ) {
			return null;
		}
		// 取最後一個
		return $ancestors[ count( $ancestors ) - 1 ];
	}
}
