<?php
global $post;

if ( ! $post instanceof CB2_PeriodItem )
	throw new Exception( 'CB2_PeriodItem required for popup' );
if ( ! $post->period )
	throw new Exception( 'CB2_PeriodItem has no specific period (CB2_Period)' );

$ID          = $post->ID;
$post_type   = $post->post_type;
$action      = CB2_Query::pass_through_query_string( NULL, array( 'action' => 'save' ) );

$cancel_text = __( 'Cancel' );
$save_text   = __( 'Save all tabs' );
$delete_definition_text = __( 'Delete whole definition' );

// ------------------------------------ Tab nav
print( "<div id='cb2-ajax-edit-form-$ID' action='$action' class='cb2-ajax-edit-form'>" );
print( "<button class='cb2-popup-form-save cb2-todo cb2-save-visible-ajax-form'>$save_text</button>" );
CB2::the_tabs();

// ------------------------------------ Instance
print( "<div id='cb2-tab-instance'>" );
CB2::the_ajax_edit_screen( $post );
print( '</div>' );

// ------------------------------------ Definition
print( "<div id='cb2-tab-definition'>" );
CB2::the_ajax_edit_screen( $post->period );
print( '</div>' );

// ------------------------------------ Advanced
print( "<div id='cb2-tab-advanced'>" );
CB2::the_ajax_edit_screen( $post, 'advanced' );
print( '</div>' );

// ------------------------------------ Bottom actions
print( "<div class='cb2-actions'>
	<a class='cb2-popup-form-cancel' onclick='tb_remove();' href='#'>$cancel_text</a>
	<button class='cb2-popup-form-save cb2-todo cb2-save-visible-ajax-form'>$save_text</button>
	<button class='cb2-popup-form-delete cb2-todo cb2-dangerous cb2-right cb2-save-visible-ajax-form'>$delete_definition_text</button>
</div>" );
print( "</div>" );
