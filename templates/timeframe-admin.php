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
 * @see       CB_Enqueue::cb_template_chooser()
 */
?>
<?php	$timeframes = $template_args;
	if ( is_array( $timeframes )) { ?>
    <?php foreach ( $timeframes as $tf ) { ?>
			<?php // timeframe  ?>
        <div id="timeframe-<?php echo $tf->timeframe_id; ?>" class="cb-timeframe <?php echo CB_Gui::timeframe_classes(  $tf ); ?>">
					<div class="cb-location">
							<h3 class="cb-location-title"><?php echo CB_Gui::post_link( $tf->location_id ); ?></h3>
							<span class="cb-location-dates"><?php echo CB_Gui::timeframe_format_location_dates( $tf->date_start, $tf->date_end, $tf->has_end_date ); ?></span>
							<span class="cb-location-opening-times"><?php echo CB_Gui::list_location_opening_times_html( $tf->location_id ); ?></span>
					</div> <? // end div.cb-location ?>
					<span class="cb-slot-availability"><?php echo CB_Gui::col_format_availability( $tf->availability ); ?></span>
					<?php CB_Gui::maybe_do_message ( $tf->message );	?>
					<?php // calendar ?>
            <ul class="cb-calendar">
              <?php if ( is_array( $tf->calendar )) { ?>
								<?php foreach ( $tf->calendar as $cal_date => $date ) { ?>
									<li class="cb-date weekday_<?php echo date ( 'w', strtotime( $cal_date ) );  ?>" id="<?php echo $tf->timeframe_id. '-' . $cal_date; ?>">
									  <span class="cb-M"><?php echo date ( 'M', strtotime( $cal_date ) );  ?></span>
                    <span class="cb-j"><?php echo date ( 'j', strtotime( $cal_date ) );  ?></span>
										<span class="cb-holiday"><?php // holiday names will be printed here ?>
                      <?php if ( ! empty ( $date['slots'][$tf->timeframe_id] ) && is_array( $date['slots'][$tf->timeframe_id] ) ) {	?></span>
                        <ul class="cb-slots"></span>
													<?php foreach ( $date['slots'][$tf->timeframe_id] as $slot ) { ?>
															<li id="<?php echo $slot['slot_id']; ?>" class="cb-slot" alt="<?php echo esc_html( $slot['description'] ); ?>" <?php echo CB_Gui::slot_attributes( $slot ); ?>>
															</li>
                            <?php } // endforeach $slots ?>
                        	</ul>
                        <?php } // if ( is_array( $date['slots'] ) ) { ?>
                      </li><? // end li.cb-date ?>
                    <?php } // endforeach $cal ?>
                <?php } //if ( is_array( $tf->calendar ))  ?>
            </ul><? // end ul.cb-calendar ?>
        </div> <? // end div.cb-timeframe ?>
    <?php } // endforeach $tfs ?>
<?php } //if ( is_array( $tfs )) ?>
