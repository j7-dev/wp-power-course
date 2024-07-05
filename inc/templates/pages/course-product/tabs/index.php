<?php

use J7\PowerCourse\Templates\Templates;

/**
 * @var WC_Product $args
 */
$product     = $args;
$description = $product->get_description();
$accordion   = Templates::get( 'collapse/chapter', args: $product, echo: false );
$qa          = Templates::get( 'collapse/qa', args: $product, echo: false );

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
	'tabs/base',
	[
		'course_tabs'        => $course_tabs,
		'default_active_key' => '1',
	]
);
