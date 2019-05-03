<li id="post-<?php the_ID(); ?>" <?php CB2::post_class( 'cb-item-wrapper cb-box' ); ?>>
	<h2 class="cb-big">
		<a href="<?php CB2::the_permalink(); ?>" title="<?php CB2::the_title_attribute(); ?>"><?php CB2::the_title(); ?></a>
	</h2>

  <div class="cb-list-item-description">
		<div class="align-left"><?php the_post_thumbnail(); ?></div>
		<div class="cb-table">
			<form class="align-right" action="<?php CB2::the_permalink(); ?>"><div>
				<input class="cb-button" type="submit" value="<?php print( __( 'Book here' ) ); ?>"/>
			</div></form>
			<?php CB2::the_inner_loop( $template_args, NULL, 'list', $template_type ); ?>
    </div>
	</div>
</li>
