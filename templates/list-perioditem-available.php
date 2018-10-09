<?php if ( is_top_priority() ) { ?>
	<div id="post-<?php the_ID(); ?>" <?php post_class( 'cb2-template-available' ); ?>>
		<input class="cb2-perioditem-selector" type="checkbox" id="perioditem-<?php the_ID(); ?>" name="booked-perioditems" value="<?php the_ID(); ?>"/><span class="cb2-time-period"><?php the_time_period(); ?></span>
		<?php the_period_status_type_name(); ?>
		<a class="thickbox cb2-debug-control" name="debug" href="?inlineId=debug_popup_<?php the_ID(); ?>&amp;title=debug&amp;width=300&amp;height=500&amp;#TB_inline"></a>
		<div id="debug_popup_<?php the_ID(); ?>" style="display:none;"><?php the_debug(); ?></div>
	</div>
<?php } ?>
