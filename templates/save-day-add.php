<?php
global $post;

if ( ! $post instanceof CB2_Day )
	throw new Exception( 'CB2_Day required for add' );

$properties       = $_POST;
$properties['ID'] = CB2_CREATE_NEW;
$period_entity    = CB2_PeriodEntity::factory_from_properties( $properties );
$period_entity->save();

print( "<result>Ok</result>" );
