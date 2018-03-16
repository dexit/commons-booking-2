# Commons Booking 2

CB2 is a complete rewrite of [Commons Booking](https://github.com/wielebenwir/commons-booking).  It is currently far from feature complete. 

Main reasons for a new code base were:

* Provide a much more flexible booking system, that can adapt to  diverse scenarios.
* Create a Database structure that allows for multiple bookings per hour (though this will *not* be implemented in CB2.0, possible for a future version).
* Re-Structure the code and allow to create an [API](https://github.com/wielebenwir/commons-api) to connect CB instances.
* Many feature requests were not possible with the old codebase.

__We are looking for contributers! Please contact @flegfleg__ 

For design docs, db structure etc, please see the [WIKI](https://github.com/wielebenwir/commons-booking-2/wiki). 
For current progress, see the [project](https://github.com/wielebenwir/commons-booking-2/projects/1)

## The way forward (Current Commons Booking users)

* There will be no more feature updates for CB 0.X
* Your issues in the CB 1.0 project are not forgotten, we´ll migrate them once we get the base plugin ready. 
* Eventually CB 2.0 will include a migration tool, so you can update to the new system. 


## Building Commons Booking 2


### Prerequisites

* [Composer](https://getcomposer.org/doc/00-intro.md)
* [Grunt](https://gruntjs.com/getting-started)
* A Wordpress install


### Clone & install dependencies

* Goto `wp-content/plugins`
* Clone (or fork) `$ git clone https://github.com/wielebenwir/commons-booking-2.git`
* Install dependencies: `$ composer install`

### Install DB tables

Currently, the plugin has no installer that creates the necessary database tables, or interface to create slot_templates (used for multiple bookings per day). 

For now, just* import this sql file into your db:

* [Download .sql file](https://github.com/wielebenwir/commons-booking-2/wiki/etc/commons-booking-2-db-tables.sql.txt) (rename to .sql to import)

*If you don´t use the standad wp database prefix (`wp_`), you need to adjust the file before import. 

### Activate

* Navigate to Plugins->Installed Plugins and activate Commons Booking


### Using Grunt 

* Run `$ grunt watch` to compile scss and javascript for both front- and backend.  
