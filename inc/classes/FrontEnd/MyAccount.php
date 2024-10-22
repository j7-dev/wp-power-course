<?php
/**
 * Front-end MyAccount Page
 * 我的學習
 */

declare( strict_types=1 );

namespace J7\PowerCourse\FrontEnd;

use J7\PowerCourse\Templates\Templates;

/**
 * Class FrontEnd
 */
final class MyAccount {
	use \J7\WpUtils\Traits\SingletonTrait;

	public const COURSES_ENDPOINT = 'courses';

	/**
	 * Constructor
	 */
	public function __construct() {
		$hide_myaccount_courses = \get_option( 'hide_myaccount_courses', 'no' );

		if ( 'yes' === $hide_myaccount_courses ) {
			return;
		}

		\add_action( 'init', [ $this, 'custom_account_endpoint' ] );
		\add_filter( 'woocommerce_account_menu_items', [ $this, 'courses_menu_items' ], 100, 1 );
		\add_action(
			'woocommerce_account_' . self::COURSES_ENDPOINT . '_endpoint',
			[ $this, 'render_courses' ]
		);
	}

	/**
	 * Custom account endpoint 我的學習
	 */
	public function custom_account_endpoint(): void {
		// @phpstan-ignore-next-line
		\add_rewrite_endpoint( self::COURSES_ENDPOINT, EP_ROOT | EP_PAGES );
	}

	/**
	 * Add menu item 我的學習
	 *
	 * @param array<string> $items Menu items.
	 *
	 * @return array<string>
	 */
	public function courses_menu_items( array $items ): array {
		// 重新排序，排在控制台後
		return array_slice( $items, 0, 1, true ) + [
			self::COURSES_ENDPOINT => __(
				'我的學習',
				'power-course'
			),
		] + array_slice( $items, 1, null, true );
	}

	/**
	 * Render courses
	 */
	public function render_courses(): void {
		echo '<div class="tailwind">';
		Templates::get( 'my-account' );
		echo '</div>';
	}
}
