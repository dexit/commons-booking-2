<?php
// ---------------------------------- add new PeriodEntity based on template
// this add action creates a new PeriodEntity
// using the template (context) post for its settings
// e.g. if the template has status Open, then this will haave status open
// a new period group is created containing the new period
global $post; // CB2_Day

if ( ! $post )
	throw new Exception( 'CB2_Day required for popup' );
if ( ! $post instanceof CB2_Day )
	throw new Exception( "CB2_Day post type required for popup, [$post->post_type] sent" );

// ---------------------------------- template post
$template_post = ( isset( $template_args[ 'context_post' ] ) ? $template_args[ 'context_post' ] : NULL );
$Class_context = ( $template_post ? get_class( $template_post ) : 'CB2_PeriodEntity_Timeframe_User' );

if ( $template_post && ! $template_post instanceof CB2_PeriodEntity )
	throw new Exception( "popup-day-add.php add can only add CB2_PeriodEntity. [$Class_context] sent through." );

// Texts
$name          = '';
$next_text     = __( 'Next' ) . ' &gt;&gt;';
$name_text     = 'Name';
$name_placeholder_text = 'New period entity';

// <div form...
CB2::the_hidden_form( $post->post_type(), array(), $post, 'add' );
print( "<button class='cb2-popup-form-next cb2-save-visible-ajax-form'>$next_text</button>" );

// ------------------------------------ Tab nav
CB2::the_nexts( NULL, ( $template_post ? 'cb2-tab-period' : NULL ) );

// ------------------------------------ Period Status Type
print( "<div id='cb2-tab-status'>" );
CB2::the_meta_box( "{$Class_context}_period_status_type", $template_post );
CB2::the_meta_box( "{$Class_context}_enabled",            $template_post );
print( '</div>' );

// ------------------------------------ Objects: Location, Item, User
print( "<div id='cb2-tab-objects'>" );
// Passing through the $Class_context in the box definition
// will cause metaboxes not found if the $Class_context does not support them
CB2::the_meta_box( "{$Class_context}_location", $template_post, FALSE ); // $throw_if_not_found = FALSE
CB2::the_meta_box( "{$Class_context}_item",     $template_post, FALSE );
CB2::the_meta_box( "{$Class_context}_user",     $template_post, FALSE );
print( '</div>' );

// ------------------------------------ Period Group
print( "<div id='cb2-tab-periodgroup'>" );
// No Context post will cause a CB2_CREATE_NEW with the associated new period
CB2::the_meta_box( "{$Class_context}_period_group" );
CB2::the_custom_meta_box( 'period_IDs[]', '', CB2_CREATE_NEW, 'hidden' );
print( '</div>' );

// ------------------------------------ Period
print( "<div id='cb2-tab-period'>" );
CB2::the_custom_meta_box( 'name', $name_text, '', 'text', $name_placeholder_text );
$new_period = CB2_Period::factory(
	CB2_CREATE_NEW,
	'new',
	$post->date->clone()->setDayStart(),
	$post->date->clone()->setDayEnd(),
	CB2_DateTime::today(),
	NULL, NULL, NULL, NULL
);
CB2::the_meta_boxes( $new_period );
print( '</div>' );

// ------------------------------------ Bottom actions
CB2::the_form_bottom();
