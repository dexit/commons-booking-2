<?php
global $post;

if ( ! $post instanceof CB2_Day )
	throw new Exception( 'CB2_Day required for add' );

$ID               = $post->ID;
$post_type        = $post->post_type;
$properties       = $_POST;
//$period_entity    = CB2_PeriodEntity::factory_from_properties( $properties );

//$period_entity->save();

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
