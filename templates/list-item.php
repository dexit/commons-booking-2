<?php echo "template : list-item.php <br>"; ?>

<?php
/**
 * Items in archives, list timeframes below item excerpt.
 *
 * @package   Commons_Booking
 * @author    Annesley Newholm <annesley_newholm@yahoo.it>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 *
 * @see       CB_Enqueue::cb_template_chooser()
 */
?>
<?php the_title(); ?>
<?php the_excerpt(); ?>
<div class="cb2-calendar"><header class="entry-header"><h1 class="entry-title">calendar</h1></header>
	<div class="entry-content">
		<table class="cb2-subposts"><tbody>
			<?php the_inner_loop(); ?>
		</tbody></table>
	</div>
</div>
