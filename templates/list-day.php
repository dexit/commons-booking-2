<li id="post-<?php the_ID(); ?>" <?php CB2::post_class(); ?>>
	<header class="entry-header">
		<?php the_title( '<h3 class="entry-title">', '</h3>' ); ?>
	</header>
	<div class="entry-content">
		<ul class="cb2-subposts">
			<?php CB2::the_inner_loop(); ?>
		</ul>
	</div><!-- .entry-content -->

	<?php CB2::the_context_menu(); ?>
</li>

