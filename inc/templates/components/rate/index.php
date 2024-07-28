<?php
/**
 * Rate component
 */

$default_args = [
	'show_before' => false, // 是否顯示前面的文字
	'count'       => 5, // 總共幾個星星
	'value'       => 3.7, // 有幾個星星是填滿的
	'total'       => null, // 有幾個評論 null | int
	'disabled'    => true, // 是否禁用
];

/**
 * @var array $args
 * @phpstan-ignore-next-line
 */
$args = wp_parse_args( $args, $default_args );

[
	'show_before' => $show_before,
	'count'       => $count,
	'value'       => $value,
	'total'       => $total,
	'disabled'    => $disabled,
] = $args;

$rest = fmod( $value, 1 ); // 取餘數

$fill_star_num    = ( (int) $value ) + ( $rest >= 0.8 ? 1 : 0 );
$half_star_num    = ( $rest > 0.2 && $rest < 0.8 ) ? 1 : 0;
$outline_star_num = $count - $fill_star_num - $half_star_num;
$cursor           = $disabled ? 'cursor-default' : '';
$disabled         = $disabled ? 'disabled' : '';

$icons_html = /*html*/'<div class="pc-rating pc-rating-sm pc-rating-half"><input type="radio" name="rating-10" class="pc-rating-hidden" />';
for ( $i = 1; $i <= $count; $i++ ) {
	$half_checked = ( $i <= $fill_star_num || ( $i === $fill_star_num + 1 && $half_star_num === 1 ) ) ? 'checked' : '';
	$full_checked = ( $i <= $fill_star_num ) ? 'checked' : '';

	$icons_html .= sprintf(
		/*html*/'<input type="radio" name="rating-10" class="bg-yellow-400 pc-mask pc-mask-star-2 pc-mask-half-1 %1$s" %2$s %3$s />',
		$cursor,
		$half_checked,
		$disabled
	);
	$icons_html .= sprintf(
		/*html*/'<input type="radio" name="rating-10" class="bg-yellow-400 pc-mask pc-mask-star-2 pc-mask-half-2 %1$s" %2$s %3$s />',
		$cursor,
		$full_checked,
		$disabled
	);
}
$icons_html .= '</div>';

printf(
/*html*/'
<div class="flex items-center gap-1 whitespace-nowrap">
	%1$s
	%2$s
	%3$s
</div>
',
$show_before ? "<span class=\"text-xl font-bold\">{$value}</span>" : '',
$icons_html,
null !== $total ? "<span>({$total})</span>" : ''
);
