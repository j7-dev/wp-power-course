<?php
/**
 * 覆寫 WooCommerce 模板
 */

declare( strict_types=1 );

namespace J7\PowerCourse\Templates;

use J7\PowerCourse\Plugin;
use J7\PowerCourse\Utils\Course as CourseUtils;

/**
 * Class FrontEnd
 */
final class Templates {

	use \J7\WpUtils\Traits\SingletonTrait;

	// 上課頁面
	public const CLASSROOM_SLUG = 'classroom_slug';
	public const CHAPTER_ID     = 'chapter_id';

	/**
	 * Constructor
	 */
	public function __construct() {
		\add_filter('template_include', [ $this, 'course_product_template' ], 9999);
		\add_action('init', [ $this, 'add_rewrite_rules' ]);
		\add_filter( 'query_vars', [ $this, 'add_query_var' ] );
		\add_filter( 'template_include', [ $this, 'load_custom_template' ], 9999 );
		\add_filter( 'language_attributes', [ $this, 'add_html_attr' ], 20, 2 );
		\add_action( 'admin_bar_menu', [ $this, 'admin_bar_item' ], 210 );
	}

	/**
	 * 覆寫課程商品頁面
	 * [危險] 如果全域變數汙染，會導致無法預期行為
	 *
	 * @param string $template 原本的模板路徑
	 *
	 * @return string
	 */
	public function course_product_template( $template ) {
		global $wp_query;

		if ($wp_query->is_page() || !$wp_query->is_single()) {
			return $template;
		}

		if ('product' !== $wp_query->get('post_type')) {
			return $template;
		}

		$product_id = $wp_query->queried_object_id;

		if (!CourseUtils::is_course_product( (int) $product_id )) {
			return $template;
		}

		$course_product_template = Plugin::$dir . '/inc/templates/course-product-entry.php';
		return $course_product_template;
	}

	/**
	 * Add rewrite rules
	 *
	 * @return void
	 */
	public function add_rewrite_rules(): void {
		// get registered rewrite rules.
		/** @var array<string,string> $rules */
		$rules = \get_option( 'rewrite_rules', [] );

		// @deprecated 為了兼容0.3以前版本的永久連結
		$old_course_permalink_structure = (string) \get_option('course_permalink_structure', '');
		$old_course_regex               = '^' . $old_course_permalink_structure . '/(.+)/?$';
		if ($old_course_permalink_structure) {
			\add_rewrite_rule($old_course_regex, 'index.php?product=$matches[1]', 'top');
		}

		// 教室頁面
		$classroom_regex = '^classroom/([^/]+)/?([^/]*)/?'; // '^classroom/?([^/]*)/?';
		\add_rewrite_rule(
			$classroom_regex,
			'index.php?' . self::CLASSROOM_SLUG . '=$matches[1]&' . self::CHAPTER_ID . '=$matches[2]',
			'top'
		);

		if ( ! isset( $rules[ $classroom_regex ] ) || ! isset( $rules[ $old_course_regex ] ) ) {
			\flush_rewrite_rules();
		}
	}

	/**
	 * Add query var
	 *
	 * @param array<string> $vars Vars.
	 *
	 * @return array<string>
	 */
	public function add_query_var( array $vars ): array {
		$vars[] = self::CLASSROOM_SLUG;
		$vars[] = self::CHAPTER_ID;
		return $vars;
	}

	/**
	 * Load custom template
	 * Set {Plugin::$kebab}/{slug}/report  php template
	 *
	 * @param string|null $template Template.
	 */
	public function load_custom_template( $template ): string|null {
		// 使用自定義的模板
		$items = [
			[
				// {site_url}/classroom/{slug}/{slug_2}
				'key'    => 'classroom',
				'slug'   => \get_query_var( self::CLASSROOM_SLUG ),
				'slug_2' => \get_query_var( self::CHAPTER_ID ),
				'path'   => Plugin::$dir . '/inc/templates/classroom-entry.php',
			],
		];

		foreach ( $items as $item ) {
			$key    = $item['key']; // "classroom"
			$slug   = (string) $item['slug']; // product_slug
			$slug_2 = (int) $item['slug_2']; // chapter_id
			$path   = $item['path'];

			if ( $slug ) {
				if ( file_exists( $path ) ) {
					// @phpstan-ignore-next-line
					$product_post = \get_page_by_path( $slug, OBJECT, 'product' );
					if ( ! $product_post ) {
						\wp_safe_redirect( \home_url( '/404' ) );
						exit;
					}
					/** @var \WP_Post $product_post */
					$product = \wc_get_product( $product_post->ID );
					if (!$product) {
						\wp_safe_redirect( \home_url( '/404' ) );
						exit;
					}

					$GLOBALS['product'] = $product;

					// 如果商品不是課程，則不要載入模板
					$is_course_product = CourseUtils::is_course_product( $GLOBALS['product'] );

					if ( ! $is_course_product ) {
						return $template;
					}

					if ('classroom' === $key) { // @phpstan-ignore-line
						if ( $slug_2 ) {
							$GLOBALS['chapter'] = \get_post( $slug_2);
						} else {
							/** @var array<int> $sub_chapter_ids */
							$sub_chapter_ids = CourseUtils::get_sub_chapters( $GLOBALS['product'], true );
							if (count($sub_chapter_ids) < 1) {
								// TODO 沒有章節應該顯示空教室!?
								\wp_safe_redirect( \home_url( '/404' ) );
								exit;
							} else {
								$first_sub_chapter_id = $sub_chapter_ids[0];
								\wp_safe_redirect( \site_url( 'classroom' ) . "/{$slug}/{$first_sub_chapter_id}" );
								exit;
							}
						}
					}

					return $path;
				}
			}
		}

		return $template;
	}


	/**
	 * Add html attr
	 * 用來切換 daisyUI 的主題
	 *
	 * @param string $output Output.
	 * @param string $doctype Doctype.
	 *
	 * @return string
	 */
	public function add_html_attr( string $output, string $doctype ): string {
		// ["light", "dark", "cupcake"]
		return $output . ' data-theme="power"';
	}

	/**
	 * 在管理員工具列中新增項目
	 *
	 * @param \WP_Admin_Bar $admin_bar 管理員工具列物件
	 *
	 * @return void
	 */
	public function admin_bar_item( \WP_Admin_Bar $admin_bar ): void {

		if ( ! \current_user_can( 'manage_options' ) ) {
			return;
		}

		global $post;
		$post_id           = (int) $post?->ID;
		$is_course_product = CourseUtils::is_course_product( $post_id );

		if (!$is_course_product) {
			// 不是課程銷售頁就顯示課程列表
			$admin_bar->add_menu(
				[
					'id'     => Plugin::$kebab,
					'parent' => null,
					'group'  => null,
					'title'  => '課程列表', // you can use img tag with image link. it will show the image icon Instead of the title.
					'href'   => \admin_url('admin.php?page=power-course#/courses'),
					'meta'   => [
						'title' => \__( '課程列表', 'power_course' ), // This title will show on hover
					],
				]
			);
			return;
		}
		// 是課程銷售頁就顯示課程編輯
		$admin_bar->add_menu(
			[
				'id'     => Plugin::$kebab,
				'parent' => null,
				'group'  => null,
				'title'  => '編輯課程', // you can use img tag with image link. it will show the image icon Instead of the title.
				'href'   => \admin_url("admin.php?page=power-course#/courses/edit/{$post_id}"),
				'meta'   => [
					'title' => \__( '編輯課程', 'power_course' ), // This title will show on hover
				],
			]
		);
	}
}
