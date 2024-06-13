<?php
/**
 * User
 */

declare(strict_types=1);

namespace J7\PowerCourse\Templates\Components;

use J7\PowerCourse\Templates\Components\Icon;

/**
 * Class FrontEnd
 */
abstract class User {


	/**
	 * User about
	 *
	 * @param array $props props.
	 * - user \WP_User
	 * @return string
	 */
	public static function about( array $props ): string {
		$user = $props['user'] ?? null;

		if ( ! ( $user instanceof \WP_User ) ) {
			throw new \Exception( 'user 不是 WP_User' );
		}

		$user_id = $user->ID;

		$display_name = $user->display_name;

		$user_avatar_url = \get_user_meta( $user_id, 'avatar_url', true );

		$user_avatar_url = $user_avatar_url ? $user_avatar_url : \get_avatar_url(
			$user->ID,
			array(
				'size' => 200,
			)
		);

		$description = \wpautop( $user->description );

		$user_link = \get_author_posts_url( $user_id );

		ob_start();
		?>

		<div class="flex gap-6 items-center mb-6">
			<div class="group rounded-full w-20 h-20 overflow-hidden">
			<img class="group-hover:scale-125 transition duration-300 ease-in-out" src="<?php echo $user_avatar_url; ?>" />
			</div>
			<h4 class="text-xl font-semibold"><?php echo $display_name; ?></h4>
		</div>

		<div class="mb-6">
		<?php echo $description; ?>
	</div>

		<div>
			<a target="_blank" href="<?php echo $user_link; ?>" class="flex hover:opacity-75 whitespace-nowrap items-center text-sm text-gray-800 hover:text-gray-800 transition duration-300 ease-in-out">
			<span style="border-bottom: 1px solid #333">前往講師個人頁</span>
			<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
<path d="M10 7L15 12L10 17" stroke="#333" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
			</a>
		</div>

		<?php
		$html = ob_get_clean();

		return $html;
	}

	/**
	 * User about
	 * DELETE
	 *
	 * @param array|null $props props.
	 * @return string
	 */
	public static function about_demo(): string {

		ob_start();
		?>

		<div class="flex gap-6 items-center mb-8">
			<img class="rounded-full w-20 h-20" src="https://s3.ap-northeast-1.amazonaws.com/s3.sat/members/1685935626_%E5%8A%89%E5%BF%85%E6%A6%AE%EF%BD%9C%E8%AC%9B%E5%B8%AB%E4%BB%8B%E7%B4%B9_%E5%A4%A7%E9%A0%AD%E7%85%A7.png" />
			<h4 class="text-xl font-semibold">劉必榮</h4>
		</div>

		<div class="mb-8">
		華人圈一致公認的國際關係權威與談判大師，數十年來致力於國際觀與談判藝術的推廣，擁有多本被譽為國際觀必讀經典、談判聖經的相關著作。其談判課程更為鴻海集團等大企業列為主管升遷的必修課程。
		</div>

		<div>
			<a href="#" class="flex hover:opacity-75 whitespace-nowrap items-center text-sm text-gray-800 hover:text-gray-800 transition duration-300 ease-in-out">
			<span style="border-bottom: 1px solid #333">前往講師個人頁</span>
			<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
<path d="M10 7L15 12L10 17" stroke="#333" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
			</a>
		</div>

		<?php
		$html = ob_get_clean();

		return $html;
	}
}
