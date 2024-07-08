<?php
/**
 * Sidebar for classroom
 */

use J7\PowerCourse\Templates\Templates;
use J7\PowerCourse\Plugin;


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


printf(
	/*html*/'
<div id="pc-classroom-sider" class="hidden lg:block w-[25rem] bg-white z-20 left-0 h-screen expended"
	style="border-right: 1px solid #eee;position:fixed;left:0px">
	<div id="pc-classroom-sider__main">
		%1$s
		<a
			href="%2$s"
			class="hover:opacity-75 transition duration-300"
		>
			<div class="flex gap-4 items-center py-4 pl-9 absolute bottom-0 w-full">
				<img class="w-6 h-6" src="%3$s" />
				<span class="text-gray-600 font-light">
						回《我的學習》
				</span>
			</div>
		</a>
	</div>
</div>
',
	Templates::get(
		'classroom/chapters',
		[
			'product' => $product,
		],
		false
		),
	\wc_get_account_endpoint_url( 'courses' ),
	Plugin::$url . '/inc/assets/src/assets/svg/wp.svg',
);
