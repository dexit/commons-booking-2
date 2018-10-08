<?php
/**
 * Commons_Booking Settings - Import/Export
 *
 * @package   CommonsBooking2
 * @author    Florian Egermann <florian@wielebenwir.de>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 */
/**
 * Import/Export settings.
 */
?>

		<div id="tabs-importexport" class="metabox-holder">
			<div class="postbox">
				<h3 class="hndle"><span><?php _e( 'Export Settings', CB2_TEXTDOMAIN ); ?></span></h3>
				<div class="inside">
					<p><?php _e( 'Export the plugin\'s settings for this site as a .json file. This will allows you to easily import the configuration to another installation.', CB2_TEXTDOMAIN ); ?></p>
					<form method="post">
						<p><input type="hidden" name="cb_action" value="export_settings" /></p>
						<p>
							<?php wp_nonce_field( 'cb_export_nonce', 'cb_export_nonce' ); ?>
							<?php submit_button( __( 'Export' ), 'secondary', 'submit', false ); ?>
						</p>
					</form>
				</div>
			</div>
			<div class="postbox">
				<h3 class="hndle"><span><?php _e( 'Import Settings', CB2_TEXTDOMAIN ); ?></span></h3>
				<div class="inside">
					<p><?php _e( 'Import the plugin\'s settings from a .json file. This file can be retrieved by exporting the settings from another installation.', CB2_TEXTDOMAIN ); ?></p>
					<form method="post" enctype="multipart/form-data">
						<p>
							<input type="file" name="cb_import_file"/>
						</p>
						<p>
							<input type="hidden" name="cb_action" value="import_settings" />
							<?php wp_nonce_field( 'cb_import_nonce', 'cb_import_nonce' ); ?>
							<?php submit_button( __( 'Import' ), 'secondary', 'submit', false ); ?>
						</p>
					</form>
				</div>
			</div>

