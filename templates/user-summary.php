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

$user = get_user_by('id', $template_args['user_id'] );
// $user_meta = get_user_meta($user ->ID);

?>
<div class="cb2-summary cb2-user-summary">
	<h3><?php echo __('Your information', 'commons-booking-2'); ?></h3>
	<dl class="cb2-user-name">
    <dt><?php echo __('Name', 'commons-booking-2'); ?></dt>
		<dd><?php echo $user->user_firstname . ' ' . $user->user_lastname; ?></dd>
	</dl>
	<dl class="cb2-user-email">
    <dt><?php echo __('Email', 'commons-booking-2'); ?></dt>
		<dd><?php echo $user->user_email; ?></dd>
	</dl>
	<dl class="cb2-user-registered-on">
    <dt><?php echo __('Member since', 'commons-booking-2'); ?></dt>
		<dd><?php echo date_i18n(get_option('date_format'), strtotime($user->user_registered)); ?>
	</dd>
</div>


