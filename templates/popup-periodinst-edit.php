<?php
global $post; // CB2_PeriodInst

if ( ! $post->period )
	throw new Exception( 'CB2_PeriodInst has no specific period (CB2_Period)' );

$save_instances_from_here = __( 'Save from here onwards' );
$save_all_instances       = __( 'Save all instances' );
$save_instance_only       = __( 'Save this instance only' );

// <div form...
CB2::the_hidden_form( $post->post_type(), array(), $post );
if ( WP_DEBUG && FALSE ) print( "<div class='cb2-WP_DEBUG-small' style='float:right;'>$post->period->ID</div>" );

print( "<div class='hidden'>" );
CB2::the_meta_boxes( $post );
print( '</div>' );

CB2::the_meta_boxes( $post->period );

// save_type can create new periods from this one, or just save this one, etc.
print( '<div id="cb2-save-types">' );
print( "<input id='cb2-SFH' name='save_type' type='radio' value='SFH' checked='1'><label for='cb2-SFH'>$save_instances_from_here</label> " );
print( "<input id='cb2-SAI' name='save_type' type='radio' value='SAI'><label for='cb2-SAI'>$save_all_instances</label> " );
print( "<input id='cb2-SOT' name='save_type' type='radio' value='SOT'><label for='cb2-SOT'>$save_instance_only</label> " );
print( '</div>' );

// ------------------------------------ Bottom actions
CB2::the_form_bottom( array(
	'trash' => 'Trash whole definition'
) );
