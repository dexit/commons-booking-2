<?php
global $post;

if ( ! $post instanceof CB2_PeriodInst )
	throw new Exception( 'CB2_PeriodInst required for popup' );

$ID               = $post->ID;
$post_type        = $post->post_type;
$properties       = $_POST;

$block            = isset( $properties['blocked'] );
print( "<!-- CB2_PeriodInst::block(" . ( $block ? 'TRUE' : 'FALSE' ) . ") -->\n" );
$post->block( $block );

$period_ID        = $post->period->ID;
print( "<!-- saving CB2_Period[ID: $period_ID] -->\n" );
$properties['ID'] = $period_ID;
// Here we force_properties because
// the period has already been loaded and cached with the old values
// and we want to re-create
$period           = CB2_Period::factory_from_properties( $properties, $instance_container, TRUE ); // force_properties
$period->save( TRUE ); // Update mode

print( "<result>Ok</result>" );
