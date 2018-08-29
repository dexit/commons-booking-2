<tr id="post-<?php the_ID(); ?>" onclick="
	var checked = document.getElementById('perioditem-<?php the_ID(); ?>').getAttribute('checked') == '1';
	if (checked) {
		document.getElementById('perioditem-<?php the_ID(); ?>').removeAttribute('checked');
		this.setAttribute( 'class', this.getAttribute('class').replace(/cb2-booked/, '') );
	} else {
		document.getElementById('perioditem-<?php the_ID(); ?>').setAttribute('checked', '1');
		this.setAttribute( 'class', this.getAttribute('class') + ' cb2-booked' );
	}
" <?php post_class(); ?>>
	<!-- td><header class="entry-header">
		<?php the_title( '<h4 class="entry-title">', '</h4>' ); ?>
	</header></td -->

	<td><input type="checkbox" id="perioditem-<?php the_ID(); ?>" name="booked-perioditems" value="<?php the_ID(); ?>"/></td>

	<?php the_content(); ?>
	<!-- ?php if ( WP_DEBUG ) the_debug(); ? -->
	<!-- ?php cb2_the_fields( CB_PeriodItem::$standard_fields ); ? -->

	<!-- td><footer class="entry-footer">
		<?php
			edit_post_link(
				__( 'Edit', 'twentysixteen' ),
				'<span class="edit-link">',
				'</span>'
			);
		?>
	</footer></td -->
</tr>
