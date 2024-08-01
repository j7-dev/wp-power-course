<?php
/**
 *  Comment API
 */

declare(strict_types=1);

namespace J7\PowerCourse\Api;

use J7\PowerCourse\Plugin;
use J7\WpUtils\Classes\WP;
use J7\PowerCourse\Utils\Comment as CommentUtils;
/**
 * Class Api
 */
final class Comment {
	use \J7\WpUtils\Traits\SingletonTrait;
	use \J7\WpUtils\Traits\ApiRegisterTrait;

	/**
	 * APIs
	 *
	 * @var array{endpoint: string, method: string, permission_callback: ?callable}[]
	 * - endpoint: string
	 * - method: 'get' | 'post' | 'patch' | 'delete'
	 * - permission_callback : callable
	 */
	protected $apis = [
		[
			'endpoint'            => 'comments',
			'method'              => 'get',
			'permission_callback' => '__return_true',
		],
		[
			'endpoint'            => 'comments',
			'method'              => 'post',
			'permission_callback' => null,
		],
		// [
		// 'endpoint'            => 'comments/(?P<id>\d+)',
		// 'method'              => 'post',
		// 'permission_callback' => null,
		// ],
	];

	/**
	 * Constructor.
	 */
	public function __construct() {
		\add_action( 'rest_api_init', [ $this, 'register_api_products' ] );
	}

	/**
	 * Register products API
	 *
	 * @return void
	 */
	public function register_api_products(): void {
		$this->register_apis(
		apis: $this->apis,
		namespace: Plugin::$kebab,
		default_permission_callback: fn() => \current_user_can( 'manage_options' ),
		);
	}


	/**
	 * Get comments callback
	 * 通用的留言查詢
	 *
	 * @param \WP_REST_Request $request Request.
	 * $params
	 *  - post_id 哪一篇 POST 的留言
	 *  - meta_value
	 * - count_total 是否要計算總數
	 *
	 * @return \WP_REST_Response
	 * @phpstan-ignore-next-line
	 */
	public function get_comments_callback( $request ): \WP_REST_Response {

		$params = $request->get_query_params();

		$params = WP::sanitize_text_field_deep( $params, false );

		$default_args = [
			'number'       => 10,
			'paged'        => 1,
			'post_id'      => 0,
			'post_type'    => 'product',
			// 'type'         => 'review', // 加了 children 會出不來
			'user_id'      => '',
			'hierarchical' => 'threaded',
		];

		$args = \wp_parse_args(
		$params,
		$default_args,
		);

		/**
		 * @var \WP_Comment[] $comments
		 */
		$comments      = \get_comments($args);
		$comments      = \array_values($comments);
		$args['count'] = true;
		$total         = \get_comments($args);

		$total_pages = \floor( $total / $args['number'] ) + 1;

		$formatted_comments = array_map( [ $this, 'format_comment_details' ], $comments );

		$response = new \WP_REST_Response( $formatted_comments );

		// set pagination in header
		$response->header( 'X-WP-Total', (string) $total );
		$response->header( 'X-WP-TotalPages', (string) $total_pages );

		return $response;
	}



	/**
	 * 新增留言
	 *
	 * @param \WP_REST_Request $request 包含新增留言所需資料的REST請求對象。
	 * @return \WP_REST_Response 返回包含操作結果的REST響應對象。成功時返回用戶資料，失敗時返回錯誤訊息。
	 * @phpstan-ignore-next-line
	 */
	public function post_comments_callback( \WP_REST_Request $request ): \WP_REST_Response {
		$body_params = $request->get_body_params();

		$body_params = WP::sanitize_text_field_deep( $body_params, false );

		$product_id  = $body_params['comment_post_ID'];
		$product     = \wc_get_product($product_id);
		$can_comment = CommentUtils::can_comment($product);

		if (true !== $can_comment) {
			return new \WP_REST_Response(
			[
				'code'    => 400,
				'message' => $can_comment,
				'data'    => null,
			],
			400
			);
		}

		[
		'data' => $data,
		'meta_data' => $meta_data,
		] = WP::separator( args: $body_params, obj: 'comment' );

		$data['comment_meta']      = array_merge($data['comment_meta'] ?? [], $meta_data);
		$data['comment_author_IP'] = $_SERVER['REMOTE_ADDR']; // phpcs:ignore
		$data['comment_agent']     = $_SERVER['HTTP_USER_AGENT']; // phpcs:ignore

		$user_id                      = \get_current_user_id();
		$user                         = \get_user_by('id', $user_id);
		$data['comment_author']       = $user->display_name;
		$data['comment_author_email'] = $user->user_email;
		$data['comment_author_url']   = $user->user_url;
		$data['user_id']              = $user_id;

		$comment_id = \wp_insert_comment( $data );

		if (!$comment_id) {
			return new \WP_REST_Response(
			[
				'code'    => 400,
				'message' => '新增評價失敗，請再嘗試一次',
				'data'    => null,
			],
			400
			);
		}

		return new \WP_REST_Response(
			[
				'code'    => 200,
				'message' => '已評價成功',
				'data'    => [
					'id' => (string) $comment_id,
				],
			],
			200
			);
	}

	/**
	 * Format comment details
	 *
	 * @param \WP_Comment $comment  Comment.
	 * @param int         $depth        Depth.

	 * @return array{id: string,user_login: string,user_email: string,display_name: string,user_registered: string,user_registered_human: string,user_avatar: string,avl_courses: array<string, mixed>}[]
	 */
	public function format_comment_details( \WP_Comment $comment, ?int $depth = 0 ): array {

		if ( ! ( $comment instanceof \WP_Comment ) ) {
			return [];
		}

		$comment_id      = $comment->comment_ID;
		$user_id         = $comment->user_id;
		$user_avatar_url = \get_user_meta($user_id, 'user_avatar_url', true);
		$user_avatar_url = !!$user_avatar_url ? $user_avatar_url : \get_avatar_url( $user_id  );
		$user            = [
			'id'         => (string) $user_id,
			'name'       => \get_the_author_meta('display_name', $user_id) ?: '訪客',
			'avatar_url' => $user_avatar_url,
		];
		$rating          = \get_comment_meta($comment_id, 'rating', true);
		$comment_content = \wpautop($comment->comment_content);
		$comment_date    = $comment->comment_date;
		/**
		 * @var \WP_Comment[] $children
		 */
		$children = $comment->get_children();

		$children_array = $children ? array_map(fn( $child ) => $this->format_comment_details($child, $depth + 1), array_values($children)) : [];

		$base_array = [
			'id'              => (string) $comment_id,
			'depth'           => (int) $depth,
			'user'            => $user,
			'rating'          => (int) $rating,
			'comment_content' => $comment_content,
			'comment_date'    => $comment_date,
			'can_reply'       => true,
			'can_delete'      => \current_user_can( 'edit_comment', $comment_id ),
			'children'        => $children_array,
		];

		return $base_array;
	}
}

Comment::instance();
