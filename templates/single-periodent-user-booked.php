<?php

$ID = get_the_ID();
$Class = CB2::get_the_Class();

global $post;

$content = cb2_get_template_part(CB2_TEXTDOMAIN, 'item-summary', '', array('item_id' => $post->item->ID), true);
$content .= cb2_get_template_part(CB2_TEXTDOMAIN, 'location-summary', '', array('location_id' => $post->location->ID), true);
$content .= cb2_get_template_part(CB2_TEXTDOMAIN, 'user-summary', '', array('user_id' => $post->user->ID), true);
// $content .= cb2_get_template_part(CB2_TEXTDOMAIN, 'user-summary', '', array('user_id' => $user_id), true);


if ( CB2::is_confirmed() ) {

	/* Notice */
	cb2_tag(	CB2_Settings::get('messagetemplates_booking-confirmed'), 'periodent-user', $ID, 'notice' );

	echo $content;

} else {


	$do_action = 'confirm';
	$confirm_text = __( 'Confirm', 'commons-booking-2' );

	cb2_tag( CB2_Settings::get('messagetemplates_please-confirm'), 'periodent-user', $ID, 'notice' );

	echo $content;


	print( "<a href='?do_action=$Class::$do_action&do_action_post_ID=$ID' class='cb2-button'>$confirm_text</a>" );
}
