<?php
$ID    = get_the_ID();
$Class = CB2::get_the_Class(); // CB2_PeriodEntity_Timeframe_User

if ( CB2::is_confirmed() ) {
	/* Notice */
	cb2_tag( CB2_Settings::get('messagetemplates_booking-confirmed'), 'periodent-user', $ID, 'notice' );

	CB2::the_inner_loop( NULL, NULL, 'summary', NULL, '', '', 'cb2_item_location_summary' );
} else {
	$do_action    = 'confirm';
	$confirm_text = __( 'Confirm', 'commons-booking-2' );

	// TODO: think about this cb2_tag( messagetemplates_please-confirm ) to CB2::*
	// cb2_tag() envelopes the template system around customizeable messages
	// post_type 'periodent-user' controls what tagging is available
	// So:
	//   CB2::the_template_message('messagetemplates_please-confirm', 'notice')
	// which uses the global post, post_type and its ID
	// TODO: and allow the relevant Class to decide its available template tags
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
