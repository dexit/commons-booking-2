<?php if ( is_top_priority() ) { ?>
	<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
		<?php the_edit_post_link( get_the_title(), '<span class="edit-link">', '</span>' ); ?>
		<a class="thickbox cb2-debug-control" name="debug" href="?inlineId=debug_popup_<?php the_ID(); ?>&amp;title=debug&amp;width=300&amp;height=500&amp;#TB_inline"></a>
		<div id="debug_popup_<?php the_ID(); ?>" style="display:none;"><?php the_debug(); ?></div>
	</div>
<?php } ?>
