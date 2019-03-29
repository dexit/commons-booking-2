(function($) {
	"use strict";

	var locationsMap, markers = [];

	function geo_hcard_map_refresh(){
		var jVCards, vcards, vcard, group, padding;
		var marker, Icon;

		if (locationsMap) {
			// ------------------------------------------------ Find hCard's
			if (window.console) console.info('geo-hcard-map: setting up map');
			jVCards = $('.vcard');
			vcards  = [];
			if (!jVCards.length && window.console)
				console.warn('geo-hcard-map: no .vcard elements found in document');

			jVCards.each(function() {
				var jThis = $(this);
				var jAdr  = jThis.find('.adr:first');
				vcards.push({
					oOptions:      {},
					oIconOptions:  {},
					adr:           jAdr,
					lat:           jAdr.find('.geo .latitude').text(),
					lng:           jAdr.find('.geo .longitude').text(),
					iconUrl:       jAdr.find('.geo .icon').text(),
					iconShadowUrl: jAdr.find('.geo .icon-shadow').text(),
					title:         jThis.find('.fn:first').text(),
					href:          jThis.find('.fn:first').attr("href"),
					desc:          '',
					css_class:     jThis.find('.fn:first').attr("class").replace(/^fn | fn | fn$/, ''),
					jPopup:        jThis.find('.cb-popup')
				});
			});

			// ------------------------------------------------ Add to Map
			switch (window.geo_hcard_map_settings.geo_hcard_map_type) {
				case 'osm': {
					if (window.L) {
						for (var i=0; i<markers.length; i++) locationsMap.removeLayer(markers[i]);
						markers = [];

						for (var i=0; i<vcards.length; i++) {
							vcard = vcards[i];

							vcard.jPopup.each(function() {
								vcard.desc += $("<div/>").append($(this).clone()).html();
							});

							// Warnings
							if (window.console) {
								if (!vcard.adr.length) console.warn('geo-hcard-map: .vcard found but has no .adr');
								if (!vcard.title)      console.warn('geo-hcard-map: .vcard found but has no .fn title');
								if (!vcard.href)       console.warn('geo-hcard-map: .vcard found with .fn but has no @href');
								if (vcard.lat === '')  console.warn('geo-hcard-map: .vcard found but has no .adr .geo .latitude');
								if (vcard.lng === '')  console.warn('geo-hcard-map: .vcard found but has no .adr .geo .longitude');
							}

							if (vcard.lat && vcard.lng) {
								// Give some defaults for best chances of working
								if (!vcard.title) vcard.title = '(no title)';
								if (!vcard.href)  vcard.href  = '#' + vcard.title;  // Should not happen

								if (vcard.iconUrl) {
									vcard.oIconOptions = {
										iconUrl:      vcard.iconUrl,
										title:        vcard.title,
										alt:          vcard.title,
										iconSize:     [48, 48], // size of the icon
										shadowSize:   [48, 48], // size of the shadow
										iconAnchor:   [24, 24], // point of the icon which will correspond to marker's location
										shadowAnchor: [20, 20], // the same for the shadow
										popupAnchor:  [0, -8]   // point from which the popup should open relative to the iconAnchor
									};
									if (vcard.iconShadowUrl) vcard.oIconOptions.shadowUrl = vcard.iconShadowUrl;
									Icon = L.Icon.extend({options:vcard.oIconOptions});
									vcard.oOptions.icon = new Icon();
								}

								if (window.console) console.info('adding [' + vcard.title + '] at [' + vcard.lat + ',' + vcard.lng + ']');
								marker = L.marker([vcard.lat, vcard.lng], vcard.oOptions).addTo(locationsMap);
								marker.bindPopup('<h2><a class="' + vcard.css_class + '" href="' + vcard.href + '">' + vcard.title + '</a></h2>' + vcard.desc);
								markers.push(marker);
							}
						}

						// Fit to all markers
						if (markers.length) {
							padding = (markers.length == 1 ? 2 : 0.5);
							group   = new L.featureGroup(markers);
							locationsMap.fitBounds(group.getBounds().pad(padding));
							if (window.console) console.log(group.getBounds());
						}

						if (window.console) console.log(locationsMap);
					} else if (window.console) console.error('geo-hcard-map: Leaflet library not loaded, get here: http://leafletjs.com/');
					break;
				}
			}

			// --------------------------------- MutationObserver
			if (window.MutationObserver) {
				var delay, observer, options;

				function mutationObserver_callback(mutationsList, observer) {
					if (window.console) console.log(mutationsList);
					if (!delay) delay = setTimeout(geo_hcard_map_refresh, 0);
				}

				observer = new MutationObserver(mutationObserver_callback);
				options  = {
					attributes:    true,
					childList:     true,
					subtree:       true,
					characterData: true
				};
				// We observe the parents because direct deletion is not detecable
				// usually the parent will of course be the single UL
				// without other changeable data relating to other areas
				jVCards.each(function(){
					observer.observe(this.parentNode, options);
				});
				if (window.console)
					console.info('geo-hcard-map: listening to ' + jVCards.length + ' vcard...');
			}
		} else if (window.console) console.warn('geo-hcard-map: not found in the page');
	}

	$(document).ready(function(){
		// ------------------------------------------------ Init Map
		var mapDIV = $('#geo-hcard-map');
		if (!window.geo_hcard_map_settings)
			window.geo_hcard_map_settings = {geo_hcard_map_type:'osm'};

		if (mapDIV.length) {
			switch (window.geo_hcard_map_settings.geo_hcard_map_type) {
				case 'osm': {
					if (window.L) {
						// OSM streets
						locationsMap = L.map(mapDIV[0]).setView([51.505, -0.09], 13);
						L.tileLayer('http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
							attribution: 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, <a href="https://opendatacommons.org/licenses/odbl/1.0/">ODbL</a>',
							maxZoom: 18
						}).addTo(locationsMap);
					}
					break;
				}
				default:
					if (window.console) console.error('geo-hcard-map: map type [' + window.geo_hcard_map_settings.geo_hcard_map_type + '] unknown');
			}
		}

		geo_hcard_map_refresh();
	});
}(jQuery));
