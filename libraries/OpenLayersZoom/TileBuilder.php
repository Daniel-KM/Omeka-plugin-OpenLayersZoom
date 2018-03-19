<?php
/**
 * Helper to create an OpenLayersZoom for an item.
 *
 * @package OpenLayersZoom
 */
class OpenLayersZoom_TileBuilder
{
    /**
     * @var string Extension added to a folder name to store data and tiles.
     */
    const ZOOM_FOLDER_EXTENSION = '_zdata';

    /**
     * Passed a file name, it will initilize the zoomify and cut the tiles.
     *
     * @param string $filename Filename of image (storage id).
     */
    public function createTiles($filename)
    {
        require_once dirname(dirname(dirname(__FILE__)))
            . DIRECTORY_SEPARATOR . 'vendor'
            . DIRECTORY_SEPARATOR . 'daniel-km'
            . DIRECTORY_SEPARATOR . 'zoomify'
            . DIRECTORY_SEPARATOR . 'src'
            . DIRECTORY_SEPARATOR . 'Zoomify.php';

        // Parameters of the tile processor.
        $params = array();
        $params['destinationRemove'] = true;

        $filepath = FILES_DIR
            . DIRECTORY_SEPARATOR . 'original'
            . DIRECTORY_SEPARATOR . $filename;
        $destination = $this->getZDataDir($filename);

        $zoomify = new DanielKm\Zoomify\Zoomify($params);
        $zoomify->process($filepath, $destination);
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
        return get_option('openlayerszoom_tiles_dir')
            . DIRECTORY_SEPARATOR . $root . OpenLayersZoom_TileBuilder::ZOOM_FOLDER_EXTENSION;
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
        return $zoom_tiles_web . '/' . $root . OpenLayersZoom_TileBuilder::ZOOM_FOLDER_EXTENSION;
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
        if ($file == '' || $file == DIRECTORY_SEPARATOR) {
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
            $check = FILES_DIR
                . DIRECTORY_SEPARATOR . 'fullsize'
                . DIRECTORY_SEPARATOR . $root . '.jpg';
            if (file_exists($check)) {
                $this->removeDir($removeDir, true);
            }
        }
    }

    /**
     * Order files attached to an item by file id.
     *
     * @param Item $item.
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
     * Checks and removes a folder recursively.
     *
     * @param string $path Full path of the folder to remove.
     * @param bool $evenNonEmpty Remove non empty folder. This parameter can be
     * used with non standard folders.
     * @return bool
     */
    protected function removeDir($path, $evenNonEmpty = false)
    {
        $path = realpath($path);
        if (strlen($path)
                && $path != DIRECTORY_SEPARATOR
                && file_exists($path)
                && is_dir($path)
                && is_readable($path)
                && is_writable($path)
                && ($evenNonEmpty || count(array_diff(@scandir($path), array('.', '..'))) == 0)
            ) {
                return $this->recursiveRemoveDir($path);
            }
    }

    /**
     * Removes directories recursively.
     *
     * @param string $dirPath Directory name.
     * @return bool
     */
    protected function recursiveRemoveDir($dirPath)
    {
        $files = array_diff(scandir($dirPath), array('.', '..'));
        foreach ($files as $file) {
            $path = $dirPath . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                $this->recursiveRemoveDir($path);
            } else {
                unlink($path);
            }
        }
        return rmdir($dirPath);
    }
}
