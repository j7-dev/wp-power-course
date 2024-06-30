<?php

/**
 * @var array $args
 * - type string error, success, warning, info
 * - message string
 */


$default_props = [
	'type'    => 'default', // error, success, warning, info
	'message' => 'OOPS! 🤯 您好像還沒購買此課程，前往購買',
];

$props = wp_parse_args( $args, $default_props );

[
	'type'    => $type,
	'message' => $message,
] = $props;

$color_class = match ( $type ) {
	'error'   => 'text-red-800 bg-red-50',
	'success' => 'text-green-800 bg-green-50',
	'warning' => 'text-orange-800 bg-orange-50',
	'info'    => 'text-blue-800 bg-blue-50',
	default   => 'text-gray-800 bg-gray-50',
};


printf(
	'
	<div
		class="flex items-center p-4 mb-4 rounded-lg %1$s"
		role="alert">
		<svg class="flex-shrink-0 w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor"
		     viewBox="0 0 20 20">
			<path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
		</svg>
		<div class="ms-3 text-base font-medium">
			%2$s
		</div>
	</div>
	',
	$color_class,
	$message
);
