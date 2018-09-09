<?php if ( is_top_priority() ) { ?>
	<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
		<?php the_debug(); ?>
		<?php the_title(); ?>
	</div>
<?php } ?>
