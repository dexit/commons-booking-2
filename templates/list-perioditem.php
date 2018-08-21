<?php // echo "template : list-perioditem.php <br>"; ?>

<?php
	global $post, $wp_query;
	if ( $post->is_top_priority() || (
		isset( $wp_query->query_vars['show_overridden_periods'] ) &&
		$wp_query->query_vars['show_overridden_periods'] != 'no'
	) ) { ?>

		<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			<?php the_debug(); ?>
			<?php if (get_the_title() == 'available') {
				the_title();
			} ?>
			<?php // the_title( '<h4 class="entry-title">', '</h4>' ); ?>
			<?php echo 'type: ' . get_the_field('period_group_type'); ?>
			<?php // the_fields( CB_PeriodItem::$standard_fields, '<div>', '</div>' ); ?>
		</div>
<?php } ?>
