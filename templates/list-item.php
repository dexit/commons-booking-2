<?php
/**
 * Items in archives, list timeframes below item excerpt.
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
<?php CB2::the_title(); ?>
<?php the_excerpt(); ?>
<li class="cb2-calendar"><header class="entry-header"><h1 class="entry-title">calendar</h1></header>
		<ul class="cb2-subposts">
			<?php CB2::the_inner_loop( $template_args ); ?>
		</ul>
</li>
