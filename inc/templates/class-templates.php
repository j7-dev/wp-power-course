<?php
/**
 * 覆寫 WooCommerce 模板
 */

declare(strict_types=1);

namespace J7\PowerCourse\Templates;

use J7\PowerCourse\Plugin;
use J7\PowerCourse\Utils\Base;


/**
 * Class FrontEnd
 */
final class Templates {
	use \J7\WpUtils\Traits\SingletonTrait;

	const SLUG = 'course_slug';

	/**
	 * Constructor
	 */
	public function __construct() {
		\add_filter( 'wc_get_template', array( $this, 'override_wc_template' ), 999999, 5 );

		\add_action(
			'init',
			array( $this, 'add_rewrite_rules' )
		);

		\add_filter( 'query_vars', array( $this, 'add_query_var' ) );
		\add_filter( 'template_include', array( $this, 'load_custom_template' ), 999999 );
	}

	/**
	 * Add rewrite rules
	 *
	 * @return void
	 */
	public function add_rewrite_rules(): void {
		// get registered rewrite rules.
		$rules = get_option( 'rewrite_rules', array() );
		// set the regex.
		$regex = 'courses/([a-z0-9-]+)[/]?$';

		// add the rewrite rule.
		\add_rewrite_rule( $regex, 'index.php?' . self::SLUG . '=$matches[1]', 'top' );

		// maybe flush rewrite rules if it was not previously in the option.
		if ( ! isset( $rules[ $regex ] ) ) {
			\flush_rewrite_rules();
		}
	}

	/**
	 * Override template
	 *
	 * @param string $located Located.
	 * @param string $template_name Template name.
	 * @param array  $args Args.
	 * @param string $template_path Template path.
	 * @param string $default_path Default path.
	 * @return string
	 */
	public function override_wc_template( $located, $template_name, $args, $template_path, $default_path ) {
		$type                      = 'basic';
		$plugin_template_file_path = Plugin::$dir . "/inc/templates/{$type}/{$template_name}";

		if ( file_exists( $plugin_template_file_path ) ) {
			return $plugin_template_file_path;
		} else {
			return $located;
		}
	}

	/**
	 * Add query var
	 *
	 * @param array $vars Vars.
	 * @return array
	 */
	public function add_query_var( $vars ) {
		$vars[] = self::SLUG;
		return $vars;
	}

	/**
	 * Custom post type rewrite rules
	 *
	 * @param array $rules Rules.
	 * @return array
	 */
	public function custom_post_type_rewrite_rules( $rules ) {
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
		return $rules;
	}

	/**
	 * Load custom template
	 * Set {Plugin::$kebab}/{slug}/report  php template
	 *
	 * @param string $template Template.
	 */
	public function load_custom_template( $template ) {
		$override_template_path = Plugin::$dir . '/inc/templates/course-entry.php';
		$courses_slug           = \get_query_var( self::SLUG );
		if ( $courses_slug ) {
			if ( file_exists( $override_template_path ) ) {
				global $product;
				$product_post = \get_page_by_path( $courses_slug, OBJECT, 'product' );
				$product      = \wc_get_product( $product_post->ID );

				// 如果商品不是課程，則不要載入模板
				$is_course_product = Base::is_course_product( $product );

				if ( ! $is_course_product ) {
					return $template;
				}

				return $override_template_path;
			}
		}
		return $template;
	}
}

Templates::instance();
