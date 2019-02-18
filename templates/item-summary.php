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

$item = CB2_Query::get_post_with_type('item', $template_args['item_id'] );

?>
<div class="cb2-item-summary">
	<div class="cb2-item-header"><h3>Item:<?php echo $item->post_title; ?></h3></div>
</div>


