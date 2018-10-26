<?php
/**
 * Items in archives, list timeframes below item excerpt.
 *
 * @package   CommonsBooking2
 * @author    Florian Egermann <florian@wielebenwir.de>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 *
 * @see       CB2_Enqueue::cb_template_chooser()
 *
*/
$cal = $template_args;
	if ( !empty ( $cal )) { ?>
		<?php // calendar ?>
		<?php var_dump($cal);
		$cb_calendar_class = 'cb2-calendar-single';
		if (isset($cal['timeframe_id']) && is_array($cal['timeframe_id']) && count($cal['timeframe_id']) > 1) {
			$cb_calendar_class = 'cb2-calendar-grouped';
		} ?>
		<div class="cb2-calendar ">
			<ul class="cb2-calendar <?php echo $cb_calendar_class; ?>">
				<?php if ( is_array( $cal['calendar'] )) { ?>
					<?php foreach ( $cal['calendar'] as $cal_date => $date ) { ?>
						<li class="cb2-date weekday_<?php echo date ( 'w', strtotime( $cal_date ) );  ?>" id="date-<?php echo $cal_date; ?>" title="<?php echo date ( 'M j', strtotime( $cal_date ) );  ?>">
							<span class="cb2-holiday"><?php echo $date['holiday']; ?></span>
							<span class="cb2-M"><?php echo date ( 'M', strtotime( $cal_date ) );  ?></span>
							<span class="cb2-j"><?php echo date ( 'j', strtotime( $cal_date ) );  ?></span>
								<?php if (is_array($date['slots'])) { ?>
									<ul class="cb2-slots">
										<?php $available_slot_count = 0 ;?>
										<?php foreach ( $date['slots'] as $slot ) { ?>

											<li id="<?php echo $slot['slot_id']; ?>" class="cb2-slot" alt="<?php echo esc_html( $slot['description'] ); ?>" <?php echo CB2_Gui::slot_attributes( $slot ); ?> >

												<span class="cb2-item-dot"></span>
												<!-- checkbox or similar here -->
											</li>
											<?php if ($slot['state'] == 'allow-booking') {
												$available_slot_count++;
											} ?>
										<?php } // endforeach $slots ?>
										<?php if ($available_slot_count > 3) { ?>
											<li class="cb2-slot-count">+<?php echo $available_slot_count - 3;?></li>
										<?php } ?>
									</ul>
								<?php } ?>
							</li><?php // end li.cb2-date ?>
					<?php } // endforeach $cal ?>
				<?php } //if ( is_array( $cal['calendar'] ))  ?>
			</ul><?php // end ul.cb2-calendar ?>
	</div> <?php // end div.cb2-calendar ?>
<?php } //if ( is_array( $calendar )) 	?>
<?php  // print_r($cal) ;?>
