<?php if ( CB2::is_top_priority() ) { ?>
	<li id="post-<?php the_ID(); ?>" <?php CB2::post_class(); ?>>
		<div class="cb2-details">
			<?php CB2::the_edit_post_link( CB2::get_the_title(), '<span class="edit-link">', '</span>' ); ?>
			<?php CB2::the_logs(); ?>
		</div>

		<a class="thickbox cb2-bald cb2-debug-control" name="debug" href="?inlineId=debug_popup_<?php the_ID(); ?>&amp;title=debug&amp;width=300&amp;height=500&amp;#TB_inline"></a>
		<div id="debug_popup_<?php the_ID(); ?>" style="display:none;"><?php CB2::the_debug(); ?></div>
	</li>
<?php } ?>
