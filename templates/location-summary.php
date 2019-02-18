<?php
/**
 * Item summary @TODO
 *
 * @package   CommonsBooking2
 * @author    Annesley Newholm <annesley_newholm@yahoo.it>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 *
 * @see       CB2_Enqueue::cb_template_chooser()
 */

$location = CB2_Query::get_post_with_type('location', $template_args['location_id'] );
?>
<div class="cb2-location-summary">
	<div class="cb2-location-header"><h3>Location:<?php echo $location->post_title; ?></h3></div>
	<div class="cb2-location-address"><?php echo $location->geo_address; ?></div>
</div>


