<?php
/**
 * Email Trigger At
 */

declare( strict_types=1 );

namespace J7\PowerCourse\PowerEmail\Resources\Email\Trigger;

/**
 * Class At 觸發發信時機點
 */
final class At {

	/**
	 * Constructor
	 */
	public function __construct() {
		// 開通課程權限後
		\add_action( 'added_{$meta_type}_meta', [ $this, 'send' ] );
	}
}
