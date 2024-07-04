<?php
/**
 * 對 AVL Coursemeta table 的 CRUD
 */

declare ( strict_types=1 );

namespace J7\PowerCourse\Utils;

/**
 * Class Utils
 */
abstract class AVLCourseMeta {
	/**
	 * Adds a meta value for a specific course and user in the AVL Course Meta class.
	 *
	 * @param int    $course_id The ID of the course.
	 * @param int    $user_id The ID of the user.
	 * @param string $meta_key The key of the meta value.
	 * @param mixed  $meta_value The value of the meta data.
	 * @return int|false The ID of the newly added meta data, or false on failure.
	 */
	public static function add_avl_course_meta( int $course_id, int $user_id, string $meta_key, mixed $meta_value ): int|false {
		global $wpdb;

		$table_name = $wpdb->avl_coursemeta;

		$data = [
			'course_id'  => $course_id,
			'user_id'    => $user_id,
			'meta_key'   => $meta_key,
			'meta_value' => maybe_serialize( $meta_value ),
		];

		return $wpdb->insert(
			$table_name,
			$data,
			[ '%d', '%d', '%s', '%s' ]
		);
	}

	/**
	 * Updates the meta value for a specific course and user in the AVL course meta table.
	 *
	 * @param int    $course_id   The ID of the course.
	 * @param int    $user_id     The ID of the user.
	 * @param string $meta_key    The meta key.
	 * @param mixed  $meta_value  The meta value.
	 *
	 * @return int|false The number of rows affected on success, or false on failure.
	 */
	public static function update_avl_course_meta( int $course_id, int $user_id, string $meta_key, mixed $meta_value ): int|false {

		global $wpdb;

		$table_name = $wpdb->avl_coursemeta;

		return $wpdb->update(
			$table_name,
			[ // data
				'meta_key'   => $meta_key,
				'meta_value' => maybe_serialize( $meta_value ),
			],
			[ // where
				'course_id' => $course_id,
				'user_id'   => $user_id,
			],
			[ // format
				'%s',
				'%s',
			],
			[ // where format
				'%d',
				'%d',
			]
		);
	}

	/**
	 * Retrieves the available course meta for a specific course and user.
	 *
	 * @param int    $course_id The ID of the course.
	 * @param int    $user_id   The ID of the user.
	 * @param string $meta_key  The meta key to retrieve.
	 * @param bool   $single    Optional. Whether to return a single value or an array of values. Default is false.
	 *
	 * @return mixed The course meta value(s).
	 */
	public static function get_avl_course_meta( int $course_id, int $user_id, string $meta_key, ?bool $single = false ) {
		global $wpdb;

		$table_name = $wpdb->avl_coursemeta;

		if (empty($meta_key)) {
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT meta_key, meta_value FROM $table_name WHERE course_id = %1\$d AND user_id = %2\$d",
					$course_id,
					$user_id
				)
			);
			return wp_list_pluck($results, 'meta_value', 'meta_key');
		}

		$meta_value = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT meta_value FROM $table_name WHERE course_id = %1\$d AND user_id = %2\$d AND meta_key = %3\$s",
				$course_id,
				$user_id,
				$meta_key
			)
		);

		if ($single) {
			return maybe_unserialize($meta_value[0]);
		} else {
			return array_map('maybe_unserialize', $meta_value);
		}
	}

	/**
	 * Deletes the available course meta for a specific course and user.
	 *
	 * @param int    $course_id   The ID of the course.
	 * @param int    $user_id     The ID of the user.
	 * @param string $meta_key    Optional. The meta key to delete. Default is null.
	 * @param mixed  $meta_value  Optional. The meta value to delete. Default is an empty string.
	 *
	 * @return int|false The number of rows deleted on success, or false on failure.
	 */
	public static function delete_avl_course_meta( int $course_id, int $user_id, string $meta_key = null, mixed $meta_value = '' ): int|false {
		global $wpdb;
		$table_name = $wpdb->avl_coursemeta;

		if (!empty($meta_value)) {
			return $wpdb->delete(
				$table_name,
				[
					'course_id'  => $course_id,
					'user_id'    => $user_id,
					'meta_key'   => $meta_key,
					'meta_value' => maybe_serialize($meta_value),
				],
				[
					'%d',
					'%d',
					'%s',
					'%s',
				]
			);
		} else {
			return $wpdb->delete(
				$table_name,
				[
					'course_id' => $course_id,
					'user_id'   => $user_id,
					'meta_key'  => $meta_key,
				],
				[
					'%d',
					'%d',
					'%s',
				]
			);
		}
	}
}
