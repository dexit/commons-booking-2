<tr id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<?php the_content(); ?>

	<?php
    global $post;

    for ($day = 1; $day < $post->pre_week_days(); $day++ ) {
      ?>
      <td class='cb2-empty-pre-cell'>&nbsp;</td>
      <?php
    }

    $outer_post = $post;
		while ( $outer_post->have_posts() ) {
			$outer_post->the_post(); // Sets the global $post
			cb_get_template_part( 'commons-booking', $post->template() );
		}
		$post = &$outer_post;
	?>
</tr>
