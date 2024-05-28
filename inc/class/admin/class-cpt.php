<?php
/**
 * Custom Post Type: chapter
 */

declare(strict_types=1);

namespace J7\PowerCourse\Admin;

use J7\PowerCourse\Plugin;

/**
 * Class CPT
 */
final class CPT {
	use \J7\WpUtils\Traits\SingletonTrait;

	const POST_TYPE = 'pc_chapter';

	/**
	 * Rewrite
	 *
	 * @var array
	 */
	public $rewrite = array(
		'template_path' => 'test.php',
		'slug'          => 'test',
		'var'           => 'power_course_test',
	);

	/**
	 * Constructor
	 */
	public function __construct() {

		\add_action( 'init', array( $this, 'init' ) );

		if ( ! empty( $args['rewrite'] ) ) {
			\add_filter( 'query_vars', array( $this, 'add_query_var' ) );
			\add_filter( 'template_include', array( $this, 'load_custom_template' ), 99 );
		}
	}

	/**
	 * Initialize
	 */
	public function init(): void {
		$this->register_cpt();

		// add {$this->post_type}/{slug}/test rewrite rule
		if ( ! empty( $this->rewrite ) ) {
			\add_rewrite_rule( '^power-course/([^/]+)/' . $this->rewrite['slug'] . '/?$', 'index.php?post_type=power-course&name=$matches[1]&' . $this->rewrite['var'] . '=1', 'top' );
			\flush_rewrite_rules();
		}
	}

	/**
	 * Register power-course custom post type
	 */
	public static function register_cpt(): void {

		$labels = array(
			'name'                     => \esc_html__( 'chapter', 'power-course' ),
			'singular_name'            => \esc_html__( 'chapter', 'power-course' ),
			'add_new'                  => \esc_html__( 'Add new', 'power-course' ),
			'add_new_item'             => \esc_html__( 'Add new item', 'power-course' ),
			'edit_item'                => \esc_html__( 'Edit', 'power-course' ),
			'new_item'                 => \esc_html__( 'New', 'power-course' ),
			'view_item'                => \esc_html__( 'View', 'power-course' ),
			'view_items'               => \esc_html__( 'View', 'power-course' ),
			'search_items'             => \esc_html__( 'Search power-course', 'power-course' ),
			'not_found'                => \esc_html__( 'Not Found', 'power-course' ),
			'not_found_in_trash'       => \esc_html__( 'Not found in trash', 'power-course' ),
			'parent_item_colon'        => \esc_html__( 'Parent item', 'power-course' ),
			'all_items'                => \esc_html__( 'All', 'power-course' ),
			'archives'                 => \esc_html__( 'chapter archives', 'power-course' ),
			'attributes'               => \esc_html__( 'chapter attributes', 'power-course' ),
			'insert_into_item'         => \esc_html__( 'Insert to this power-course', 'power-course' ),
			'uploaded_to_this_item'    => \esc_html__( 'Uploaded to this power-course', 'power-course' ),
			'featured_image'           => \esc_html__( 'Featured image', 'power-course' ),
			'set_featured_image'       => \esc_html__( 'Set featured image', 'power-course' ),
			'remove_featured_image'    => \esc_html__( 'Remove featured image', 'power-course' ),
			'use_featured_image'       => \esc_html__( 'Use featured image', 'power-course' ),
			'menu_name'                => \esc_html__( 'chapter', 'power-course' ),
			'filter_items_list'        => \esc_html__( 'Filter power-course list', 'power-course' ),
			'filter_by_date'           => \esc_html__( 'Filter by date', 'power-course' ),
			'items_list_navigation'    => \esc_html__( 'chapter list navigation', 'power-course' ),
			'items_list'               => \esc_html__( 'chapter list', 'power-course' ),
			'item_published'           => \esc_html__( 'chapter published', 'power-course' ),
			'item_published_privately' => \esc_html__( 'chapter published privately', 'power-course' ),
			'item_reverted_to_draft'   => \esc_html__( 'chapter reverted to draft', 'power-course' ),
			'item_scheduled'           => \esc_html__( 'chapter scheduled', 'power-course' ),
			'item_updated'             => \esc_html__( 'chapter updated', 'power-course' ),
		);
		$args   = array(
			'label'                 => \esc_html__( 'chapter', 'power-course' ),
			'labels'                => $labels,
			'description'           => '',
			'public'                => true,
			'hierarchical'          => true,
			'exclude_from_search'   => true,
			'publicly_queryable'    => true,
			'show_ui'               => true,
			'show_in_nav_menus'     => false,
			'show_in_admin_bar'     => false,
			'show_in_rest'          => true,
			'query_var'             => false,
			'can_export'            => true,
			'delete_with_user'      => true,
			'has_archive'           => false,
			'rest_base'             => '',
			'show_in_menu'          => true,
			'menu_position'         => 6,
			'menu_icon'             => 'dashicons-store',
			'capability_type'       => 'post',
			'supports'              => array( 'title', 'editor', 'thumbnail', 'custom-fields', 'author', 'page-attributes' ),
			'taxonomies'            => array(),
			'rest_controller_class' => 'WP_REST_Posts_Controller',
			'rewrite'               => array(
				'with_front' => true,
			),
		);

		\register_post_type( self::POST_TYPE, $args );
	}


	/**
	 * Add query var
	 *
	 * @param array $vars Vars.
	 * @return array
	 */
	public function add_query_var( $vars ) {
		$vars[] = $this->rewrite['var'];
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
		$repor_template_path = Plugin::$dir . '/inc/templates/' . $this->rewrite['template_path'];

		if ( \get_query_var( $this->rewrite['var'] ) ) {
			if ( file_exists( $repor_template_path ) ) {
				return $repor_template_path;
			}
		}
		return $template;
	}
}

CPT::instance();
