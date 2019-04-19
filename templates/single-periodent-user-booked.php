<?php
$ID             = get_the_ID();
$Class          = CB2::get_the_Class(); // CB2_PeriodEntity_Timeframe_User
$post_type      = get_post_type();      // periodent-user
$message_class  = 'cb2-notice';
$fdisplay_order = 'cb2_item_location_summary';

if ( CB2::is_confirmed() ) {
	CB2_Settings::set_default_options();

	$message_booking_confirmed = CB2_Settings::get( 'messagetemplates_booking-confirmed' );
	CB2::the_message( $message_booking_confirmed, array( $message_class ) );

	CB2::the_inner_loop( NULL, NULL, 'summary', NULL, '', '', $fdisplay_order );
} else {
	$do_action    = 'confirm';
	$confirm_text = __( 'Confirm', 'commons-booking-2' );
	$message_please_confirm = CB2_Settings::get( 'messagetemplates_please-confirm' );
	CB2::the_message( $message_please_confirm, array( $message_class ) );

	CB2::the_inner_loop( NULL, NULL, 'summary', NULL, '', '', $fdisplay_order );

	print( "<a href='?do_action=$Class::$do_action&do_action_post_ID=$ID' class='cb2-button'>$confirm_text</a>" );
}

function cb2_item_location_summary( $post1, $post2 ) {
	// Manual sorting of the inner posts
	$order = array(
		'item'     => 1,
		'location' => 2,
		'user'     => 3,
	);
	return $order[$post1->post_type] > $order[$post2->post_type];
}
