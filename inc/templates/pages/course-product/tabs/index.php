<?php
/**
 * Course Tabs component
 */

use J7\PowerCourse\Templates\Templates;

$default_args = [
	'product' => $GLOBALS['product'] ?? null,
];

/**
 * @var array $args
 * @phpstan-ignore-next-line
 */
$args = wp_parse_args( $args, $default_args );

[
	'product' => $product,
] = $args;

if ( ! ( $product instanceof \WC_Product ) ) {
	throw new \Exception( 'product 不是 WC_Product' );
}

$description = $product->get_description();
$accordion   = Templates::get(
	'collapse/chapter',
	args: [
		'product' => $product,
	],
	echo: false
	);
$qa          = Templates::get(
	'collapse/qa',
	args: [
		'product' => $product,
	],
	echo: false
	);

$course_tabs = [
	[
		'key'     => '1',
		'label'   => '簡介',
		'content' => \wpautop( $description ),
	],
	[
		'key'     => '2',
		'label'   => '章節',
		'content' => $accordion,
	],
	[
		'key'     => '3',
		'label'   => '問答',
		'content' => $qa,
	],
	[
		'key'     => '4',
		'label'   => '留言',
		'content' => '🚧 施工中... 🚧',
	],
	[
		'key'     => '5',
		'label'   => '評價',
		'content' => '🚧 施工中... 🚧',
	],
	[
		'key'     => '6',
		'label'   => '公告',
		'content' => '🚧 施工中... 🚧',
	],
];

Templates::get(
	'tabs',
	[
		'course_tabs'        => $course_tabs,
		'default_active_key' => '1',
	]
);
