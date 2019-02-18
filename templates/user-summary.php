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

$user = CB2_Query::get_post_with_type('user', $template_args['user_id'] );

?>
<div class="cb2-user-summary">
	<div class="cb2-user-header"><h3>User: <?php echo $user->post_title; ?></h3></div>
</div>


