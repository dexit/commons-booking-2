<tr id="post-<?php the_ID(); ?>" <?php CB2::post_class(); ?>>
	<?php
		// Empty day cells before the startdate in the week starts
		global $post;
    for ($day = 0; $day < $post->pre_days(); $day++ ) {
      print( '<td class="cb2-empty-pre-cell">&nbsp;</td>' );
    }
  ?>

  <?php CB2::the_inner_loop( NULL, 'list', 'available', NULL, NULL, $template_args ); ?>
</tr>
