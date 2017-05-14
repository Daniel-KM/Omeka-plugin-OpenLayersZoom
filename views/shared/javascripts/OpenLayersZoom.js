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
    var source = new ol.source.Zoomify({
        url: url,
        size: [imgWidth, imgHeight],
        // This server does not support CORS, and so is incompatible with WebGL.
        crossOrigin: 'anonymous'
    });
    var extent = [0, -imgHeight, imgWidth, 0];

    var map = new ol.Map({
        target: target,
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
        view: new ol.View({
            // adjust zoom levels to those provided by the source
            resolutions: source.getTileGrid().getResolutions(),
            // constrain the center: center cannot be set outside this extent
            extent: extent
        })
    });

    // Initialize zoom to extent.
    map.getView().fit(extent, map.getSize());
}
