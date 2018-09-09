<?php if ( is_top_priority() ) { ?>
	<div id="post-<?php the_ID(); ?>" <?php post_class( 'cb2-template-available' ); ?>>
		<input class="cb2-perioditem-selector" type="checkbox" id="perioditem-<?php the_ID(); ?>" name="booked-perioditems" value="<?php the_ID(); ?>"/>
		<?php the_title( '<h4 class="entry-title">', '</h4>' ); ?>
		<!-- ?php
			edit_post_link(
				__( 'Edit', 'twentysixteen' ),
				'<span class="edit-link">',
				'</span>'
			);
		? -->
	</div>
<?php } ?>
