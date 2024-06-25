<?php
$props = $args;

$default_props = [
	'show_before' => false, // 是否顯示前面的文字
	'count'       => 5, // 總共幾個星星
	'value'       => 3.7, // 有幾個星星是填滿的
	'total'       => null, // 有幾個評論 null | int
	'disabled'    => true, // 是否禁用
];

$props = \array_merge( $default_props, $props );

$value       = $props['value'];
$count       = $props['count'];
$total       = $props['total'];
$show_before = $props['show_before'];
$disabled    = $props['disabled'];
$rest        = fmod( $value, 1 );

$fill_start_num    = ( (int) $value ) + ( $rest >= 0.8 ? 1 : 0 );
$half_start_num    = ( $rest > 0.2 && $rest < 0.8 ) ? 1 : 0;
$outline_start_num = $count - $fill_start_num - $half_start_num;
$cursor            = $disabled ? 'cursor-default' : '';
$disabled          = $disabled ? 'disabled' : '';

$icons_html = '<div class="pc-rating pc-rating-sm pc-rating-half"><input type="radio" name="rating-10" class="pc-rating-hidden" />';
for ( $i = 0; $i < $count; $i++ ) {
	$half_checked = ( $i === ( (int) $fill_start_num ) && ! $half_start_num ) ? 'checked' : '';
	$full_checked = ( $i === ( (int) $fill_start_num ) && ! ! $half_start_num ) ? 'checked' : '';
	$icons_html  .= '<input type="radio" name="rating-10" class="bg-yellow-400 pc-mask pc-mask-star-2 pc-mask-half-1 ' . $cursor . '" ' . $full_checked . ' ' . $disabled . ' />';
	$icons_html  .= '<input type="radio" name="rating-10" class="bg-yellow-400 pc-mask pc-mask-star-2 pc-mask-half-2 ' . $cursor . '" ' . $half_checked . ' ' . $disabled . ' />';
}
$icons_html .= '</div>';

?>
<div class="flex items-center gap-1 whitespace-nowrap">
	<?php if ( $show_before ) : ?>
		<span class="text-xl font-bold"><?php echo $value; ?></span>
	<?php endif; ?>
	<?php echo $icons_html; ?>
	<?php if ( null !== $total ) : ?>
		<span class="">(<?php echo $total; ?>)</span>
	<?php endif; ?>
</div>
