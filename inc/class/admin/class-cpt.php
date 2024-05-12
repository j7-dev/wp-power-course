<?php
/**
 * Custom Post Type: power-course
 */

declare(strict_types=1);

namespace J7\PowerCourse\Admin;

use Micropackage\Singleton\Singleton;
use J7\PowerCourse\Utils\Base;
use J7\PowerCourse\Plugin;

/**
 * Class CPT
 */
final class CPT extends Singleton {

	/**
	 * Post metas
	 *
	 * @var array
	 */
	public $post_meta_array = array();
	/**
	 * Rewrite
	 *
	 * @var array
	 */
	public $rewrite = array();

	/**
	 * Constructor
	 *
	 * @param array $args Arguments.
	 */
	public function __construct( $args ) {
		$this->post_meta_array = $args['post_meta_array'];
		$this->rewrite         = $args['rewrite'] ?? array();

		\add_action( 'init', array( $this, 'init' ) );

		if ( ! empty( $args['post_meta_array'] ) ) {
			\add_action( 'rest_api_init', array( $this, 'add_post_meta' ) );
		}

		\add_action( 'load-post.php', array( $this, 'init_metabox' ) );
		\add_action( 'load-post-new.php', array( $this, 'init_metabox' ) );

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
			'name'                     => \esc_html__( 'power-course', 'power_course' ),
			'singular_name'            => \esc_html__( 'power-course', 'power_course' ),
			'add_new'                  => \esc_html__( 'Add new', 'power_course' ),
			'add_new_item'             => \esc_html__( 'Add new item', 'power_course' ),
			'edit_item'                => \esc_html__( 'Edit', 'power_course' ),
			'new_item'                 => \esc_html__( 'New', 'power_course' ),
			'view_item'                => \esc_html__( 'View', 'power_course' ),
			'view_items'               => \esc_html__( 'View', 'power_course' ),
			'search_items'             => \esc_html__( 'Search power-course', 'power_course' ),
			'not_found'                => \esc_html__( 'Not Found', 'power_course' ),
			'not_found_in_trash'       => \esc_html__( 'Not found in trash', 'power_course' ),
			'parent_item_colon'        => \esc_html__( 'Parent item', 'power_course' ),
			'all_items'                => \esc_html__( 'All', 'power_course' ),
			'archives'                 => \esc_html__( 'power-course archives', 'power_course' ),
			'attributes'               => \esc_html__( 'power-course attributes', 'power_course' ),
			'insert_into_item'         => \esc_html__( 'Insert to this power-course', 'power_course' ),
			'uploaded_to_this_item'    => \esc_html__( 'Uploaded to this power-course', 'power_course' ),
			'featured_image'           => \esc_html__( 'Featured image', 'power_course' ),
			'set_featured_image'       => \esc_html__( 'Set featured image', 'power_course' ),
			'remove_featured_image'    => \esc_html__( 'Remove featured image', 'power_course' ),
			'use_featured_image'       => \esc_html__( 'Use featured image', 'power_course' ),
			'menu_name'                => \esc_html__( 'power-course', 'power_course' ),
			'filter_items_list'        => \esc_html__( 'Filter power-course list', 'power_course' ),
			'filter_by_date'           => \esc_html__( 'Filter by date', 'power_course' ),
			'items_list_navigation'    => \esc_html__( 'power-course list navigation', 'power_course' ),
			'items_list'               => \esc_html__( 'power-course list', 'power_course' ),
			'item_published'           => \esc_html__( 'power-course published', 'power_course' ),
			'item_published_privately' => \esc_html__( 'power-course published privately', 'power_course' ),
			'item_reverted_to_draft'   => \esc_html__( 'power-course reverted to draft', 'power_course' ),
			'item_scheduled'           => \esc_html__( 'power-course scheduled', 'power_course' ),
			'item_updated'             => \esc_html__( 'power-course updated', 'power_course' ),
		);
		$args   = array(
			'label'                 => \esc_html__( 'power-course', 'power_course' ),
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
			'supports'              => array( 'title', 'editor', 'thumbnail', 'custom-fields', 'author' ),
			'taxonomies'            => array(),
			'rest_controller_class' => 'WP_REST_Posts_Controller',
			'rewrite'               => array(
				'with_front' => true,
			),
		);

		\register_post_type( 'power-course', $args );
	}

	/**
	 * Register meta fields for post type to show in rest api
	 */
	public function add_post_meta(): void {
		foreach ( $this->post_meta_array as $meta_key ) {
			\register_meta(
				'post',
				Plugin::SNAKE . '_' . $meta_key,
				array(
					'type'         => 'string',
					'show_in_rest' => true,
					'single'       => true,
				)
			);
		}
	}

	/**
	 * Meta box initialization.
	 */
	public function init_metabox(): void {
		\add_action( 'add_meta_boxes', array( $this, 'add_metabox' ) );
		\add_action( 'save_post', array( $this, 'save_metabox' ), 10, 2 );
		\add_filter( 'rewrite_rules_array', array( $this, 'custom_post_type_rewrite_rules' ) );
	}

	/**
	 * Adds the meta box.
	 *
	 * @param string $post_type Post type.
	 */
	public function add_metabox( string $post_type ): void {
		if ( in_array( $post_type, array( Plugin::KEBAB ) ) ) {
			\add_meta_box(
				Plugin::KEBAB . '-metabox',
				__( 'Power Course', 'power_course' ),
				array( $this, 'render_meta_box' ),
				$post_type,
				'advanced',
				'high'
			);
		}
	}

	/**
	 * Render meta box.
	 */
	public function render_meta_box(): void {
		// phpcs:ignore
		echo '<div id="' . Base::APP2_SELECTOR . '" class="relative"></div>';
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
	 * Save the meta when the post is saved.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public function save_metabox( $post_id, $post ) { // phpcs:ignore
		// phpcs:disable
		/*
		* We need to verify this came from the our screen and with proper authorization,
		* because save_post can be triggered at other times.
		*/

		// Check if our nonce is set.
		if ( ! isset( $_POST['_wpnonce'] ) ) {
			return $post_id;
		}

		$nonce = $_POST['_wpnonce'];

		/*
		* If this is an autosave, our form has not been submitted,
		* so we don't want to do anything.
		*/
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		$post_type = \sanitize_text_field( $_POST['post_type'] ?? '' );

		// Check the user's permissions.
		if ( 'power-course' !== $post_type ) {
			return $post_id;
		}

		if ( ! \current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		/* OK, it's safe for us to save the data now. */

		// Sanitize the user input.
		$meta_data = \sanitize_text_field( $_POST[ Plugin::SNAKE . '_meta' ] );

		// Update the meta field.
		\update_post_meta( $post_id, Plugin::SNAKE . '_meta', $meta_data );
	}

	/**
	 * Load custom template
	 * Set {Plugin::KEBAB}/{slug}/report  php template
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

CPT::get(
	array(
		'post_meta_array' => array( 'meta', 'settings' ),
		'rewrite'    => array(
			'template_path' => 'test.php',
			'slug'          => 'test',
			'var'           => Plugin::SNAKE . '_test',
		),
	)
);
