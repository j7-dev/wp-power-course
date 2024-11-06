<?php
/**
 * Sidebar for classroom
 */

use J7\PowerCourse\Plugin;
use J7\PowerCourse\FrontEnd\MyAccount;


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
<div id="pc-classroom-sider" class="tw-hidden lg:tw-block w-[25rem] bg-white z-20 left-0 top-0 h-screen expended"
	style="border-right: 1px solid #eee;position:fixed;left:0px">
	<div id="pc-classroom-sider__main" class="h-full flex flex-col [&_.pc-sider-chapters]:flex-1 [&_.pc-sider-chapters]:pb-12">
		%1$s
		<div class="py-4 pl-9 absolute bottom-0 w-full bg-white">
			<a
				href="%2$s"
				class="hover:opacity-75 transition duration-300 flex gap-4 items-center"
			>
				<img class="w-6 h-6" src="%3$s" />
				<span class="text-gray-600 font-light">
						回《我的學習》
				</span>
			</a>
		</div>
	</div>
</div>
',
	Plugin::get(
		'classroom/chapters',
		[
			'product' => $product,
		],
		false
		),
	\wc_get_account_endpoint_url( MyAccount::COURSES_ENDPOINT ),
	Plugin::$url . '/inc/assets/src/assets/svg/back.svg',
);
