<?php

/**
 * Framework base, provides period framework in front- and backend.
 *
 *
 *
 * @package   CommonsBooking2
 * @author    Florian Egermann <florian@wielebenwir.de>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 */


/** Libraries */
require_once('includes/lib/cmb2/init.php');
require_once('includes/lib/cmb2-grid/Cmb2GridPluginLoad.php');
require_once('includes/lib/cmb2-tabs/cmb2-tabs.php');
require_once('includes/lib/cmb2-field-icon/cmb2-field-icon.php');
/** in /lib-temp because not availabe via composer atm */
require_once('includes/lib-temp/CMB2-field-Calendar/cmb-field-calendar.php');
require_once('includes/lib-temp/CMB2-field-Paragraph/cmb-field-paragraph.php');

/** Includes - CB_Query period framework */
require_once('CB2_Query/CB_Query.php');
require_once('CB2_Query/CB_Database.php');
require_once('CB2_Query/CB_PostNavigator.php');
require_once('CB2_Query/CB_PeriodItem.php');
require_once('CB2_Query/CB_Entities.php');
require_once('CB2_Query/CB_PeriodEntity.php');
require_once('CB2_Query/CB_PeriodInteractionStrategies.php');
require_once('CB2_Query/CB_Time_Classes.php');
require_once('CB2_Query/WP_Query_integration.php');
require_once('CB2_Query/CB_Forms.php');

/** Includes - other */
require_once('includes/the_template_functions.php');

