<?php
// TODO: Temporary fix until instance_container ready
CB2_PostNavigator::clear_object_caches();
?>
<div <?php CB2::post_class( 'cb2-content cb-item-wrapper cb-box' ); ?>>
	<!-- h2 class="cb-big">
		<a href="<?php CB2::the_permalink(); ?>" title="<?php CB2::the_title_attribute(); ?>"><?php CB2::the_title(); ?></a>
	</h2 -->

  <div class="cb-list-item-description">
		<div class="align-left"><?php the_post_thumbnail(); ?></div>
		<div class="cb-table">
			<form class="align-right" action="<?php CB2::the_permalink(); ?>"><div>
				<input class="cb-button" type="submit" value="<?php print( __( 'Book here' ) ); ?>"/>
			</div></form>

			[cb2_item_current_location]
    </div>
	</div>
</li>

