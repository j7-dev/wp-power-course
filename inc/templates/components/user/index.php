<?php
/**
 * User component
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
	return;
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

$user_link = \get_author_posts_url( $user_id );

printf(
	'<span href="%1$s" target="_blank" class="text-sm flex gap-2 items-center text-base-content hover:text-base-content/75">
	<img class="rounded-full size-6" src="%2$s" loading="lazy" alt="%3$s"/>%3$s</span>',
	'#', // TODO 先隱藏 $user_link,
	$user_avatar_url,
	$display_name
);
