<?php

$theme = $args['theme'] ?? 'light';

printf(
/*html*/'
<button class="outline-base-content text-start outline-offset-4 bg-transparent" data-act-class="[&_svg]:visible" data-set-theme="%1$s" type="button">
	<span class="bg-base-100 rounded-btn text-base-content tw-block w-full cursor-pointer font-sans" data-theme="%1$s">
		<span class="grid grid-cols-5 grid-rows-3">
			<span class="col-span-5 row-span-3 row-start-1 flex items-center gap-2 px-4 py-3">
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" class="invisible h-3 w-3 shrink-0">
					<path d="M20.285 2l-11.285 11.567-5.286-5.011-3.714 3.716 9 8.728 15-15.285z">
					</path>
				</svg> <span class="flex-grow text-sm">%1$s</span> <span class="flex h-full shrink-0 flex-wrap gap-1">
					<span class="bg-primary rounded-badge w-2">
					</span> <span class="bg-secondary rounded-badge w-2">
					</span> <span class="bg-accent rounded-badge w-2">
					</span> <span class="bg-neutral rounded-badge w-2">
					</span>
				</span>
			</span>
		</span>
	</span>
</button>
',
$theme
);
