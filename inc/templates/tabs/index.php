<?php

use J7\PowerCourse\Templates\Components\Tabs;


$description = $product->get_description();

ob_start();
?>

<div id="accordion">
	<h3>Section 1</h3>
	<div>
	<p>Mauris mauris ante, blandit et, ultrices a, suscipit eget.
	Integer ut neque. Vivamus nisi metus, molestie vel, gravida in,
	condimentum sit amet, nunc. Nam a nibh. Donec suscipit eros.
	Nam mi. Proin viverra leo ut odio.</p>
	</div>
	<h3>Section 2</h3>
	<div>
	<p>Sed non urna. Phasellus eu ligula. Vestibulum sit amet purus.
	Vivamus hendrerit, dolor aliquet laoreet, mauris turpis velit,
	faucibus interdum tellus libero ac justo.</p>
	</div>
	<h3>Section 3</h3>
	<div>
	<p>Nam enim risus, molestie et, porta ac, aliquam ac, risus.
	Quisque lobortis.Phasellus pellentesque purus in massa.</p>
	<ul>
		<li>List item one</li>
		<li>List item two</li>
		<li>List item three</li>
	</ul>
	</div>
</div>
<script>
	(function($){
		setTimeout(() => {
			$( "#accordion" ).accordion();
		}, 3000);

	})(jQuery);
</script>
<?php
$accordion = ob_get_clean();

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
		'content' => '學習資源內容',
	),
	array(
		'key'     => '4',
		'label'   => '留言',
		'content' => '學習評價內容',
	),
	array(
		'key'     => '5',
		'label'   => '評價',
		'content' => '學習評價內容',
	),
	array(
		'key'     => '6',
		'label'   => '公告',
		'content' => '學習評價內容',
	),
);


echo Tabs::base(
	array(
		'course_tabs'        => $course_tabs,
		'default_active_key' => '1',
	)
);
