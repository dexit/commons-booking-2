<tr id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<?php the_content(); ?>

	<?php
		global $post;

		$outer_post = $post;
		while ( $outer_post->have_posts() ) {
			$outer_post->the_post(); // Sets the global $post
			cb_get_template_part( 'commons-booking', $post->template() );
		}
		$post = &$outer_post;
	?>
</tr>
