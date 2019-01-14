<?php if ( CB2::is_top_priority() ) { ?>
	<div id="post-<?php the_ID(); ?>" <?php CB2::post_class( 'cb2-template-available cb2-selectable' ); ?>>
		<div class="cb2-details">
			<?php if ( CB2::can_select() ) { ?>
				<input class="cb2-perioditem-selector" type="checkbox" id="perioditem-<?php the_ID(); ?>" name="<?php CB2::the_post_type(); ?>s[]" value="<?php the_ID(); ?>"/>
				<span class="cb2-time-period"><?php CB2::the_time_period(); ?></span>
			<?php } ?>
			<?php CB2::the_period_status_type_name(); ?>
			<?php CB2::the_blocked(); ?>
		</div>

		<a class="thickbox cb2-bald cb2-debug-control" name="debug" href="?inlineId=debug_popup_<?php the_ID(); ?>&amp;title=debug&amp;width=300&amp;height=500&amp;#TB_inline"></a>
		<div id="debug_popup_<?php the_ID(); ?>" style="display:none;"><?php CB2::the_debug(); ?></div>
	</div>
<?php } ?>
