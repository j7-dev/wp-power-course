<?php

/**
 * 覆寫 WooCommerce 模板
 */

declare( strict_types=1 );

namespace J7\PowerCourse\Templates;

use J7\PowerCourse\Plugin;
use J7\PowerCourse\Utils\Base;

/**
 * Class FrontEnd
 */
final class Templates {

	use \J7\WpUtils\Traits\SingletonTrait;

	public const SLUG = 'course_slug';

	/**
	 * Constructor
	 */
	public function __construct() {
		// \add_filter( 'wc_get_template', array( $this, 'override_wc_template' ), 999999, 5 );

		\add_action(
			'init',
			[ $this, 'add_rewrite_rules' ]
		);

		\add_filter( 'query_vars', [ $this, 'add_query_var' ] );
		\add_filter( 'template_include', [ $this, 'load_custom_template' ], 999999 );

		\add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		\add_filter( 'language_attributes', [ $this, 'add_html_attr' ], 20, 2 );
	}

	/**
	 * 從指定的模板路徑讀取模板文件並渲染數據
	 *
	 * @param string $name 指定路徑裡面的文件名
	 * @param mixed  $args 要渲染到模板中的數據
	 * @param bool   $load_once 是否只載入一次
	 * @param bool   $echo 是否輸出
	 *
	 * @return ?string|null
	 * @throws \Exception 如果模板文件不存在.
	 */
	public static function get(
		string $name,
		mixed $args = null,
		?bool $load_once = false,
		?bool $echo = true
	): ?string {
		$result = self::safe_get( $name, $args, $load_once, $echo );
		if ( '' === $result ) {
			throw new \Exception( "模板文件 {$name} 不存在" );
		}

		return $result;
	}

	/**
	 * 從指定的模板路徑讀取模板文件並渲染數據
	 *
	 * @param string $name 指定路徑裡面的文件名
	 * @param mixed  $args 要渲染到模板中的數據
	 * @param bool   $load_once 是否只載入一次
	 * @param bool   $echo 是否輸出
	 *
	 * @return string|false|null
	 * @throws \Exception 如果模板文件不存在.
	 */
	public static function safe_get(
		string $name,
		mixed $args = null,
		?bool $load_once = false,
		?bool $echo = true
	): string|false|null {
		$area_names = [ 'head', 'header', 'body', 'main', 'sider', 'footer', 'my-account' ]; // 區域名稱

		// 如果 $name 是以 area name 開頭的，那就去 area folder 裡面找
		$is_area = false;
		foreach ( $area_names as $area_name ) {
			if ( strpos( $name, $area_name ) === 0 ) {
				$is_area = true;
				break;
			}
		}

		if ( $is_area ) {
			$template_path = Plugin::$dir . '/inc/templates/' . $name;
		} else { // 不是區域名稱就去 components 裡面找
			$template_path = Plugin::$dir . '/inc/templates/components/' . $name;
		}

		// 檢查模板文件是否存在
		if ( file_exists( "{$template_path}.php" ) ) {
			if ( $echo ) {
				\load_template( "{$template_path}.php", $load_once, $args );

				return null;
			}
			ob_start();
			\load_template( "{$template_path}.php", $load_once, $args );

			return ob_get_clean();
		} elseif ( file_exists( "{$template_path}/index.php" ) ) {
			if ( $echo ) {
				\load_template( "{$template_path}/index.php", $load_once, $args );

				return null;
			}
			ob_start();
			\load_template( "{$template_path}/index.php", $load_once, $args );

			return ob_get_clean();
		}

		return '';
	}


	/**
	 * Add rewrite rules
	 *
	 * @return void
	 */
	public function add_rewrite_rules(): void {
		// get registered rewrite rules.
		$rules = get_option( 'rewrite_rules', [] );
		// set the regex.
		$regex = '^courses/?([^/]*)/?';

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
	 *
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
	 *
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
	 *
	 * @return array
	 */
	public function custom_post_type_rewrite_rules( $rules ): array {
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
				if ( ! $product_post ) {
					\wp_safe_redirect( \home_url( '/404' ) );
					exit;
				}

				$product = \wc_get_product( $product_post->ID );

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

	/**
	 * Enqueue assets
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		// DELETE \wp_enqueue_script( 'jquery-ui-accordion' );

		\wp_enqueue_script(
			Plugin::$kebab . '-template',
			Plugin::$url . '/inc/assets/dist/index.js',
			[ 'jquery' ],
			Plugin::$version,
			[
				'strategy'  => 'async',
				'in_footer' => true,
			]
		);

		\wp_enqueue_style(
			Plugin::$kebab . '-template',
			Plugin::$url . '/inc/assets/dist/css/index.css',
			[],
			Plugin::$version
		);
	}

	public function add_html_attr( $output, $doctype ) {
		// ["light", "dark", "cupcake"]
		return $output . ' data-theme="light"';
	}
}

Templates::instance();
