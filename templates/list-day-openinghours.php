<td id="post-<?php the_ID(); ?>" <?php CB2::post_class(); ?>>
	<header class="entry-header">
		<?php the_date( 'D', '<h3 class="entry-title">', '</h3>' ); ?>
	</header>

	<div class="entry-content">
		<?php CB2::the_inner_loop( NULL, 'list', 'openinghours' ); ?>

		<div><select name="period_IDs[]">
			<option value="<?php the_date( 'D' ); ?>:08:00-12:00"/>8:00 am - 12:00 pm</option>
			<option value=""/>-- select --</option>
		</select></div>
		<div><select name="period_IDs[]">
			<option value=""/>-- select --</option>
			<option value="<?php echo get_the_date( 'D' ); ?>:13:00-18:00"/>1:00 pm - 6:00 pm</option>
		</select></div>

		<?php $add_times_text = __( 'Add Slot' ); ?>
		<a class="thickbox" name="opening times" href="?inlineId=day_popup_<?php the_ID(); ?>&amp;title=opening+hours&amp;width=300&amp;height=500&amp;#TB_inline">
			<button class="cb2-perioditem-selector"><?php echo $add_times_text; ?></button>
		</a>
	</div><!-- .entry-content -->

	<div id="day_popup_<?php the_ID(); ?>" style="display:none;">
		wahhhh
	</div>
	<?php CB2::the_context_menu(); ?>
</td>

