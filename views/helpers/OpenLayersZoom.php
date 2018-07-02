<?php
/**
 * Helpers for OpenLayersZoom.
 *
 * @package OpenLayersZoom
 */
class OpenLayersZoom_View_Helper_OpenLayersZoom extends Zend_View_Helper_Abstract
{
    /**
     * The creator is used to check if a zoom exists.
     */
    protected $_creator;

    /**
     * Load the OpenLayersZoom Creator one time only.
     */
    public function __construct()
    {
        $this->_creator = new OpenLayersZoom_TileBuilder();
    }

    /**
     * Get the helper.
     *
     * @return OpenLayersZoom_View_Helper_OpenLayersZoom This view helper.
     */
    public function openLayersZoom()
    {
        return $this;
    }

    /**
     * Returns an OpenLayersZoom to display for an item or a file.
     *
     * @param Item|File $record Item or File to zoom.
     *
     * @return html.
     */
    public function zoom($record)
    {
        $html = '';
        $js = '';

        switch (get_class($record)) {
            case 'Item':
                $zoomedFiles = $this->getZoomedFiles($record);
                if (!empty($zoomedFiles)) {
                    $html = '<div class="openlayerszoom-images">';
                    foreach ($zoomedFiles as $file) {
                        list($htmlCode, $jsCode) = $this->_zoomFile($file);
                        if ($htmlCode) {
                            $html .= $htmlCode . PHP_EOL;
                            $js .= $jsCode . PHP_EOL;
                        };
                    }
                    $html .= '</div>' . PHP_EOL;
                    if ($js) {
                        if (get_option('openlayerszoom_queue_js')) {
                            queue_js_string($js);
                        } else {
                            $html .= '<script type="application/javascript">' . $js . '</script>';
                        }
                    }
                }
                break;

            case 'File':
                list($htmlCode, $jsCode) = $this->_zoomFile($record);
                if ($htmlCode) {
                    $html = '<div class="openlayerszoom-images">';
                    $html .= $htmlCode . PHP_EOL;
                    $html .= '</div>' . PHP_EOL;
                    if (get_option('openlayerszoom_queue_js')) {
                        queue_js_string($jsCode);
                    } else {
                        $html .= '<script type="application/javascript">' . $jsCode . '</script>';
                    }
                }
                break;
        }

        return $html;
    }

    /**
     * Get an array of all zoomed images of an item.
     *
     * @param Item $item
     *
     * @return array
     *   Associative array of file id and files.
     */
    public function getZoomedFiles($item = null)
    {
        if ($item == null) {
            $item = get_current_record('item', false);
            if (empty($item)) {
                return array();
            }
        }

        $list = array();
        foreach($item->Files as $file) {
            if ($this->isZoomed($file)) {
                $list[$file->id] = $file;
            }
        }
        return $list;
    }

    /**
     * Count the number of zoomed images attached to an item.
     *
     * @param Item $item
     *
     * @return integer
     *   Number of zoomed images attached to an item.
     */
    public function zoomedFilesCount($item = null)
    {
        return count($this->getZoomedFiles($item));
    }

    /**
     * Determine if a file is zoomed.
     *
     * @param File $file
     *
     * @return boolean
     */
    public function isZoomed($file = null)
    {
        return (boolean) $this->getTileUrl($file);
    }

    /**
     * Get the url to tiles or a zoomified file, if any.
     *
     * @param File $file
     *
     * @return string
     */
    public function getTileUrl($file = null)
    {
        if (empty($file)) {
            $file = get_current_record('file', false);
            if (empty($file)) {
                return;
            }
        }

        // Does it use a IIPImage server?
        if ($this->_creator->useIIPImageServer()) {
            $item = $file->getItem();
            $tileUrl = $item->getElementTexts('Item Type Metadata', 'Tile Server URL');
            if ($tileUrl) {
                return trim($tileUrl[0]->text);
            }
        }

        // Does it have zoom tiles?
        if (file_exists($this->_creator->getZDataDir($file))) {
            // fetch identifier, to use in link to tiles for this jp2 - pbinkley
            // $jp2 = item('Dublin Core', 'Identifier') . '.jp2';
            // $tileUrl = ZOOMTILES_WEB . '/' . $jp2;
            $tileUrl = $this->_creator->getZDataWeb($file);
            return $tileUrl;
        }
    }

    /**
     * Helper to zoom a file.
     *
     * @param File $file
     * @return array Html and js code.
     */
    protected function _zoomFile($file)
    {
        $tileUrl = $this->getTileUrl($file);
        if (empty($tileUrl)) {
            return array(null, null);
        }

        // Grab the width/height of the original image.
        list($width, $height, $type, $attr) = getimagesize(FILES_DIR . DIRECTORY_SEPARATOR . 'original' . DIRECTORY_SEPARATOR . $file->filename);

        $target = 'map-' . $file->id;

        $html = '<div id="' . $target . '" class="map"></div>';
        $js = sprintf('open_layers_zoom("%s",%d,%d,%s);',
            $target, $width, $height, json_encode(rtrim($tileUrl, '/') . '/'));

        return array($html, $js);
    }
}
