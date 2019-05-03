<li id="post-<?php the_ID(); ?>" <?php CB2::post_class( 'cb-row' ); ?>>
	<form class="align-right" action="<?php CB2::the_permalink(); ?>"><div>
		<input type="submit" class="cb-button" value="<?php print( __( 'Book here' ) ); ?>"/>
	</div></form>

	<h3 class="cb-big"><a href="<?php CB2::the_permalink(); ?>"><?php CB2::the_title(); ?></a></h3>
	<?php the_post_thumbnail(); ?>
</li>

