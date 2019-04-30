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
?>
<div class="cb2-summary cb2-item-summary">
	<h3><?php echo __('Item: ', 'commons-booking-2'); ?>
		<?php CB2::the_html_permalink(); ?>
	</h3>
	<div class="cb2-item-thumb cb2-summary-image"><?php the_post_thumbnail( 'thumbnail' );?></div>
	<p class="cb2-item-excerpt"><!--?php the_excerpt();?--></p>
</div>

