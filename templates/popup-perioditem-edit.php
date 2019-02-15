<?php
global $post;

if ( ! $post instanceof CB2_PeriodItem )
	throw new Exception( 'CB2_PeriodItem required for popup' );
if ( ! $post->period )
	throw new Exception( 'CB2_PeriodItem has no specific period (CB2_Period)' );

// <div form...
CB2::the_hidden_form( $post->post_type(), array(), $post );

// ------------------------------------ Tab nav
CB2::the_tabs();

// ------------------------------------ Instance
print( "<div id='cb2-tab-instance'>" );
CB2::the_meta_boxes( $post );
print( '</div>' );

// ------------------------------------ Definition
print( "<div id='cb2-tab-definition'>" );
CB2::the_meta_boxes( $post->period );
print( '</div>' );

// ------------------------------------ Security
print( "<div id='cb2-tab-security'>" );
CB2::the_meta_boxes( $post->period_entity, 'security' );
print( '</div>' );

// ------------------------------------ Bottom actions
CB2::the_form_bottom( array(
	'delete' => 'Delete whole definition'
) );
