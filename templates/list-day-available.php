<td id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<header class="entry-header">
		<?php the_title( '<h3 class="entry-title">', '</h3>' ); ?>
	</header>
	<div class="entry-content">
		<table class="cb2-subposts"><tbody>
			<?php the_inner_loop( NULL, 'list', 'available' ); ?>
		</tbody></table>
	</div><!-- .entry-content -->
</td>

