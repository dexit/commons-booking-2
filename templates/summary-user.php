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
<div class="cb2-summary cb2-user-summary">
	<h3><?php echo __('Your information', 'commons-booking-2'); ?></h3>
	<dl class="cb2-user-name">
    <dt><?php echo __('Name', 'commons-booking-2'); ?></dt>
		<dd><?php the_author(); ?></dd>
	</dl>
	<dl class="cb2-user-email">
    <dt><?php echo __('Email', 'commons-booking-2'); ?></dt>
		<dd><?php the_author_meta( 'user_email' ); ?></dd>
	</dl>
	<dl class="cb2-user-registered-on">
    <dt><?php echo __('Member since', 'commons-booking-2'); ?></dt>
		<dd><?php echo date_i18n(get_option('date_format'), strtotime( get_the_author_meta( 'user_registered' ) ) ); ?>
	</dd>
</div>


