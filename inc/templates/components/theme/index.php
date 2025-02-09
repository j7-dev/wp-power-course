<?php

use J7\PowerCourse\Plugin;

$themes = [
	'light',
	'dark',
	'cupcake',
	'bumblebee',
	'emerald',
	'corporate',
	'synthwave',
	'retro',
	'cyberpunk',
	'valentine',
	'halloween',
	'garden',
	'forest',
	'aqua',
	'lofi',
	'pastel',
	'fantasy',
	'wireframe',
	'black',
	'luxury',
	'dracula',
	'cmyk',
	'autumn',
	'business',
	'night',
	'winter',
	'dim',
	'nord',
	'sunset',
];
?>
<div id="pc-theme-changer" tabindex="0" class="tw-fixed bottom-8 right-8 z-20 pc-dropdown-content pc-dropdown-top pc-dropdown-end bg-base-200 text-base-content rounded-box h-[28.6rem] max-h-[calc(100vh-10rem)] w-56 overflow-y-auto border border-white/5 shadow-2xl outline outline-1 outline-black/5 mt-16">
	<div class="grid grid-cols-1 gap-3 p-3">
		<?php foreach ( $themes as $theme ) : ?>
			<?php Plugin::get('theme/button', [ 'theme' => $theme ]); ?>
		<?php endforeach; ?>
	</div>
</div>

<script type="module" async>
	(function($) {
		$(document).ready(function() {
			$('#pc-theme-changer button[data-set-theme]').click(function() {
				const theme = $(this).data('set-theme');
				// 修改 html tag attribute data-theme
				$('html').attr('data-theme', theme);
				// document.documentElement.setAttribute('data-theme', theme);
			});


		});
	})(jQuery);
</script>
