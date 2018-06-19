<?php
// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB_PeriodStatusType implements JsonSerializable {
  static $all = array();

  protected function __construct(
    $period_status_type_id,
    $period_status_type_name = NULL,
    $colour   = NULL,
    $opacity  = NULL,
    $priority = NULL,
    $return   = NULL,
    $collect  = NULL,
    $use      = NULL
  ) {
    $this->period_status_type_id   = $period_status_type_id;
    $this->period_status_type_name = $period_status_type_name;
    $this->colour   = $colour;
    $this->opacity  = $opacity;
    $this->priority = $priority;
    $this->return   = $return;
    $this->collect  = $collect;
    $this->use      = $use;
  }

  static function factory(
    $period_status_type_id,
    $period_status_type_name = NULL,
    $colour   = NULL,
    $opacity  = NULL,
    $priority = NULL,
    $return   = NULL,
    $collect  = NULL,
    $use      = NULL
  ) {
    // Design Patterns: Factory Singleton with Multiton
    $key = $period_status_type_id;
    if ( isset( self::$all[$key] ) ) $object = self::$all[$key];
    else {
      $period_status_type_class = 'CB_PeriodStatusType';
      // Hardcoded system status types
      // TODO: create a trigger preventing deletion of these
      switch ( $period_status_type_id ) {
        case 1: $period_status_type_class = 'CB_PeriodStatusType_Available'; break;
        case 2: $period_status_type_class = 'CB_PeriodStatusType_Booked';    break;
        case 3: $period_status_type_class = 'CB_PeriodStatusType_Closed';    break;
        case 4: $period_status_type_class = 'CB_PeriodStatusType_Open';      break;
        case 5: $period_status_type_class = 'CB_PeriodStatusType_Repair';    break;
      }

      $object = new $period_status_type_class(
        $period_status_type_id,
        $period_status_type_name,
        $colour,
        $opacity,
        $priority,
        $return,
        $collect,
        $use
      );
      self::$all[$key] = $object;
    }

    return $object;
  }

  function styles() {
    $styles = '';
    if ( $this->colour   ) $styles .= 'color:#'  . $this->colour           . ';';
    if ( $this->priority ) $styles .= 'z-index:' . $this->priority + 10000 . ';';
    if ( $this->opacity && $this->opacity != 100 ) $styles .= 'opacity:' . $this->opacity / 100    . ';';
    return $styles;
  }

  function classes() {
    return '';
  }

  function indicators() {
    $indicators = array();
    array_push( $indicators, ( $this->return  === TRUE ? 'return'  : 'no-return'  ) );
    array_push( $indicators, ( $this->collect === TRUE ? 'collect' : 'no-collect' ) );
    array_push( $indicators, ( $this->use     === TRUE ? 'use'     : 'no-use'     ) );

    return $indicators;
  }

  function jsonSerialize() {
    return array_merge( (array) $this, array(
      'styles'     => $this->styles(),
      'classes'    => $this->classes(),
      'indicators' => $this->indicators(),
    ) );
  }
}

// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------
class CB_PeriodStatusType_Available extends CB_PeriodStatusType {}
class CB_PeriodStatusType_Booked    extends CB_PeriodStatusType {}
class CB_PeriodStatusType_Closed    extends CB_PeriodStatusType {}
class CB_PeriodStatusType_Open      extends CB_PeriodStatusType {}
class CB_PeriodStatusType_Repair    extends CB_PeriodStatusType {}
