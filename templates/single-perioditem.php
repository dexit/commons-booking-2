<?php echo "template : single-perioditem.php <br>"; ?>
<tr id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<!-- td><header class="entry-header">
		<?php the_title( '<h4 class="entry-title">', '</h4>' ); ?>
	</header></td -->
	<?php cb2_the_fields( CB_PeriodItem::$standard_fields ); ?>

	<td><footer class="entry-footer">
		<?php
			edit_post_link(
				__( 'Edit', 'twentysixteen' ),
				'<span class="edit-link">',
				'</span>'
			);
		?>
	</footer><!-- .entry-footer --></td>
</tr>
