<td id="post-<?php the_ID(); ?>" <?php CB2::post_class(); ?>>
	<header class="entry-header">
		<?php the_date( 'D', '<h3 class="entry-title">', '</h3>' ); ?>
	</header>

	<div class="entry-content">
		<table class="cb2-subposts"><tbody>
			<?php CB2::the_inner_loop( NULL, 'list', 'openinghours' ); ?>
		</tbody></table>

		<?php $add_times_text = __( 'Add Times' ); ?>
		<a class="thickbox" name="opening times" href="?inlineId=day_popup_<?php the_ID(); ?>&amp;title=opening+hours&amp;width=300&amp;height=500&amp;#TB_inline">
			<button class="cb2-perioditem-selector"><?php echo $add_times_text; ?></button>
		</a>
	</div><!-- .entry-content -->

	<div id="day_popup_<?php the_ID(); ?>" style="display:none;">
		wahhhh
	</div>
	<?php CB2::the_context_menu(); ?>
</td>

