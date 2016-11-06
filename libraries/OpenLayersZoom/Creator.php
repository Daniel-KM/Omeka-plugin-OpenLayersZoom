<?php
/**
 * Helper to create an OpenLayersZoom for an item.
 *
 * @package OpenLayersZoom
 */
class OpenLayersZoom_Creator
{
    /**
     * @var string Extension added to a folder name to store data and tiles.
     */
    const ZOOM_FOLDER_EXTENSION = '_zdata';

    /**
     * Passed a file name, it will initilize the zoomify and cut the tiles.
     *
     * @param filename of image
     */
    public function createTiles($filename)
    {
        require_once dirname(__FILE__)
            . DIRECTORY_SEPARATOR . 'Zoomify'
            . DIRECTORY_SEPARATOR . 'Zoomify.php';

        // Tiles are built in-place, in a subdir of the original image folder.
        // TODO Add a destination path to use local server path and to avoid move.
        $originalDir = FILES_DIR . DIRECTORY_SEPARATOR . 'original' . DIRECTORY_SEPARATOR;
        list($root, $ext) = $this->getRootAndExtension($filename);
        $sourcePath = $originalDir . $root . OpenLayersZoom_Creator::ZOOM_FOLDER_EXTENSION;

        $zoomify = new Zoomify($originalDir);
        $zoomify->zoomifyObject($filename, $originalDir);

       // Move the tiles into their storage directory.
       if (file_exists($sourcePath)) {
            // Check if destination folder exists, else create it.
            $destinationPath = $this->getZDataDir($filename);
            if (!is_dir(dirname($destinationPath))) {
                $result = mkdir(dirname($destinationPath), 0755, true);
                if (!$result) {
                    $message = __('Unable to create destination directory: "%s" for file "%s".', $destinationPath, basename($filename));
                    _log($message, Zend_Log::WARN);
                    throw new Omeka_Storage_Exception($message);
                }
            }
            $result = rename($sourcePath, $destinationPath);
        }
    }

    /**
     * Determine if Omeka is ready to use an IIPImage server.
     *
     * @internal Result is statically saved.
     *
     * @return boolean
     */
    public function useIIPImageServer()
    {
        static $flag = null;

        if (is_null($flag)) {
            $db = get_db();
            $sql = "
                SELECT elements.id
                FROM {$db->Elements} elements
                WHERE elements.element_set_id = ?
                    AND elements.name = ?
                LIMIT 1
            ";
            $bind = array(3, 'Tile Server URL');
            $IIPImage = $db->fetchOne($sql, $bind);
            $flag = (boolean) $IIPImage;
        }

        return $flag;
    }

    /**
     * Explode a filepath in a root and an extension, i.e. "/path/file.ext" to
     * "/path/file" and "ext".
     *
     * @return array
     */
    public function getRootAndExtension($filepath)
    {
        $extension = pathinfo($filepath, PATHINFO_EXTENSION);
        $root = $extension ? substr($filepath, 0, strrpos($filepath, '.')) : $filepath;
        return array($root, $extension);
    }

    /**
     * Returns the folder where are stored xml data and tiles (zdata path).
     *
     * @param string|object $file
     *   Filename or file object.
     *
     * @return string
     *   Full folder path where xml data and tiles are stored.
     */
    public function getZDataDir($file)
    {
        $filename = is_string($file) ? $file : $file->filename;
        list($root, $extension) = $this->getRootAndExtension($filename);
        return get_option('openlayerszoom_tiles_dir') . DIRECTORY_SEPARATOR . $root . OpenLayersZoom_Creator::ZOOM_FOLDER_EXTENSION;
    }

    /**
     * Returns the url to the folder where are stored xml data and tiles (zdata
     * path).
     *
     * @param string|object $file
     *   Filename or file object.
     * @param boolean|null $absolute If null, use the user option, else check
     *   and add the base url when true, and remove the base url when false.
     *
     * @return string
     *   Url where xml data and tiles are stored.
     */
    public function getZDataWeb($file, $absolute = null)
    {
        $filename = is_string($file) ? $file : $file->filename;
        list($root, $extension) = $this->getRootAndExtension($filename);
        $zoom_tiles_web = get_option('openlayerszoom_tiles_web');
        $isUrlAbsolute = strpos($zoom_tiles_web, 'https://') === 0 || strpos($zoom_tiles_web, 'http://') === 0;
        // Use the absolute or the relative path according to the user option.
        if (is_null($absolute)) {
            if (!$isUrlAbsolute) {
                $zoom_tiles_web = url($zoom_tiles_web);
            }
        }
        // Force absolute url.
        elseif ($absolute) {
            if (!$isUrlAbsolute) {
                $zoom_tiles_web = absolute_url($zoom_tiles_web);
            }
        }
        // Force relative url.
        else {
            if ($isUrlAbsolute) {
                $serverUrlHelper = new Zend_View_Helper_ServerUrl;
                $serverUrl = $serverUrlHelper->serverUrl();
                if (strpos($zoom_tiles_web, $serverUrl) === 0) {
                    $zoom_tiles_web = substr($zoom_tiles_web, strlen($serverUrl));
                }
            }
        }
        return $zoom_tiles_web . '/' . $root . OpenLayersZoom_Creator::ZOOM_FOLDER_EXTENSION;
    }

    /**
     * Manages deletion of the folder of a file when this file is removed.
     *
     * @param string|object $file
     *   Filename or file object.
     *
     * @return void
     */
    public function removeZDataDir($file)
    {
        $file = is_string($file) ? $file : $file->filename;
        if ($file == '' || $file == '/') {
            return;
        }

        $removeDir = $this->getZDataDir($file);
        if (file_exists($removeDir)) {
            // Make sure there is an image file with this name,
            // meaning that it really is a zoomed image dir and
            // not deleting the root of the site :(
            // We check a derivative, because the original image
            // is not always a jpg one.
            list($root, $ext) = $this->getRootAndExtension($file);
            if (file_exists(FILES_DIR . DIRECTORY_SEPARATOR . 'fullsize' . DIRECTORY_SEPARATOR . $root . '.jpg')) {
                $this->_rrmdir($removeDir);
            }
        }
    }

    /**
     * Order files attached to an item by file id.
     *
     * @param object $item.
     *
     * @return array
     *  Array of files ordered by file id.
     */
    public function getFilesById($item)
    {
        $files = array();
        foreach ($item->Files as $file) {
            $files[$file->id] = $file;
        }

        return $files;
    }

    /**
     * Removes directories recursively.
     *
     * @param string $dirPath Directory name.
     *
     * @return boolean
     */
    protected function _rrmdir($dirPath)
    {
        $files = array_diff(scandir($dirPath), array('.', '..'));
        foreach ($files as $file) {
            $path = $dirPath . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                $this->_rrmDir($path);
            }
            else {
                unlink($path);
            }
        }
        return rmdir($dirPath);
    }
}
