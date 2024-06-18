<?php

use J7\PowerCourse\Templates\Components\Tabs;
use J7\PowerCourse\Templates\Components\Collapse;



$description = $product->get_description();

$accordion = Collapse::chapter( array( 'product' => $product ) );
$qa        = Collapse::qa( array( 'product' => $product ) );


$course_tabs = array(
	array(
		'key'     => '1',
		'label'   => '簡介',
		'content' => \wpautop( $description ),
	),
	array(
		'key'     => '2',
		'label'   => '章節',
		'content' => $accordion,
	),
	array(
		'key'     => '3',
		'label'   => '問答',
		'content' => $qa,
	),
	array(
		'key'     => '4',
		'label'   => '留言',
		'content' => '🚧 施工中... 🚧',
	),
	array(
		'key'     => '5',
		'label'   => '評價',
		'content' => '🚧 施工中... 🚧',
	),
	array(
		'key'     => '6',
		'label'   => '公告',
		'content' => '🚧 施工中... 🚧',
	),
);


echo Tabs::base(
	array(
		'course_tabs'        => $course_tabs,
		'default_active_key' => '1',
	)
);
