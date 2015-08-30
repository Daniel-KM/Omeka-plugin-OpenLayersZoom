/**
 * Display an OpenLayers map.
 *
 * target = attribute id of the div element to use
 * imgWidth = width of the image
 * imgHeight = heigh of the image
 * url = the url to the tiles directory (with final "/")
 *
 * @todo Interactive overview map, permalink, attribution.
 */
function open_layers_zoom(target, imgWidth, imgHeight, url)
{
    // This server does not support CORS, and so is incompatible with WebGL.
    var crossOrigin = undefined;
    // The zoom is set to extent after map initialization.
    var zoom = 2;
    var imgCenter = [imgWidth / 2, imgHeight / 2];
    var extent = [0, -imgHeight, imgWidth, 0];

    var source = new ol.source.Zoomify({
        url: url,
        size: [imgWidth, imgHeight],
        crossOrigin: crossOrigin
    });

    // Maps always need a projection, but Zoomify layers are not geo-referenced, and
    // are only measured in pixels.  So, we create a fake projection that the map
    // can use to properly display the layer.
    var projection = new ol.proj.Projection({
        code: 'ZOOMIFY',
        units: 'pixels',
        extent: [0, 0, imgWidth, imgHeight]
    });

    var map = new ol.Map({
        layers: [
            new ol.layer.Tile({
                source: source
            })
        ],
        logo: false,
        controls: ol.control.defaults({attribution: false}).extend([
            new ol.control.FullScreen(),
            new ol.control.OverviewMap()
        ]),
        interactions: ol.interaction.defaults().extend([
            new ol.interaction.DragRotateAndZoom()
        ]),
        target: target,
        view: new ol.View({
            projection: projection,
            center: imgCenter,
            zoom: zoom,
            // constrain the center: center cannot be set outside this extent
            extent: extent
        })
    });

    // Initialize zoom to extent.
    map.getView().fit(extent, map.getSize());
}
