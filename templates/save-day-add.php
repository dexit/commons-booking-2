<?php
global $post; // CB2_Day

$properties       = $_POST;
$properties['ID'] = CB2_CREATE_NEW;
$period_entity    = CB2_PeriodEntity::factory_from_properties( $properties );
$period_entity->save();

print( "<result>Ok</result>" );
