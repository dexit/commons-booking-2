<?php
global $post;

$properties       = $_POST;
$properties['ID'] = CB2_CREATE_NEW;
$period           = CB2_Period::factory_from_properties( $properties );
$post->add_period( $period );
$post->save();

// The parent file will trap any Exceptions and output
// and set the response code to 500 in the event of an error
print( "<result>Ok</result>" );
