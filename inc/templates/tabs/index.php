<?php

use J7\PowerCourse\Templates\Components\Tabs;


$description = $product->get_description();

ob_start();
?>
<div class="pc-accordion">
<section class="accordion">
	<div class="tab">
		<input type="checkbox" name="accordion-1" id="cb1">
		<label for="cb1" class="tab__label">Checkbox</label>
		<div class="tab__content">
			<p>Pure CSS accordion based on the "input:checked + label" style trick.</p>
		</div>
	</div>
	<div class="tab">
		<input type="checkbox" name="accordion-1" id="cb2">
		<label for="cb2" class="tab__label">Open multiple</label>
		<div class="tab__content">
			<p>Using <code>&lt;input type="checkbox"&gt;</code> allows to have several tabs open at the same time.</p>
		</div>
	</div>
</section>
</div>
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
