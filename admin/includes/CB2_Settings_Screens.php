<?php
/**
 * Library and meta boxes for items & locations
 *
 *
 * @package   CommonsBooking2
 * @author    Florian Egermann <florian@wielebenwir.de>
 * @copyright 2018 wielebenwir e.V.
 * @license   GPL 2.0+
 * @link      http://commonsbooking.wielebenwir.de
 */
/**
 * All the CMB related code.
 */
class CB2_Settings_Screens
{
	/**
	 * Defaults for new metaboxes
	 *
	 * @var string
	 */
	static $metabox_defaults = array (
		'show_on' => array(
			'key' => 'options-page',
			'value' => array('commons-booking'),
		),
		'show_names' => true,
	);
	/**
	 * Initialize
	 *
	 * @since 2.0.0
	 */
	public function __construct()
	{

	}
	/**
	 * Plugins Settings GUI: Screen
	 *
	 * @since 2.0.0
	 *
	 * @return string $html
	 */
	public function render_settings_screen( ) {
		$html = sprintf('
				<div class="wrap">
					<h2>%s</h2>
					<div id="tabs" class="settings-tab">
						<ul>
							%s
						</ul>
						%s
					</div>
				</div>
			</div>',
			esc_html( get_admin_page_title() ),
			$this->render_admin_tabs(),
			$this->render_plugin_settings_screen()
		);
		return $html;
	}
    /**
     * Add tabs
     *
     * @since 2.0.0
     */

    public static function settings_tabs( )
    {
			 /* CB2 */
			self::add_settings_tab(
					array(
					'title' => __('CB2', 'commons-booking-2'),
					'id' => 'cb2',
					'tab_show' => true, // or callback
					'content' => '<h2>Welcome</h2>' . self::tab_features() // or callback
					)
			);
			/* Tab: General */
			self::add_settings_tab(
					array(
					'title' => __('General', 'commons-booking-2'),
					'id' => 'general',
					'tab_show' => true, // or callback
					'content' => self::tab_general() // or callback
					)
			);
			/* Tab: bookings */
			self::add_settings_tab(
					array(
					'title' => __('Bookings', 'commons-booking-2'),
					'id' => 'bookings',
					'tab_show' => true, // or callback
					'content' => self::tab_bookings() // or callback
					)
			);
			/* Tab feature (conditional): Maps */
			self::add_settings_tab(
					array(
					'title' => __('Maps', 'commons-booking-2'),
					'id' => 'maps',
					'tab_show' => self::is_enabled('features', 'enable-maps'),
					'content' => self::tab_maps() // or callback
					)
			);
			/* Tab feature (conditional): Codes */
			self::add_settings_tab(
					array(
					'title' => __('Codes', 'commons-booking-2'),
					'id' => 'codes',
					'tab_show' => self::is_enabled('features', 'enable-codes'),
					'content' => self::tab_codes() // or callback
					)
			);
			/* Tab feature (conditional): Holidays */
			self::add_settings_tab(
					array(
					'title' => __('Holidays', 'commons-booking-2'),
					'id' => 'holidays',
					'tab_show' => self::is_enabled('features', 'enable-holidays'),
					'content' => self::tab_holidays() // or callback
					)
			);
			/* Tab: Strings */
			self::add_settings_tab(
					array(
					'title' => __('Strings', 'commons-booking-2'),
					'id' => 'strings',
					'tab_show' => true,
					'content' => self::tab_strings() // or callback
					)
			);

    }

}
