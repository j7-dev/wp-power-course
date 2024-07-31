<?php
/**
 * Admin Entry
 */

declare(strict_types=1);

namespace J7\PowerCourse\Admin;

use J7\PowerCourse\Utils\Base;
use J7\PowerCourse\Plugin;
use J7\PowerCourse\Bootstrap;


/**
 * Class Entry
 */
final class Entry {


	use \J7\WpUtils\Traits\SingletonTrait;

	/**
	 * Constructor
	 */
	public function __construct() {

		\add_action('admin_menu', [ $this, 'add_menu' ], 20);
		// Add the admin page for full-screen.
		\add_action('current_screen', [ $this, 'maybe_output_admin_page' ], 10);
	}

	/**
	 * Add menu
	 */
	public function add_menu(): void {
		\add_submenu_page(
			'power_house',
			'Power Course',
			'Power Course',
			'manage_options',
			Plugin::$kebab,
			'',
			'dashicons-welcome-learn-more',
			6
		);
	}

	/**
	 * Output the dashboard admin page.
	 */
	public function maybe_output_admin_page() {
		// Exit if not in admin.
		if (!\is_admin()) {
			return;
		}

		// Make sure we're on the right screen.
		$screen = \get_current_screen();
		if (Plugin::$kebab !== $screen?->id) {
			return;
		}
		// require_once ABSPATH . 'wp-admin/admin-header.php';

		$this->render_page();

		exit;
	}

	/**
	 * Output landing page header.
	 *
	 * Credit: SliceWP Setup Wizard.
	 */
	public function render_page() {
		// Output header HTML.
		Bootstrap::enqueue_script();
		$blog_name = \get_bloginfo('name');

		?>
		<!doctype html>
		<html lang="zh_tw">

		<head>
			<meta charset="UTF-8" />
			<meta name="viewport" content="width=device-width, initial-scale=1.0" />
			<title>課程後台 | <?php echo $blog_name; ?></title>
		</head>

		<body  class="pc">
			<main id="power_course">

			</main>
		<?php
		/**
		 * Prints any scripts and data queued for the footer.
		 *
		 * @since 2.8.0
		 */
		\do_action('admin_print_footer_scripts');

		?>
		</body>

		</html>
		<?php
	}
}

Entry::instance();
