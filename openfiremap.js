function createMap(container) {
    var map = new OpenLayers.Map(container, {
        controls: [
            new OpenLayers.Control.ArgParser(),
	    new OpenLayers.Control.Navigation(),
	    new OpenLayers.Control.PanZoomBar(),
	    new OpenLayers.Control.LayerSwitcher(),
	    new OpenLayers.Control.ScaleLine(),
	    new OpenLayers.Control.Permalink('permalinkanchor'),
	    new OpenLayers.Control.KeyboardDefaults()
            //, geolocate
        ],
        units: 'm',
        projection: new OpenLayers.Projection("EPSG:900913"),
        displayProjection: new OpenLayers.Projection("EPSG:4326")
    }
	                        );
    
    map.addLayer(new OpenLayers.Layer.OSM("OpenStreetMap", null,
	                                  { transitionEffect: 'resize' } ));
    
    map.addLayer(new OpenLayers.Layer.OSM("OpenStreetMap, pale", null,
	                                  { transitionEffect: 'resize', opacity: 0.5 }));
    
    map.addLayer(new OpenLayers.Layer.OSM("Black/white map","http://toolserver.org/tiles/bw-mapnik/${z}/${x}/${y}.png",
	                                  { transitionEffect: 'resize' }));
    
    map.addLayer(new OpenLayers.Layer.OSM("Grey/white map","http://toolserver.org/tiles/bw-mapnik/${z}/${x}/${y}.png",
	                                  { transitionEffect: 'resize', opacity: 0.5 }));
    
    map.addLayer(new OpenLayers.Layer.OSM("No Background","img/blank.png",
	                                  { transitionEffect: 'resize' }));
    
    map.addLayer(new OpenLayers.Layer.OSM("Fire hydrants","http://openfiremap.org/hytiles/${z}/${x}/${y}.png",
	                                  { numZoomLevels: 18, transitionEffect: 'resize', alpha: true, isBaseLayer: false }));

    var hydrantStyles = new OpenLayers.StyleMap({ 
        externalGraphic: "icons/hydrant_u_17.png",
        labelYOffset: -10,
        graphicWidth: 24,
        graphicHeight: 32,
        label:"${name}",
        labelAlign: 'ct',
        fontColor: '#F00',
        fontSize: '11px',
        fontWeight: 'bold',
        fontFamily: 'sans-serif'
    });

    var styleMap = {
        "#hy_u": {externalGraphic: "icons/hydrant_u_17.png"},
        "#hy_p": {externalGraphic: "icons/hydrant_p_17.png"},
        "#wt": {externalGraphic: "icons/water_tank_17.png"},
        "#wu": {externalGraphic: "icons/water_tank_unlimited_17.png"}
    };

    hydrantStyles.addUniqueValueRules("default", "styleUrl", styleMap);

    var vectorHydrants = new OpenLayers.Layer.Vector("Fire hydrants (vector)", {
        styleMap: hydrantStyles,
	projection: new OpenLayers.Projection("EPSG:4326"),
        strategies: [
	    new OpenLayers.Strategy.BBOX(),
        ],
        protocol: new OpenLayers.Protocol.HTTP( {
            url: "http://localhost/~cwhlocal/data.php",
            format: new OpenLayers.Format.KML( {
                //extractStyles: true, 
                extractAttributes: true,
                maxDepth: 2
            })
        }),
        maxResolution: 1.0,
        isBaseLayer: false
    });

    map.addLayer(vectorHydrants);

    map.addLayer(new OpenLayers.Layer.OSM("Emergency rooms","http://openfiremap.org/eytiles/${z}/${x}/${y}.png",
	                                  { visibility: false, numZoomLevels: 18, transitionEffect: 'resize',
                                            alpha: true, isBaseLayer: false }));

    if (!map.getCenter()) {        
        var defaultCenter = new OpenLayers.LonLat(10.3702339533674, 51.3229238802164);
    
        defaultCenter = defaultCenter.transform(
            new OpenLayers.Projection("EPSG:4326"), // transform from WGS 1984
            new OpenLayers.Projection("EPSG:900913") // to Spherical Mercator Projection
        )
    
        var zoom = 7;
        map.setCenter(defaultCenter, zoom);
    }

    return map;
}
