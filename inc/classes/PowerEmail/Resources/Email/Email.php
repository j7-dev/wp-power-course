<?php
/**
 * Email
 */

declare( strict_types=1 );

namespace J7\PowerCourse\PowerEmail\Resources\Email;

/**
 * Class Email
 */
final class Email {

	/**
	 * @var string Email ID
	 */
	public string $id;

	/**
	 * @var string Email 狀態
	 */
	public string $status;

	/**
	 * @var string Email 主旨
	 */
	public string $name;

	/**
	 * @var string Email 內容，存放 mjml
	 * @see https://mjml.io/
	 */
	public string $description;

	/**
	 * @var string Email Hook 動作名稱
	 */
	public string $action_name;

	/**
	 * @var string Email N 天{前後}發送
	 */
	public string $days;

	/**
	 * @var string Email 操作 'after' | 'before'
	 */
	public string $operator;

	/**
	 * @var string Email 建立時間
	 */
	public string $date_created;

	/**
	 * @var string Email 修改時間
	 */
	public string $date_modified;

	/**
	 * @var array Email post meta 欄位
	 */
	public static array $meta_keys = [
		'action_name',
		'days',
		'operator',
	];
	/**
	 * Constructor
	 *
	 * @param \WP_Post|int $post Post object or post ID.
	 */
	public function __construct( $post ) {
		$post                = $post instanceof \WP_Post ? $post : \get_post( $post );
		$this->id            = (string) $post->ID;
		$this->status        = $post->post_status;
		$this->name          = $post->post_title;
		$this->description   = $post->post_content;
		$this->date_created  = $post->post_date;
		$this->date_modified = $post->post_modified;

		foreach ( self::$meta_keys as $key ) {
			$this->$key = \get_post_meta( $this->id, $key, true );
		}
	}
}
