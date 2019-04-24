<?php
// ---------------------------------- add new PeriodEntity based on template
// this addto action adds a period to an existing PeriodGroup
// using the context post as its target
global $post; // CB2_Day

// ---------------------------------- template post
$context_post  = ( isset( $template_args[ 'context_post' ] ) ? $template_args[ 'context_post' ] : NULL );
$Class_target  = ( $context_post ? get_class( $context_post ) : NULL );
if ( ! $context_post )
	throw new Exception( "addto needs a target CB2_PeriodEntity." );
if ( ! $context_post instanceof CB2_PeriodEntity )
	throw new Exception( "addto can only add to CB2_PeriodEntity. [$Class_target] sent through." );
$period_group  = $context_post->period_group;

// ---------------------------------- indicate context post_type
// Imposed #TB_window extra classes
$classes = array();
if ( $context_post && method_exists( $context_post, 'classes' ) ) $classes = $context_post->classes();
if ( $context_post->post_type ) array_push( $classes, "cb2-$context_post->post_type" );
$classes_string = implode( ' ', $classes );
print( "<div class='TB_window_classes'> $classes_string</div>" );
// Imposed #TB_window extra styles
$styles = array();
if ( $context_post && method_exists( $context_post, 'styles' ) ) $styles = $context_post->styles();
$styles_string = implode( ';', $styles );
print( "<div class='TB_window_styles'> $styles_string</div>" );

// <div form...
CB2::the_hidden_form( $period_group->post_type(), array(), $period_group, 'addto' );

// ------------------------------------ Period and Period Group
print( "<div class='hidden'>" );
CB2::the_meta_box( "CB2_PeriodEntity_Timeframe_User_period_group", $context_post );
CB2::the_meta_box( "CB2_PeriodGroup_periods",      $period_group );
print( '</div>' );

// ------------------------------------ New Period
$new_period = CB2_Period::factory(
	CB2_CREATE_NEW,
	'new',
	$post->date->clone()->setDayStart(),
	$post->date->clone()->setDayEnd(),
	CB2_DateTime::today(),
	NULL, NULL, NULL, NULL
);
CB2::the_meta_boxes( $new_period );

// ------------------------------------ Bottom actions
CB2::the_form_bottom();
