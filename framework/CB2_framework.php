<?php

/**
 * Framework base, provides period framework in front- and backend.
 *
 * @package   CommonsBooking2
 * @author    Florian Egermann <florian@wielebenwir.de>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 */


/**
 * Libraries
 */
require_once('includes/lib/cmb2/init.php');
require_once('includes/lib/cmb2-grid/Cmb2GridPluginLoad.php');
require_once('includes/lib/cmb2-tabs/cmb2-tabs.php');
require_once('includes/lib/cmb2-field-icon/cmb2-field-icon.php');
/**
 * @TODO move libs
 * Libs temporarily in /lib-temp because
 * not availabe via composer atm
 */
require_once('includes/lib-temp/CMB2-field-Calendar/cmb-field-calendar.php');
require_once('includes/lib-temp/CMB2-field-Paragraph/cmb-field-paragraph.php');
require_once(CB_PLUGIN_ROOT . 'admin/includes/lib/yasumi/src/Yasumi/Yasumi.php'); /* TODO */


/**
 * Includes - CB_Query period framework
 */
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

/**
 * Includes - Classes
 */
require_once('includes/CB2_Settings.php');
require_once('includes/CB2_Holidays.php');
require_once('includes/CB2_Codes.php');

/**
 * Includes - Functions
 */
require_once('includes/cb_get_template_part.php');
require_once('includes/template_functions.php');
require_once('includes/helper_functions.php');

