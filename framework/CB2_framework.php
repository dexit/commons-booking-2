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
 * Libraries - managed by Composer
 */
require_once( CB2_PLUGIN_ROOT . 'admin/includes/CB2_Metaboxes.php' );
require_once( CB2_PLUGIN_ROOT . 'admin/includes/lib/yasumi/src/Yasumi/Yasumi.php' ); /* TODO */
require_once( CB2_PLUGIN_ROOT . 'framework/includes/lib/cmb2-metatabs-options/code/cmb2_metatabs_options.php');


/**
 * Includes - CB2_Query period framework
 * Order is important here because it governs the installation order
 */
require_once('CB2_Query/CB2_DateTime.php');
require_once('CB2_Query/CB2_Query.php');
require_once('CB2_Query/CB2_Database.php');
require_once('CB2_Query/CB2_PostNavigator.php');
require_once('CB2_Query/CB2_DatabaseTable_PostNavigator.php');
require_once('CB2_Query/CB2_PeriodEntity.php');
require_once('CB2_Query/CB2_PeriodInst.php');
require_once('CB2_Query/CB2_PeriodInteractionStrategies.php');
require_once('CB2_Query/CB2_Time_Classes.php');
require_once('CB2_Query/WP_Query_integration.php');
require_once('CB2_Query/CB2_Forms.php');

/**
 * Includes - CB2 Post Types
 */
require_once('CB2_Entities/CB2_Post.php');
require_once('CB2_Entities/CB2_Item.php');
require_once('CB2_Entities/CB2_Location.php');
require_once('CB2_Entities/CB2_User.php');


/**
 * Includes - Classes @TODO: Clean up
 */
require_once('includes/CB2_Strings.php');
require_once('includes/CB2_Template_Tags.php');
require_once('CB2_Settings/CB2_Settings.php');
require_once('includes/CB2_Holidays.php');
require_once('includes/CB2_Codes.php');

/**
 * Includes - Functions
 */
require_once('includes/get_template_part.php');
require_once('includes/CB2_template_functions.php');
require_once('includes/helper_functions.php');

