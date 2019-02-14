<?php
global $post;

if ( ! $post instanceof CB2_PeriodItem )
	throw new Exception( 'CB2_PeriodItem required for popup' );

$ID               = $post->ID;
$post_type        = $post->post_type;
$properties       = $_POST;

$post->block( isset( $properties['blocked'] ) );

$properties['ID'] = $post->period->ID;
$period           = CB2_Period::factory_from_properties( $properties );
$period->save();

print( "<result>Ok</result>" );
