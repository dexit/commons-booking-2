<?php // echo "template : list-perioditem.php <br>"; ?>

<?php 
global $post;
	if ($post->is_top_priority()) { ?>

		<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			<?php if (get_the_title() == 'available') {

				the_title();
			} ?>
		<?php // the_title( '<h4 class="entry-title">', '</h4>' ); ?>
		<?php echo 'type: ' . get_the_field('period_group_type'); ?>
	<?php // the_fields( CB_PeriodItem::$standard_fields, '<div>', '</div>' ); ?>

	
</div>


<?php } ?>
