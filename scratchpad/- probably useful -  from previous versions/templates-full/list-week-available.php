<tr id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<?php the_content(); ?>

	<?php
		// Empty day cells before the startdate in the week starts
		global $post;
    for ($day = 1; $day < $post->pre_days(); $day++ ) {
      print( '<td class="cb2-empty-pre-cell">&nbsp;</td>' );
    }
  ?>

  <?php the_inner_loop( NULL, 'list', 'available' ); ?>
</tr>
