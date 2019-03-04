<?php
$ID    = get_the_ID();
$Class = CB2::get_the_Class();

if ( CB2::is_confirmed() ) {
	/* Notice */
	cb2_tag(	CB2_Settings::get('messagetemplates_booking-confirmed'), 'periodent-user', $ID, 'notice' );

	CB2::the_inner_loop( NULL, NULL, 'summary', NULL, '', '', 'cb2_item_location_summary' );
} else {
	$do_action    = 'confirm';
	$confirm_text = __( 'Confirm', 'commons-booking-2' );

	cb2_tag( CB2_Settings::get('messagetemplates_please-confirm'), 'periodent-user', $ID, 'notice' );

	CB2::the_inner_loop( NULL, NULL, 'summary', NULL, '', '', 'cb2_item_location_summary' );

	print( "<a href='?do_action=$Class::$do_action&do_action_post_ID=$ID' class='cb2-button'>$confirm_text</a>" );
}

// Manual sorting of the inner posts
function cb2_item_location_summary( $post1, $post2 ) {
	$order = array(
		'item'     => 1,
		'location' => 2,
		'user'     => 3,
	);
	return $order[$post1->post_type] > $order[$post2->post_type];
}
