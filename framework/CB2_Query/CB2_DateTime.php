<?php
class CB2_DateTime {
	// Wrapper class for PHP DateTime
	private $datetime;

	// -------------------------------------------- Factory
	// NOTE: PHP DateTime constructor already accepts:
	// today, tomorrow and yesterday
	static function now() {
		return new CB2_DateTime();
	}

	static function today() {
		return self::now()->clearTime();
	}

	static function yesterday() {
		return self::today()->sub( 'P1D' );
	}

	static function next_week_start() {
		$today       = self::today();
		$day_of_week = CB2_Day::dayofweek_adjusted( $today );
		return $today->add( 7 - $day_of_week );
	}

	static function next_week_end() {
		$today       = self::today()->endTime();
		$day_of_week = CB2_Day::dayofweek_adjusted( $today );
		return $today->add( 7 - $day_of_week + 6 );
	}

	static function day_start() {
		$today = self::today();
		return $today->setTime( get_option( CB2_TEXTDOMAIN . '-day-start', 9 ), 0 );
	}

	static function day_end() {
		$today = self::today();
		return $today->setTime( get_option( CB2_TEXTDOMAIN . '-day-end', 18 ), 0 );
	}

	static function lunch_start() {
		$today = self::today();
		return $today->setTime( get_option( CB2_TEXTDOMAIN . '-lunch-start', 12 ), 0 );
	}

	static function lunch_end() {
		$today = self::today();
		return $today->setTime( get_option( CB2_TEXTDOMAIN . '-lunch-end', 13 ), 0 );
	}

	function __construct( $datetime = NULL, String $parse_error = NULL ) {
		if ( $datetime instanceof CB2_DateTime ) {
			$this->datetime = clone $datetime->datetime;
		} else if ( $datetime instanceof DateTime ) {
			$this->datetime = clone $datetime;
		} else if ( is_numeric( $datetime ) ) {
			$this->datetime = new DateTime();
			$this->add( $datetime );
		} else {
			$datetime_method = preg_replace( '/ -/', '_', $datetime );
			if ( method_exists( get_class(), $datetime_method ) ) {
				$cb2_datetime   = self::$datetime_method();
				$this->datetime = $cb2_datetime->datetime;
			} else {
				$this->datetime = new DateTime( $datetime );
			}
		}

		if ( ! ( $this->datetime instanceof DateTime ) )
			throw new Exception( $parse_error ? $parse_error : "Failed to parse DateTime [$datetime]" );

		if ( WP_DEBUG ) $this->debug_datetime = $this->datetime->format('c');
	}

	function __clone() {
		$this->datetime = clone $this->datetime;
		if ( WP_DEBUG ) $this->debug_datetime = $this->datetime->format('c');
	}

	function clone() {
		return clone $this;
	}

	// -------------------------------------------- Serialisation
  public function __toString() {
		return $this->format( 'c' );
  }

	function format( String $format = 'c' ) {
		return $this->datetime->format( $format );
	}

	// -------------------------------------------- Time Navigation
	function clearTime() {
		$this->setTime( 0, 0 );
		return $this;
	}

	function endTime() {
		$this->setTime( 23, 59, 59 );
		return $this;
	}

	function setTime( Int $hours, Int $minutes, Int $seconds = 0 ) {
		$this->datetime->setTime( $hours, $minutes, $seconds );
		return $this;
	}

	function setTimestamp( Int $seconds_since_epoch ) {
		$this->datetime->setTimestamp( $seconds_since_epoch );
		return $this;
	}

	function setDayStart() {
		$this->datetime->setTime( get_option( CB2_TEXTDOMAIN . '-day-start', 9 ), 0 );
		return $this;
	}

	function setDayEnd() {
		$this->datetime->setTime( get_option( CB2_TEXTDOMAIN . '-day-end', 18 ), 0 );
		return $this;
	}

	function sub( $interval ) {
		if      ( is_numeric( $interval ) ) $interval = new DateInterval( "P{$interval}D" );
		else if ( is_string( $interval ) )  $interval = new DateInterval( $interval );
		$this->datetime->sub( $interval );
		return $this;
	}

	function add( $interval ) {
		if      ( is_numeric( $interval ) ) $interval = new DateInterval( "P{$interval}D" );
		else if ( is_string( $interval ) )  $interval = new DateInterval( $interval );
		$this->datetime->add( $interval );
		return $this;
	}

	// -------------------------------------------- Operators
	function after( CB2_DateTime $datetime2 ) {
		return $this->datetime > $datetime2->datetime;
	}

	function moreThanOrEqual( CB2_DateTime $datetime2 ) {
		return $this->datetime >= $datetime2->datetime;
	}

	function lessThanOrEqual( CB2_DateTime $datetime2 ) {
		return $this->datetime <= $datetime2->datetime;
	}

	function before( CB2_DateTime $datetime2 ) {
		return $this->datetime < $datetime2->datetime;
	}

	function is( CB2_DateTime $datetime2 ) {
		return $this->datetime == $datetime2->datetime;
	}

	function diff( CB2_DateTime $datetime2, Bool $absolute = FALSE ) {
		return $this->datetime->diff( $datetime2->datetime, $absolute );
	}
}
