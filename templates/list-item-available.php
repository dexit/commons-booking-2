<li id="post-<?php the_ID(); ?>" <?php CB2::post_class(); ?>>
	<?php CB2::the_title(); ?>
	<?php the_excerpt(); ?>
	<div class="cb2-calendar">
		<header class="entry-header"><h1 class="entry-title">calendar</h1></header>
		<ul class="cb2-subposts">
			<?php CB2::the_inner_loop( $template_args, 'list', 'available' ); ?>
		</ul>
	</div>
</li>

