<?php
/**
 * Front-end Entry
 */

declare(strict_types=1);

namespace J7\PowerCourse\FrontEnd;

use J7\PowerCourse\Utils\Base;

/**
 * Class FrontEnd
 */
final class Entry {
	use \J7\WpUtils\Traits\SingletonTrait;

	/**
	 * Constructor
	 */
	public function __construct() {
		\add_action( 'wp_footer', [ $this, 'render_app' ] );
	}

	/**
	 * Render application's markup
	 */
	public function render_app(): void {
		// phpcs:ignore
		echo '<div id="' . Base::APP2_SELECTOR . '"></div>';
	}
}

Entry::instance();
