<?php
global $post;

if ( ! $post instanceof CB2_PeriodInst )
	throw new Exception( 'CB2_PeriodInst required for popup' );

$periodinst    = &$post;
$ID            = $periodinst->ID;
$properties    = $_POST;
$period        = $periodinst->period;
$period_group  = $periodinst->period_entity->period_group;
$save_type     = ( isset( $_REQUEST['save_type'] ) ? $_REQUEST['save_type'] : NULL );

// ----------------------- blocking
$block            = isset( $properties['blocked'] );
print( "<!-- CB2_PeriodInst::block(" . ( $block ? 'TRUE' : 'FALSE' ) . ") -->\n" );
$periodinst->block( $block );

switch ( $save_type ) {
	case 'SOT': {
		// TODO: Save this instance only
		throw new Exception( __( 'Save this instance only not implemented' ) );
		break;
	}
	case 'SFH': {
		// Save recurrence from here onwards
		// this will create another period from this point onwards
		// essentially spliting the input period
		// This is useful when wanting to change the future but not the past
		if ( $period->recurrence_type ) {
			$new_period = $period_group->split_period_at_instance( $periodinst, $properties, $instance_container );
			$period_group->save( TRUE ); // Update changed source period
			break;
		}
		// else failover to saving whole period
	}
	case 'SAI':
	default:    {
		// Save all instances
		// Here we force_properties because
		// the period has already been loaded and cached with the old values
		// and we want to re-create
		print( "<!-- saving CB2_Period[ID: $period->ID] -->\n" );
		$properties['ID'] = $period->ID;
		$period           = CB2_Period::factory_from_properties( $properties, $instance_container, TRUE ); // force_properties
		$period->save( TRUE ); // Update mode
		break;
	}
}

print( "<result>Ok</result>" );
