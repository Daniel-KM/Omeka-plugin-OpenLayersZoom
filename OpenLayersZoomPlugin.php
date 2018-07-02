<?php
/**
 * OpenLayers Zoom: an OpenLayers based image zoom widget.
 *
 * @copyright Daniel Berthereau, 2013-2017
 * @copyright Peter Binkley, 2012-2013
 * @copyright Matt Miller, 2012
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 */

/**
 * The OpenLayers Zoom plugin.
 *
 * @package Omeka\Plugins\OpenLayersZoom
 */
class OpenLayersZoomPlugin extends Omeka_Plugin_AbstractPlugin
{
    /**
     * @var array Hooks for the plugin.
     */
    protected $_hooks = array(
        'install',
        'uninstall',
        'initialize',
        'config_form',
        'config',
        'admin_items_batch_edit_form',
        'items_batch_edit_custom',
        'public_head',
        'after_save_item',
        'before_delete_file',
        'public_items_show',
        'open_layers_zoom_display_file',
    );

    /**
     * @var array Filters for the plugin.
     */
    protected $_filters = array(
        'admin_items_form_tabs',
        // Currently, it's a checkbox, so no error can be done.
        // 'items_batch_edit_error',
    );

    /**
     * @var array Options and their default values.
     */
    protected $_options = array(
        'openlayerszoom_tiles_dir' => '/zoom_tiles',
        'openlayerszoom_tiles_web' => '/zoom_tiles',
        'openlayerszoom_use_default_hook' => true,
        'openlayerszoom_use_public_head' => true,
        'openlayerszoom_queue_js' => false,
    );

    /**
     * Installs the plugin.
     */
    public function hookInstall()
    {
        $this->_options['openlayerszoom_tiles_dir'] = FILES_DIR . DIRECTORY_SEPARATOR . 'zoom_tiles';
        // define('ZOOMTILES_WEB', 'http://ec2-75-101-192-109.compute-1.amazonaws.com/cgi-bin/iipsrv.fcgi?zoomify=/var/www/jp2samples');
        $this->_options['openlayerszoom_tiles_web'] = WEB_FILES . '/zoom_tiles';

        $this->_installOptions();

        // Check if there is a directory in the archive for the zoom titles we
        // will be making.
        $tilesDir = get_option('openlayerszoom_tiles_dir');
        if (!file_exists($tilesDir)) {
            mkdir($tilesDir);
            @chmod($tilesDir, 0755);

            copy(FILES_DIR . DIRECTORY_SEPARATOR . 'original' . DIRECTORY_SEPARATOR . 'index.html', $tilesDir . DIRECTORY_SEPARATOR . 'index.html');
            @chmod($tilesDir . DIRECTORY_SEPARATOR . 'index.html', 0644);
        }

        $this->registerArchiveRepertory();
    }

    /**
     * Uninstalls the plugin.
     */
    public function hookUninstall()
    {
        // Nuke the zoom tiles directory.
        $tilesDir = get_option('openlayerszoom_tiles_dir');
        $this->rrmdir($tilesDir);

        $this->_uninstallOptions();

        $derivatives = get_option('archive_repertory_derivative_folders');
        if ($derivatives) {
            $derivatives = explode(',', $derivatives);
            foreach ($derivatives as $key => $derivative) {
                if (strpos(trim($derivative), 'zoom_tiles') === 0) {
                    unset($derivatives[$key]);
                }
            }
            set_option('archive_repertory_ingesters', implode(',', $derivatives));
        }
    }

    /**
     * Initialize the plugin.
     */
    public function hookInitialize()
    {
        add_shortcode('zoom', array($this, 'shortcodeOpenLayersZoom'));
    }

    /**
     * Shows plugin configuration page.
     */
    public function hookConfigForm($args)
    {
        $this->registerArchiveRepertory();
        $view = get_view();
        echo $view->partial('plugins/openlayerszoom-config-form.php');
    }

    /**
     * Saves plugin configuration page.
     *
     * @param array Options set in the config form.
     */
    public function hookConfig($args)
    {
        $post = $args['post'];

        $openLayersZoomTilesDir = trim($post['openlayerszoom_tiles_dir']);
        if (!empty($openLayersZoomTilesDir)) {
            $openLayersZoomTilesDir = realpath($openLayersZoomTilesDir);
        }
        $post['openlayerszoom_tiles_dir'] = empty($openLayersZoomTilesDir)
            ? FILES_DIR . DIRECTORY_SEPARATOR . 'zoom_tiles'
            : $openLayersZoomTilesDir;

        foreach ($this->_options as $optionKey => $optionValue) {
            if (isset($post[$optionKey])) {
                set_option($optionKey, $post[$optionKey]);
            }
        }
    }

    /**
     * Add a partial batch edit form.
     *
     * @return void
     */
    public function hookAdminItemsBatchEditForm($args)
    {
        $view = $args['view'];
        echo get_view()->partial(
            'forms/openlayerszoom-batch-edit.php'
        );
    }

    /**
     * Process the partial batch edit form.
     *
     * @return void
     */
    public function hookItemsBatchEditCustom($args)
    {
        $item = $args['item'];
        $zoomify = $args['custom']['openlayerszoom']['zoomify'];

        if (!$zoomify) {
            return;
        }

        $supportedFormats = array(
            'jpeg' => 'JPEG Joint Photographic Experts Group JFIF format',
            'jpg' => 'Joint Photographic Experts Group JFIF format',
            'png' => 'Portable Network Graphics',
            'gif' => 'Graphics Interchange Format',
            'tif' => 'Tagged Image File Format',
            'tiff' => 'Tagged Image File Format',
        );
        // Set the regular expression to match selected/supported formats.
        $supportedFormatRegEx = '/\.' . implode('|', array_keys($supportedFormats)) . '$/i';

        // Retrieve image files from the item.
        $view = get_view();
        $creator = new OpenLayersZoom_TileBuilder();
        foreach($item->Files as $file) {
            if ($file->hasThumbnail()
                    && preg_match($supportedFormatRegEx, $file->filename)
                    && !$view->openLayersZoom()->isZoomed($file)
                ) {
                $creator->createTiles($file->filename);
            }
        }
    }

    /**
     * Add css and js in the header of the public theme.
     */
    public function hookPublicHead($args)
    {
        if (!get_option('openlayerszoom_use_public_head')) {
            return;
        }

        $view = $args['view'];

        $request = Zend_Controller_Front::getInstance()->getRequest();
        if ($request->getControllerName() == 'items'
                && $request->getActionName() == 'show'
                && $view->openLayersZoom()->zoomedFilesCount($view->item) > 0
            ) {
            queue_css_file('ol');
            queue_js_file(array(
                'ol',
                'OpenLayersZoom',
            ));
        }
    }

    /**
     * Fired once the record is saved, if there is a `open_layers_zoom_filename`
     * passed in the $_POST along with save then we know that we need to zoom
     * resource.
     */
    public function hookAfterSaveItem($args)
    {
        if (!$args['post']) {
            return;
        }

        $item = $args['record'];
        $post = $args['post'];

        // Loop through and see if there are any files to zoom.
        // Only checked values are posted.
        $filesave = false;
        $view = get_view();
        $creator = new OpenLayersZoom_TileBuilder();
        $files = $creator->getFilesById($item);
        foreach ($post as $key => $value) {
            // Key is the file id of the stored image, value is the filename.
            if (strpos($key, 'open_layers_zoom_filename_') !== false) {
                $file = (int) substr($key, strlen('open_layers_zoom_filename_'));
                if (empty($files[$file])) {
                    continue;
                }
                if (!$view->openLayersZoom()->isZoomed($files[$file])) {
                    $creator->createTiles($value);
                }
                $filesaved = true;
            }
            elseif ((strpos($key, 'open_layers_zoom_removed_hidden_') !== false) && ($filesaved != true)) {
                $creator->removeZDataDir($value);
            }
        }
    }

    /**
     * Manages deletion of the folder of a file when this file is removed.
     */
    public function hookBeforeDeleteFile($args)
    {
        $file = $args['record'];
        if (!$file->hasThumbnail()) {
            return;
        }

        $creator = new OpenLayersZoom_TileBuilder();
        $creator->removeZDataDir($file);
    }

    /**
     * Controls how the image will be returned.
     *
     * @todo Need to change this based on how non-zoomed images are to be
     * presented.
     *
     * @param array $args
     *   Array containing:
     *   - 'file': object a file object
     *   - 'options'
     *
     * @return string
     */
    public function hookPublicItemsShow($args = array())
    {
        if (!get_option('openlayerszoom_use_default_hook')) {
            return;
        }

        $view = $args['view'];
        $item = $args['item'];

        echo $view->openLayersZoom()->zoom($item);
    }

    /**
     * Controls how the image will be returned.
     *
     * @deprecated since v 2.5
     * @todo To be removed in next release.
     * @internal Different from public_items_show hook, because it can return
     * the normal file if the file is not zoomed and it needs to be wrapped with
     * <div id="openlayerszoom-images"></div>.
     *
     * @param array $args
     *   Array containing:
     *   - 'file': object a file object
     *   - 'options'
     *
     * @return string
     */
    public function hookOpenLayersZoomDisplayFile($args = array())
    {
        if (!isset($args['file'])) {
            return;
        }

        $file = $args['file'];
        $view = get_view();

        $html = $view->openLayersZoom()->zoom($file);

        // Display normal file if nothing.
        if (empty($html)) {
            $options = isset($args['options']) ? $args['options'] : array();
            $html = file_markup($file, $options);
        }

        echo $html;
    }

    /**
     * Adds the zoom options to the images attached to the record, it inserts a
     * "Zoom" tab in the admin->edit page
     *
     * @return array of tabs
     */
    public function filterAdminItemsFormTabs($tabs, $args)
    {
        $item = $args['item'];

        $useHtml = '<span>' . __('Only images files attached to the record can be zoomed.') . '</span>';
        $zoomList = '';

        $view = get_view();
        foreach($item->Files as $file) {
            if (strpos($file->mime_type, 'image/') === 0) {
                // See if this image has been zoooomed yet.
                if ($view->openLayersZoom()->isZoomed($file)) {
                    $isChecked = '<input type="checkbox" checked="checked" name="open_layers_zoom_filename_' . $file->id . '" id="open_layers_zoom_filename_' . $file->id . '" value="' . $file->filename . '"/>' . __('This image is zoomed.') . '</label>';
                    $isChecked .= '<input type="hidden" name="open_layers_zoom_removed_hidden_' . $file->id . '" id="open_layers_zoom_removed_hidden_' . $file->id . '" value="' . $file->filename . '"/>';

                    $title = __('Click and Save Changes to make this image un zoom-able');
                    $style_color = "color:green";
                }
                else {
                    $isChecked = '<input type="checkbox" name="open_layers_zoom_filename_' . $file->id . '" id="open_layers_zoom_filename_' . $file->id . '" value="' . $file->filename . '"/>' . __('Zoom this image') . '</label>';
                    $title = __('Click and Save Changes to make this image zoom-able');
                    $style_color = "color:black";
                }

                $useHtml .= '
                <div style="float:left; margin:10px;">
                    <label title="' . $title . '" style="width:auto;' . $style_color . ';" for="zoomThis_' . $file->id . '">'
                    . file_markup($file, array('imageSize'=>'thumbnail'))
                    . $isChecked . '<br />
                </div>' . PHP_EOL;
            }
        }

        $ttabs = array();
        foreach($tabs as $key => $html) {
            if ($key == 'Tags') {
                $ttabs['Zoom'] = $useHtml;
            }
            $ttabs[$key] = $html;
        }
        $tabs = $ttabs;
        return $tabs;
    }

    /**
     * Shortcode to display viewer.
     *
     * @param array $args
     * @param Omeka_View $view
     * @return string
     */
    public static function shortcodeOpenLayersZoom($args, $view)
    {
        // Check required arguments
        $recordType = isset($args['record_type']) ? $args['record_type'] : 'Item';
        $recordType = ucfirst(strtolower($recordType));
        if (!in_array($recordType, array('Item', 'File'))) {
            return;
        }

        // Get the specified record.
        if (isset($args['record_id'])) {
            $recordId = (integer) $args['record_id'];
            $record = get_record_by_id($recordType, $recordId);
        }
        // Get the current record.
        else {
            $record = get_current_record(strtolower($recordType), false);
        }
        if (empty($record)) {
            return;
        }

        $html = $view->openLayersZoom()->zoom($record);
        if ($html) {
            $html = '<link href="' . css_src('ol') . '" media="all" rel="stylesheet" type="text/css" >'
                . js_tag('ol')
                . js_tag('OpenLayersZoom')
                . $html;
            return $html;
        }
    }

    /**
     * Helper to register the tile for ArchiveRepertory.
     */
    protected function registerArchiveRepertory()
    {
        if (!plugin_is_active('ArchiveRepertory')) {
            return;
        }

        $derivatives = get_option('archive_repertory_derivative_folders');
        $derivatives = explode(',', $derivatives);
        foreach ($derivatives as $key => $derivative) {
            if (strpos(trim($derivative), 'zoom_tiles') === 0) {
                return;
            }
        }
        $derivatives[] = 'zoom_tiles|_zdata';
        $derivatives = implode(',', $derivatives);
        set_option('archive_repertory_derivative_folders', $derivatives);
    }

    /**
     * Removes directories recursively.
     *
     * @param string $dir Directory name.
     * @return bool
     */
    private function rrmdir($dir)
    {
        if (!file_exists($dir)
                || !is_dir($dir)
                || !is_readable($dir)
                || !is_writable($dir)
            ) {
            return false;
        }

        $scandir = scandir($dir);
        if (!is_array($scandir)) {
            return false;
        }

        $files = array_diff($scandir, array('.', '..'));
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                $this->rrmdir($path);
            } else {
                unlink($path);
            }
        }

        return @rmdir($dir);
    }
}
