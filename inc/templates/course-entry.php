<?php
/**
 * The Template for displaying all single products
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://woo.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use J7\PowerCourse\Templates\Components\Icons;
use J7\PowerCourse\Templates\Components\Buttons;
use J7\PowerCourse\Templates\Components\Rate;
use J7\PowerCourse\Templates\Components\Course;



get_header(); ?>
<script src="https://cdn.tailwindcss.com"></script>
<div class="w-full max-w-[1138px] mx-auto bg-slate-100 px-0 md:px-6 text-base font-normal">

<!-- Header -->
<div class="flex gap-6 flex-col md:flex-row">
	<div class="w-full md:w-[55%]">
		<div class="w-full rounded-2xl aspect-video bg-slate-400 animate-pulse"></div>
	</div>

	<div class="w-full md:w-[45%]">
		<a href="#" class="flex gap-2 items-center">
		<img class="rounded-full w-6 h-6" src="https://images.hahow.in/images/65e190cc6292533d0c8b547a?width=24" />
		蔡佩軒 Ariel
		</a>

		<h1 class="mt-2 mb-[10px] text-xl md:text-4xl leading-7 md:leading-[3rem] font-semibold  text-gray-800">唱出你的特色！蔡佩軒的歌唱訓練課</h1>

		<div class="text-gray-400">
		創作歌手 Ariel 蔡佩軒的「歌唱訓練」與「詞曲創作」課！帶你發現自己獨特聲音的力量，運用技巧唱出個人特色，一開口就被稱讚！並帶你從 0 開始做出第一首歌，輕鬆寫出心中旋律。透過課程學習，歌唱零基礎也能唱出特色，不懂樂理也能寫出自創曲。
		</div>

		<div class="flex h-8 items-center gap-2">
		<?php echo Icons::fire(); ?>
		熱門課程
		</div>


		<div class="flex h-8 items-center gap-2">
		<?php echo Icons::shopping_bag(); ?>
		2,310 人已購買
		</div>

		<?php
		echo Buttons::button(
			array(
				'children' => '立即購買',
				'icon'     => 'fire',
			)
		);
		?>

		<div class="flex h-8 items-center gap-2">
		<?php
		echo Rate::rate(
			array(
				'value' => 4.5,
				'total' => 100,
			)
		);
		?>
		</div>


	</div>
</div>

<div class="flex flex-col md:flex-row gap-8">
<!-- Body -->
<div class="flex-1">
<?php
echo Course::info(
	array(
		'items' => array(
			array(
				'icon'  => 'fire',
				'label' => '開課時間',
				'value' => '2022/08/31 16:00',
			),
			array(
				'icon'  => 'fire',
				'label' => '預計時長',
				'value' => '15 小時 8 分',
			),
			array(
				'icon'  => 'fire',
				'label' => '預計單元',
				'value' => '39個',
			),
			array(
				'icon'  => 'fire',
				'label' => '觀看時間',
				'value' => '無限制',
			),
			array(
				'icon'  => 'fire',
				'label' => '課程學員',
				'value' => '1214 人',
			),
		),
	)
);
?>
</div>

<!-- Sider -->
<div class="w-[20rem]">

</div>

</div>




</div>
<?php
get_footer();

/* Omit closing PHP tag at the end of PHP files to avoid "headers already sent" issues. */
