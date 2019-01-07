<?php


    $my_settings_tab = array(
      'title' => __('Bookings', 'commons-booking-2'),
      'id' => 'bookings',
      'tab_show' => '', // or callback
      'content' => '' // or callback

    );

    $my_timeframe_option_tab = array(
      'title' => __('Bookings', 'commons-booking-2'),
      'id' => 'bookings',
      'tab_show' => '', // or callback
      'content' => '' // or callback
    );

    /**
     * Add a settings tab to the global array
     *
     * @since 2.0.0
     *
     * @param array $args
     */
    public static function add_settings_tab( $tab=array() )
    {
      if ( ! empty ($tab) && is_array($tab) && $tab['tab_show'] == TRUE ) {
        self::$plugin_settings_tabs[$tab['tab_id']] = $tab;
      }
    }
    
$prefix = 

    $my_settings_group = array(
      'id' => self::$settings_prefix . 'bookings',
      'show_on' => array(
        'key' => 'options-page',
        'value' => array('commons-booking'),
      ),
      'show_names' => true,
      'fields' => array(
        array(
          'name' => __('Maximum slots', 'commons-booking'),
          'desc' => __('Maximum slots a user is allowed to book at once', 'commons-booking'),
          'id' => 'max-slots',
          'type' => 'text_small',
          'default' => 3
          ),
        array(
          'name' => __('Consecutive slots', 'commons-booking'),
          'desc' => __('Slots must be consecutive', 'commons-booking'),
          'id' => 'consecutive-slots',
          'type' => 'checkbox',
          'default' => cmb2_set_checkbox_default_for_new_post(true)
        ),
      array(
          'name' => __('Use booking codes', 'commons-booking'),
          'desc' => __('Create codes for every slot', 'commons-booking'),
          'id' => 'use-codes',
          'type' => 'checkbox',
          'default' => cmb2_set_checkbox_default_for_new_post(true)
          ),
        )
      );