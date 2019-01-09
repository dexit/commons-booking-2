<?php if ( CB2::is_top_priority() ) { ?>
	<div id="post-<?php the_ID(); ?>" <?php CB2::post_class( 'cb2-template-available' ); ?>>
		<div class="cb2-details">
			<a class="thickbox cb2-bald cb2-debug-control" name="debug" href="?inlineId=debug_popup_<?php the_ID(); ?>&amp;title=debug&amp;width=300&amp;height=500&amp;#TB_inline" />
			<a class="thickbox" name="opening times" href="?inlineId=perioditem_popup_<?php the_ID(); ?>&amp;title=opening+hours&amp;width=300&amp;height=500&amp;#TB_inline">
				<input name="period_IDs[]" type="text" value="<?php the_date( 'D' ); ?>:<?php CB2::the_time_period(); ?>"/>
			</a>
		</div>

		<div id="debug_popup_<?php the_ID(); ?>" style="display:none;"><?php CB2::the_debug(); ?></div>
		<div id="perioditem_popup_<?php the_ID(); ?>" style="display:none;">
			wahhhh
		</div>
	</div>
<?php } ?>
