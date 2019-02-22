<div class="wrap">
<?php
global $wp_query;

// We set the global $wp_query so that all template functions will work
// And also so pre_get_posts will not bulk with no global $wp_query
wp_reset_query();
$wp_query = CB2_PeriodInteractionStrategy::factory_from_args( $_REQUEST, array(
	'enddate'          => CB2_DateTime::next_week_end()->format( CB2_Query::$datetime_format ),
	//'display-strategy' => 'CB2_AllItemAvailability',
) );
$title_text   = __( 'Dashboard' );
?>
<h1>Commons Booking 2 <?php print( $title_text ); ?></h1>
	<div class="cb2-calendar">
		<div class="entry-content" style="width:100%;">
			<?php CB2::the_calendar_header( $wp_query ); ?>
			<ul class="cb2-subposts">
				<!-- usually weeks -->
				<?php CB2::the_inner_loop( array( 'action' => '' ), $wp_query ); ?>
			</ul>
			<?php CB2::the_calendar_footer( $wp_query ); ?>
		</div><!-- .entry-content -->
	</div>
	<br style="clear:both;"/>
	<?php if ( WP_DEBUG ) krumo( $wp_query ); ?>
</div>
