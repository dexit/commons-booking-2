<?php
global $post;

if ( ! $post instanceof CB2_Location )
	throw new Exception( 'CB2_Location required for popup' );

// <div form...
CB2::the_hidden_form( $post->post_type(), array(), $post );

// ------------------------------------ Tab nav
CB2::the_tabs();

// ------------------------------------ Bottom actions
CB2::the_form_bottom( array(
	'delete' => 'Delete whole definition'
) );
