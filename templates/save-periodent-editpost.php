<?php
global $post;

if ( ! $post instanceof CB2_PeriodItem )
	throw new Exception( 'CB2_PeriodItem required for popup' );

$ID               = $post->ID;
$post_type        = $post->post_type;
$properties       = $_POST;

$block            = isset( $properties['blocked'] );
print( "<!-- CB2_PeriodItem::block(" . ( $block ? 'TRUE' : 'FALSE' ) . ") -->\n" );
$post->block( $block );

$period_ID        = $post->period->ID;
print( "<!-- saving CB2_Period[ID: $period_ID] -->\n" );
$properties['ID'] = $period_ID;
$period           = CB2_Period::factory_from_properties( $properties, $instance_container, TRUE );
$period->save( TRUE );

print( "<result>Ok</result>" );
