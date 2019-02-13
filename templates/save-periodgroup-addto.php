<?php
global $post;

if ( ! $post )
	throw new Exception( 'CB2_PeriodGroup required for addto' );
if ( ! $post instanceof CB2_PeriodGroup )
	throw new Exception( "CB2_PeriodGroup type post required for addto, [$post->post_type] sent" );

$ID               = $post->ID;
$post_type        = $post->post_type;
$properties       = $_POST;

$properties['ID'] = CB2_CREATE_NEW;
$period           = CB2_Period::factory_from_properties( $properties );

// TODO: make this generic so that it can save
// entirely from the target post_type and properties

$post->add_period( $period );
$post->save();

// XML response
print( "<!--\n" );
print( "  [$ID/$post_type]\n" );
foreach( $_POST as $name => $value ) {
	if      ( is_string( $value ) ) print( "  $name => $value\n" );
	else if ( is_array(  $value ) ) print( "  $name => Array(...)\n" );
	else print( "  $name => ...\n" );
}
print( "\n-->\n" );
print( "<result>Ok</result>" );
