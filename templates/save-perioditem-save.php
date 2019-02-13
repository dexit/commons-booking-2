<?php
global $post;

if ( ! $post instanceof CB2_PeriodItem )
	throw new Exception( 'CB2_PeriodItem required for popup' );

$ID            = $post->ID;
$post_type     = $post->post_type;

print( "[$ID/$post_type] Ok :)" );
