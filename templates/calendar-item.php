<?php echo "template : calendar-item.php <br>"; ?>

<?php
/**
 * Calendar of items
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
<?php global $post; ?>
<div class="cb2-calendar"><header class="entry-header"><h1 class="entry-title">Calendar</h1></header>

	<table class="cb-calendar">
		<thead>
			<tr>
				<?php
				    for($i=1;$i<8;$i++) {
						echo '<th>' . date("D",mktime(0,0,0,3,28,2009)+$i * (3600*24)) . '</th>';
					}
				?>

			</tr>
		</thead>
		<tbody>
			<?php the_inner_loop($post, 'list'); ?>
		</tbody>
	</table>
</div>
