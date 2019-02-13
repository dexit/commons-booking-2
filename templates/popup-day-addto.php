<?php
// ---------------------------------- add new PeriodEntity based on template
// this addto action adds a period to an existing PeriodGroup
// using the context post as its target
global $post; // CB2_Day

if ( ! $post instanceof CB2_Day )
	throw new Exception( 'CB2_Day required for popup' );

// ---------------------------------- template post
$target_post   = ( isset( $template_args[ 'context_post' ] ) ? $template_args[ 'context_post' ] : NULL );
$Class_target  = ( $target_post ? get_class( $target_post ) : NULL );
if ( ! $target_post )
	throw new Exception( "addto needs a target CB2_PeriodEntity." );
if ( ! $target_post instanceof CB2_PeriodEntity )
	throw new Exception( "addto can only add to CB2_PeriodEntity. [$Class_target] sent through." );
$period_group  = $target_post->period_group;

// Texts
$cancel_text   = __( 'Cancel' );
$save_text     = __( 'Save' );
$advanced_text = __( 'advanced' );

// <div form...
CB2::the_hidden_form( $period_group->post_type(), array(), $period_group, 'addtopost' );

// ------------------------------------ Tab nav
print( "<button class='cb2-popup-form-save cb2-save-visible-ajax-form'>$save_text</button>" );
if ( WP_DEBUG )
	print( "<div class='dashicons-before dashicons-admin-tools cb2-advanced'><a href='#'>$advanced_text</a></div>" );
CB2::the_tabs( array(
		"cb2-tab-periodgroup" => 'Period Group',
		"cb2-tab-periods"     => 'Periods',
		"cb2-tab-period"      => 'Time period',
	),
	'cb2-tab-period'
);

// ------------------------------------ Period Group
print( "<div id='cb2-tab-periodgroup'>" );
CB2::the_meta_box( "{$Class_target}_period_group", $target_post );
print( '</div>' );

// ------------------------------------ Periods
print( "<div id='cb2-tab-periods'>" );
CB2::the_meta_box( "CB2_PeriodGroup_periods",      $period_group );
print( '</div>' );

// ------------------------------------ New Period
print( "<div id='cb2-tab-period'>" );
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
print( "<div class='cb2-actions'>
	<a class='cb2-popup-form-cancel' onclick='tb_remove();' href='#'>$cancel_text</a>
	<button class='cb2-popup-form-save cb2-save-visible-ajax-form'>$save_text</button>
</div>" );
print( '</div>' );
