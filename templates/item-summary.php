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

$item_thumb = ( has_post_thumbnail( $item->ID ) ) ? get_the_post_thumbnail( $item->ID, 'thumbnail') : '';

?>
<div class="cb2-summary cb2-item-summary">
	<h3><?php echo __('Item: ', 'commons-booking-2'); ?>
		<a href="<?php echo esc_url( get_permalink( $item->ID ) ); ?>"><?php echo $item->post_title; ?></a>
	</h3>
	<div class="cb2-item-thumb cb2-summary-image"><?php echo ( $item_thumb );?></div>
	<p class="cb2-item-excerpt"><?php echo ( $item->post_excerpt );?></p>
</div>

