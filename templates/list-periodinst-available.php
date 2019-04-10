<?php
	// Range selection on the front end
	if ( CB2::is_top_priority() ) {
		$can_select  = CB2::can_select();
		$can_include = CB2::can_include();
		$selectable  = ( $can_select  ? 'cb2-selectable' : 'cb2-not-selectable' );
		$includable  = ( $can_include ? 'cb2-includable' : 'cb2-not-includable' );
	?>
	<li id="post-<?php the_ID(); ?>" <?php CB2::post_class( "cb2-template-available $selectable $includable" ); ?> style="background-color:<?php CB2::the_colour(); ?>">
		<div class="cb2-details">
			<?php if ( $can_select ) { ?>
				<input class="cb2-periodinst-selector" type="checkbox" id="periodinst-<?php the_ID(); ?>" name="<?php CB2::the_post_type(); ?>s[]" value="<?php the_ID(); ?>"/>
				<span class="cb2-time-period"><?php CB2::the_time_period(); ?></span>
			<?php } ?>
			<div class="cb2-debug-period-info">
				<?php CB2::the_period_status_type_name(); ?>
				<?php CB2::the_blocked(); ?>
				<?php CB2::the_logs(); ?>
			</div>
		</div>
		<?php CB2::the_debug_popup(); ?>
	</li>
<?php } ?>
