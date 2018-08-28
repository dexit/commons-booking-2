<?php // echo "template : list-perioditem.php <br>"; ?>

<?php
	global $post, $wp_query;
	$show_overridden_periods = (
		isset( $wp_query->query_vars['show_overridden_periods'] ) &&
		$wp_query->query_vars['show_overridden_periods'] != 'no'
	);

	if ( $post->is_top_priority() || $show_overridden_periods ) { ?>
		<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			<?php the_debug(); ?>
			<?php if (get_the_title() == 'available') {
				the_title();
			} ?>
			<?php // the_title( '<h4 class="entry-title">', '</h4>' ); ?>
			<?php echo 'type: ' . cb2_get_field('period_group_type'); ?>
			<?php // cb2_the_fields( CB_PeriodItem::$standard_fields, '<div>', '</div>' ); ?>
		</div>
<?php } ?>
