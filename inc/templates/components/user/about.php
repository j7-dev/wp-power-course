<?php
/**
 * User About component
 */

$default_args = [
	'user' => wp_get_current_user(),
];

/**
 * @var array $args
 * @phpstan-ignore-next-line
 */
$args = wp_parse_args( $args, $default_args );

[
	'user' => $user,
] = $args;


if ( ! ( $user instanceof \WP_User ) ) {
	throw new \Exception( 'user 不是 WP_User' );
}

$user_id = $user->ID;

$display_name = $user->display_name;

$user_avatar_url = \get_user_meta( $user_id, 'user_avatar_url', true );

$user_avatar_url = $user_avatar_url ? $user_avatar_url : \get_avatar_url(
	$user->ID,
	[
		'size' => 200,
	]
);

$description = \wpautop( $user->description );

$user_link = \get_author_posts_url( $user_id );

printf(
/*html*/'
<div class="flex gap-6 items-center mb-6">
	<div class="group rounded-full w-20 h-20 overflow-hidden">
		<img class="group-hover:scale-125 transition duration-300 ease-in-out" src="%1$s" loading="lazy"/>
	</div>
	<h4 class="text-xl font-semibold">%2$s</h4>
</div>

<div class="mb-6">%3$s</div>

<div>
	<a target="_blank" href="%4$s"
		class="flex hover:opacity-75 whitespace-nowrap items-center text-sm text-gray-800 hover:text-gray-800 transition duration-300 ease-in-out">
		<span style="border-bottom: 1px solid #333">前往講師個人頁</span>
		<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
			<path d="M10 7L15 12L10 17" stroke="#333" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
		</svg>
	</a>
</div>
',
$user_avatar_url,
$display_name,
$description,
$user_link
);
