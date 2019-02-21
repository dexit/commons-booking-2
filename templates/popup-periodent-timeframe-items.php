<?php
global $post;

$period_entity  = $post->period_entity;
$period         = $post->period;
$location       = $period_entity->location;
$item           = $period_entity->item;
$date_period    = $period->summary_date_period();
?>
<li>
	<div class='cb2-item-name' style='background-color:<?php CB2::the_colour( $item ); ?>'>
		<a href='<?php the_permalink( $item ); ?>'><?php print( $item->post_title ); ?></a>
	</div>
	at <span class='cb2-location-name'><?php print( $location->post_title ); ?></span>,
	<span class='cb2-time-period'><?php print( $date_period ); ?></span>
</li>
