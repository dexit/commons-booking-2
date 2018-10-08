<?php
/**
 * Represents the view for the administration dashboard.
 *
 *
 * @package   CommonsBooking2
 * @author    Florian Egermann <florian@wielebenwir.de>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 */
?>
<div class="wrap">
    <h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

    <div id="tabs" class="settings-tab">
		<ul>
			<?php echo CB_Settings::do_admin_tabs() ?>
			<li><a href="#tabs-importexport"><?php _e( 'Import/Export', 'commons-booking' ); ?></a></li>
		</ul>
		<?php
			CB_Settings::do_admin_settings();
			require_once( plugin_dir_path( __FILE__ ) . 'page-settings-importexport.php' );
		?>
		</div>
    </div>
</div>
