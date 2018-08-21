<?php // echo "template : list-day.php <br>"; ?>
<?php global $post; ?>
<td id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<header class="entry-header">

		<span class="cb-M"><?php echo $post->date->format('M');?></span>
		<span class="cb-j"><?php echo $post->date->format('d');?></span>
		
	</header>
	<div class="entry-content">
		<div class="cb2-subposts">
			<?php  the_inner_loop(); ?>

		</div>
	</div><!-- .entry-content -->
</td>

