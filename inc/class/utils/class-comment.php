<?php
/**
 * Comment
 */

declare ( strict_types=1 );

namespace J7\PowerCourse\Utils;

/**
 * Class Utils
 */
abstract class Comment {
	/**
	 * 檢查能不能評價商品
	 *
	 * @param \WC_Product $product 商品
	 * @param string      $type create
	 * @return true|string 能不能 comment 或原因
	 */
	public static function can_comment( \WC_Product $product, ?string $type = 'create' ): bool|string {
		if ('create' === $type) {
			$reviews_allowed = $product->get_reviews_allowed(); // 後台設定，是否允許評價

			if (!$reviews_allowed) {
				return '此課程不開放評價';
			}

			$has_bought = wc_customer_bought_product( '', get_current_user_id(), $product->get_id() ); // 用戶是否已購買此課程

			if (!$has_bought) {
				return '您尚未購買此課程，尚無法評價';
			}

			// 檢查用戶是否評論過此商品
			$has_reviewed = get_comments(
				[
					'post_id' => $product->get_id(),
					'user_id' => get_current_user_id(),
				]
			);

			if ($has_reviewed) {
				return '您已評價過此課程，無法再次評價';
			}

			$has_reviewed    = count( $has_reviewed ) > 0;
			$reviews_allowed = $reviews_allowed && $has_bought && !$has_reviewed;

			return $reviews_allowed;
		}

		return true;
	}
}
