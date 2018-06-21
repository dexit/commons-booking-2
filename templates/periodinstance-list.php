<?php if ( ! is_pseudo() ) { ?>
<tr id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<?php the_content(); ?>
	<?php the_debug(); ?>
	<?php the_fields( CB_Period_Instance::$standard_fields ); ?>
</tr>
<?php } ?>
