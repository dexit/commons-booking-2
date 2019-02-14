<?php
global $post;

if ( ! $post )
	throw new Exception( 'CB2_PeriodGroup required for addto' );
if ( ! $post instanceof CB2_PeriodGroup )
	throw new Exception( "CB2_PeriodGroup type post required for addto, [$post->post_type] sent" );

$properties       = $_POST;
$properties['ID'] = CB2_CREATE_NEW;
$period           = CB2_Period::factory_from_properties( $properties );
$post->add_period( $period );
$post->save();

// The parent file will trap any Exceptions and output
// and set the response code to 500 in the event of an error
print( "<result>Ok</result>" );
