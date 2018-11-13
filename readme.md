# CommonsBooking 2

CB2 is a complete rewrite of [Commons Booking](https://github.com/wielebenwir/commons-booking).  It is currently under heavy development.

CB2 will:

* Provide a much more flexible booking system, that can adapt to  diverse scenarios.
* Create a Database structure that allows for multiple bookings per day (though the booking functionality will *not* be implemented in CB2.0, possible for a future version).
* Re-Structure the code and allow to create an [API](https://github.com/wielebenwir/commons-api) to connect CB instances.
* Many feature requests were not possible with the old codebase.

For design docs, db structure etc, please see the [WIKI](https://github.com/wielebenwir/commons-booking-2/wiki).
For current progress, see the [project](https://github.com/wielebenwir/commons-booking-2/projects/1)

**[Subscribe to the CommonsBooking Newsletter](https://www.wielebenwir.de/kontakt/newsletter)** (bilingual german/english) to recieve news about the development. 

## The way forward (for current Commons Booking users)

* There will be no more feature updates for CB 0.X
* Your issues in the CB 1.0 project are not forgotten, we´ll migrate them once we get the base plugin ready.
* Eventually CB 2.0 will include a migration tool, so you can update to the new system.


## Contributing

We are looking for developers, translators and people willing to beta-test new features.

Please contact [@flegfleg](https://github.com/flegfleg).

## Building Commons Booking 2

*We are finishing up the DB structure right now, we will update this page as soon as we have an alpha*

### Prerequisites

* [Composer](https://getcomposer.org/doc/00-intro.md)
* [Grunt](https://gruntjs.com/getting-started)
* A Wordpress install


### Clone & install dependencies

* Goto `wp-content/plugins`
* Clone (or fork) `$ git clone https://github.com/wielebenwir/commons-booking-2.git`
* Install dependencies: `$ composer install` & `$ npm install`

### Install DB tables

Currently, the plugin has no installer that creates the necessary database tables, or interface to create slot_templates (used for multiple bookings per day).

For now, just* import this sql file into your db:

* [Download .sql file](https://github.com/wielebenwir/commons-booking-2/wiki/etc/commons-booking-2-db-tables.sql.txt) (rename to .sql to import)

*If you don´t use the standad wp database prefix (`wp_`), you need to adjust the file before import.

### Activate

* Navigate to Plugins->Installed Plugins and activate Commons Booking


### Using Grunt

* Run `$ grunt watch` to compile scss and javascript for both front- and backend.


## Supported by

### CB 2.0

* [ADFC Essen](https://www.adfc-nrw.de/kreisverbaende/kv-essen/kreisverband-essen.html) / [Essener Lastenräder](https://essener-lastenrad.de)
* [ADFC Bundesverband](https://www.adfc.de)
* [Freie Lasten – Dein Lastenrad in Marburg](https://freie-lasten.org/)

### CB 1.0

For a full list of supporters of CB 1.0, see the [CB 1.0 repo](https://github.com/wielebenwir/commons-booking).

* [ADFC Dresden](http://www.adfc-dresden.de/index.php/verein/137-adfc-dresden/2152-frieda-und-friedrich)
* [ADFC Hamburg](https://klara.bike)
* [ADFC Bundesverband](https://www.adfc.de)
* [BMBF: Bundesministerium für Bildung und Forschung](https://www.bmbf.de)
* [Prototype Fund](https://prototypefund.de)



