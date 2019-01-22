<li id="post-<?php the_ID(); ?>" <?php CB2::post_class(); ?>>
  <ul class="cb2-subposts">
		<?php
			// Empty day cells before the startdate in the week starts
			global $post;
			for ($day = 0; $day < $post->pre_days(); $day++ ) {
				print( '<li class="cb2-empty-pre-cell type-day">&nbsp;</li>' );
			}
		?>

		<?php CB2::the_inner_loop( NULL, 'list', 'openinghours' ); ?>
	</ul>
</li>
