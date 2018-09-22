<?php
/**
 * Represents the view for the administration dashboard.
 *
 * @uses CB2_Settings
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

		<?php echo CB2_Strings::get('general', 'test-variable', 'i am replacing this' ); ?>

    <div id="tabs" class="settings-tab">
		<ul>
			<?php
			/* Display the settings tabs */
			echo CB2_Settings::do_admin_tabs()
			?>
			<li><a href="#tabs-importexport">
				<?php
				/* Display the import/export tab contents */
				_e( 'Import/Export', 'commons-booking' ); ?>
			</a></li>
		</ul>
		<?php
			/* Display the settings tabs contents */
			CB2_Settings::do_admin_settings();
			/* Display the import/export tab contents */
			require_once( plugin_dir_path( __FILE__ ) . 'settings_importexport.php' );
		?>
		</div>
    </div>
</div>
