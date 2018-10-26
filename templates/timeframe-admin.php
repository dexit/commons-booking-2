<?php
/**
 * single timeframe in admin @TODO
 *
 * @package   CommonsBooking2
 * @author    Florian Egermann <florian@wielebenwir.de>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 *
 * @see       CB2_Enqueue::cb_template_chooser()
 */
?>
<?php	$timeframes = $template_args;
	if ( is_array( $timeframes )) { ?>
    <?php foreach ( $timeframes as $tf ) { ?>
			<?php // timeframe  ?>
        <div id="timeframe-<?php echo $tf->timeframe_id; ?>" class="cb2-timeframe <?php echo CB2_Gui::timeframe_classes(  $tf ); ?>">
					<div class="cb2-location">
							<h3 class="cb2-location-title"><?php echo CB2_Gui::post_link( $tf->location_id ); ?></h3>
							<span class="cb2-location-dates"><?php echo CB2_Gui::timeframe_format_location_dates( $tf->date_start, $tf->date_end, $tf->has_end_date ); ?></span>
							<span class="cb2-location-opening-times"><?php echo CB2_Gui::list_location_opening_times_html( $tf->location_id ); ?></span>
					</div> <? // end div.cb2-location ?>
					<span class="cb2-slot-availability"><?php echo CB2_Gui::col_format_availability( $tf->availability ); ?></span>
					<?php CB2_Gui::maybe_do_message ( $tf->message );	?>
					<?php // calendar ?>
            <ul class="cb2-calendar">
              <?php if ( is_array( $tf->calendar )) { ?>
								<?php foreach ( $tf->calendar as $cal_date => $date ) { ?>
									<li class="cb2-date weekday_<?php echo date ( 'w', strtotime( $cal_date ) );  ?>" id="<?php echo $tf->timeframe_id. '-' . $cal_date; ?>">
									  <span class="cb2-M"><?php echo date ( 'M', strtotime( $cal_date ) );  ?></span>
                    <span class="cb2-j"><?php echo date ( 'j', strtotime( $cal_date ) );  ?></span>
										<span class="cb2-holiday"><?php // holiday names will be printed here ?>
                      <?php if ( ! empty ( $date['slots'][$tf->timeframe_id] ) && is_array( $date['slots'][$tf->timeframe_id] ) ) {	?></span>
                        <ul class="cb2-slots"></span>
													<?php foreach ( $date['slots'][$tf->timeframe_id] as $slot ) { ?>
															<li id="<?php echo $slot['slot_id']; ?>" class="cb2-slot" alt="<?php echo esc_html( $slot['description'] ); ?>" <?php echo CB2_Gui::slot_attributes( $slot ); ?>>
															</li>
                            <?php } // endforeach $slots ?>
                        	</ul>
                        <?php } // if ( is_array( $date['slots'] ) ) { ?>
                      </li><? // end li.cb2-date ?>
                    <?php } // endforeach $cal ?>
                <?php } //if ( is_array( $tf->calendar ))  ?>
            </ul><? // end ul.cb2-calendar ?>
        </div> <? // end div.cb2-timeframe ?>
    <?php } // endforeach $tfs ?>
<?php } //if ( is_array( $tfs )) ?>
