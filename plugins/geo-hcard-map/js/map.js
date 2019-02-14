(function($) {
  "use strict";

  $(document).ready(function(){
    var locationsMap, vcards, markers, group, padding;
    var mapDIV = $('#geo-hcard-map');
    
    if (!window.geo_hcard_map_settings) window.geo_hcard_map_settings = {geo_hcard_map_type:'osm'};
                    
    if (mapDIV.length) {
      switch (window.geo_hcard_map_settings.geo_hcard_map_type) {
        case 'osm': {
          if (window.L) {
            locationsMap = L.map(mapDIV[0]).setView([51.505, -0.09], 13);
            markers      = [];
            
            // OSM streets
            L.tileLayer('http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, <a href="https://opendatacommons.org/licenses/odbl/1.0/">ODbL</a>',
                maxZoom: 18
            }).addTo(locationsMap);
            
            // Add hCard's
            vcards = $('.vcard');
            if (!vcards.length && window.console) console.warn('no .vcard found for the map');
            vcards.each(function() {
              var marker, Icon, icon;
              var oOptions = {};
              var oIconOptions = {};
              var adr      = $(this).find('.adr:first');
              var lat      = adr.find('.geo .latitude').text();
              var lng      = adr.find('.geo .longitude').text();
              var iconUrl  = adr.find('.geo .icon').text();
              var iconShadowUrl = adr.find('.geo .icon-shadow').text();
              var title    = $(this).find('.fn:first').text();
              var href     = $(this).find('.fn:first').attr("href");
              var desc     = '';
              
              $(this).find('.cb-popup').each(function() {
                desc += $("<div/>").append($(this).clone()).html();
              });
              
              // Warnings
              if (window.console) {
                if (!adr.length) console.warn('.vcard found but has no .adr');
                if (!title)      console.warn('.vcard found but has no .fn title');
                if (!href)       console.warn('.vcard found with .fn but has no @href');
                if (lat === '')  console.warn('.vcard found but has no .adr .geo .latitude');
                if (lng === '')  console.warn('.vcard found but has no .adr .geo .longitude');
              }
                        
              if (lat && lng) {
                // Give some defaults for best chances of working
                if (!title) title = '(no title)';
                if (!href)  href  = '#' + title;  // Should not happen
                          
                if (iconUrl) {
                  oIconOptions = {
                      iconUrl:      iconUrl,
                      title:        title,
                      alt:          title,
                      iconSize:     [48, 48], // size of the icon
                      shadowSize:   [48, 48], // size of the shadow
                      iconAnchor:   [24, 24], // point of the icon which will correspond to marker's location
                      shadowAnchor: [20, 20], // the same for the shadow
                      popupAnchor:  [0, -8]   // point from which the popup should open relative to the iconAnchor
                  };
                  if (iconShadowUrl) oIconOptions.shadowUrl = iconShadowUrl;
                  Icon = L.Icon.extend({options:oIconOptions});
                  oOptions.icon = new Icon();
                }
                
                if (window.console) console.info('adding [' + title + '] at [' + lat + ',' + lng + ']');
                marker = L.marker([lat, lng], oOptions).addTo(locationsMap);
                marker.bindPopup('<h2><a href="' + href + '">' + title + '</a></h2>' + desc);
                markers.push(marker);
              }
            });

            // Fit to all markers
            if (markers.length) {
              padding = (markers.length == 1 ? 2 : 0.5);
              group   = new L.featureGroup(markers);
              locationsMap.fitBounds(group.getBounds().pad(padding));
              if (window.console) console.log(group.getBounds());
            }
            
            if (window.console) console.log(locationsMap);
          } else if (window.console) console.error('Leaflet library not loaded, get here: http://leafletjs.com/');
          break;
        }
      }
    } else if (window.console) console.warn('#geo-hcard-map not found in the page');
  });
}(jQuery));
