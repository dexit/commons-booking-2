<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<header class="entry-header">
		<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
	</header>
	<div class="entry-content">
		<?php the_content(); ?>
		<table class="cb2-subposts"><tbody>
			<?php
				global $post;

				$outer_post = $post;
				while ( $outer_post->have_posts() ) {
					$outer_post->the_post(); // Sets the global $post
					cb_get_template_part( 'commons-booking', $post->template() );
				}
				$post = &$outer_post;
			?>
		</tbody></table>
	</div><!-- .entry-content -->

	<footer class="entry-footer">
		<?php
			edit_post_link(
				sprintf(
					/* translators: %s: Name of current post */
					__( 'Edit<span class="screen-reader-text"> "%s"</span>', 'twentysixteen' ),
					get_the_title()
				),
				'<span class="edit-link">',
				'</span>'
			);
		?>
	</footer><!-- .entry-footer -->
</div>

