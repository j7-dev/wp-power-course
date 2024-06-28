<?php
/**
 * @var \WP_User $args
 */

$user = $args;

if ( ! ( $user instanceof \WP_User ) ) {
	throw new \Exception( 'user 不是 WP_User' );
}

$user_id = $user->ID;

$display_name = $user->display_name;

$user_avatar_url = \get_user_meta( $user_id, 'avatar_url', true );

$user_avatar_url = $user_avatar_url ? $user_avatar_url : \get_avatar_url(
	$user->ID,
	[
		'size' => 200,
	]
);

$user_link = \get_author_posts_url( $user_id );

printf(
	'<a href="%1$s" target="_blank" class="flex gap-2 items-center text-gray-800 hover:text-gray-800/70">
	<img class="rounded-full w-6 h-6" src="%2$s" loading="lazy" alt="%3$s"/>%3$s</a>',
	$user_link,
	$user_avatar_url,
	$display_name
);
