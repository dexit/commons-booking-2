<li id="post-<?php the_ID(); ?>" <?php CB2::post_class(); ?>>
	<header class="entry-header">
		<?php the_date( 'D', '<h3 class="entry-title">', '</h3>' ); ?>
	</header>

	<ul class="cb2-subposts">
		<?php CB2::the_inner_loop( $template_args, NULL, 'list', 'openinghours' ); ?>
	</ul>

	<!-- Using echo get_the_date() because there is a bug in the_date() -->
	<div class="cb2-removable-item"><input class="cb2-hidden" name="period_IDs[]" value="<?php echo get_the_date( 'D' ); ?>:08:00-12:00"/><a href="#">08:00 - 12:00</a></div>
	<div class="cb2-removable-item"><input class="cb2-hidden" name="period_IDs[]" value="<?php echo get_the_date( 'D' ); ?>:13:00-18:00"/><a href="#">13:00 - 18:00</a></div>

	<?php
		$add_times_text  = '<span class="cb2-todo">' . __( 'Add Hours' ) . '</span>';
		$add_times_title = __( 'Add Opening Hours' );
	?>
	<a class="thickbox" name="<?php echo $add_times_title ?>" href="?inlineId=day_popup_<?php the_ID(); ?>&amp;title=<?php echo $add_times_title ?>&amp;width=400&amp;height=500&amp;#TB_inline">
		<?php echo $add_times_text; ?>
	</a>
</div><!-- .entry-content -->

<div id="day_popup_<?php the_ID(); ?>" class="cb2-day-popup" style="display:none;">
	<div style="background-color:#f7f7f7;margin: -2px -15px -15px -15px;padding: 2px 15px 15px 15px;">
		<h2><?php echo __( 'Time' ); ?></h2>
		<input size="10" value="13:00" /> <input size="10" value="18:00" />

		<h2><?php echo __( 'Repeat' ); ?></h2>
		<ul>
			<?php
				$days_of_week = CB2_Week::days_of_week();
				for ( $i = 0; $i < count( $days_of_week ); $i++ ) {
					print( "<li style='float:left;margin-right:8px;' ><input id='day_$i' name='day[]' type='checkbox' value='$i'/> <label for='day_$i'>$days_of_week[$i]</label></li>" );
				}
			?>
		</ul>

		<h2 style="clear:both;"><?php echo __( 'Title' ); ?></h2>
		<input size="25"/>

		<hr/>
		<div class="cb2-popup-footer" style="background-color:#fff;">
			<!-- NOTE: the whole calendar and WordPress edit form is within a form
				so we cannot place another one here.
				This must be a purely JavaScript process.
			-->
			<input style="float:right;" type="button" value="Save" />
		</div>
	</div>
	<?php CB2::the_context_menu(); ?>
</li>

