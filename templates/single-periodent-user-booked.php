<?php
if ( CB2::is_confirmed() ) {
	print( 'confirmed' );
} else {
	$ID        = get_the_ID();
	$Class     = CB2::get_the_Class();
	$do_action = 'confirm';
	$confirm_text = __( 'confirm' );
	print( "<a href='?do_action=$Class::$do_action&do_action_post_ID=$ID'>$confirm_text</a>" );
}
