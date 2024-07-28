<?php
/**
 * Comment Form
 */

use J7\PowerCourse\Templates\Templates;

$commenter    = wp_get_current_commenter();
$comment_form = [
	'title_reply'         => sprintf(/*html*/'<p class="text-gray-800 text-base font-bold mb-2">%s</p>', __( 'Add a review', 'woocommerce' )),
	'title_reply_to'      => esc_html__( 'Leave a Reply to %s', 'woocommerce' ),
	'comment_notes_after' => '',
	'label_submit'        => esc_html__( 'Submit', 'woocommerce' ),
	'logged_in_as'        => '',
	'comment_field'       => '',
];


$comment_form['comment_field'] = sprintf(
/*html*/'
<div class="mb-2">%1$s</div>
<textarea class="mb-2 rounded" id="comment" name="comment" cols="45" rows="4" required></textarea>
',
Templates::get(
	'rate',
	[
		'name'     => 'rating',
		'value'    => 5,
		'disabled' => false,
	],
	false
	)
);



comment_form( $comment_form, $product->get_id() );
