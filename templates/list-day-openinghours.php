<td id="post-<?php the_ID(); ?>" <?php CB2::post_class(); ?>>
	<header class="entry-header">
		<?php the_date( 'D', '<h3 class="entry-title">', '</h3>' ); ?>
	</header>

	<div class="entry-content">
		<?php CB2::the_inner_loop( NULL, 'list', 'openinghours' ); ?>

		<!-- Using echo get_the_date() because there is a bug in the_date() -->
		<div class="cb2-removable-item"><input class="cb2-hidden" name="period_IDs[]" value="<?php echo get_the_date( 'D' ); ?>:08:00-12:00"/><a href="#">08:00 - 12:00</a></div>
		<div class="cb2-removable-item"><input class="cb2-hidden" name="period_IDs[]" value="<?php echo get_the_date( 'D' ); ?>:13:00-18:00"/><a href="#">13:00 - 18:00</a></div>

		<?php $add_times_text = '<span class="cb2-todo">' . __( 'Add Slot' ) . '</span>'; ?>
		<a class="thickbox" name="opening times" href="?inlineId=day_popup_<?php the_ID(); ?>&amp;title=opening+hours&amp;width=300&amp;height=500&amp;#TB_inline">
			<button class="cb2-perioditem-selector"><?php echo $add_times_text; ?></button>
		</a>
	</div><!-- .entry-content -->

	<div id="day_popup_<?php the_ID(); ?>" style="display:none;">
		wahhhh
	</div>
	<?php CB2::the_context_menu(); ?>
</td>

