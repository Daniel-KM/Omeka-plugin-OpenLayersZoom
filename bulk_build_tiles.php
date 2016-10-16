<?php
    /**
    * bulk_build_tiles.php
    * This script will build all zoom_tiles for a specific collection
    *
    * You should edit manually the collection id, the item ids or set "$all" to
    * true. In all cases, processed files are not retiled.
    *
    * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
    * @author Sylvain Machefert - Bordeaux 3
    */

    // Building tiles asks for more memory than usual php, maybe need to modify
    // default setting.
    ini_set('memory_limit', '1024M');
    // max_picture_size in bytes, to prevent memory errors for big files.
    $max_picture_size = 256000000;

    // The collection id to process.
    $collection_id = 0;
    // Or, when no collection is set, the list of item ids.
    $item_ids = array();
    // Or, when collection and items are not set, images of all the items.
    $all = false;

    // Process selected files, except these ones. This may be usefull when a
    // collection is selected, or when all files are selected.
    $except_collection_ids = array();
    $except_items_ids = array();
    $except_files_ids = array();

    // Main checks.
    $collection_id = (integer) $collection_id;
    $item_ids = array_filter(array_map('intval', $item_ids));
    $except_collection_ids = array_filter(array_map('intval', $except_collection_ids));
    $except_items_ids = array_filter(array_map('intval', $except_items_ids));
    $except_files_ids = array_filter(array_map('intval', $except_files_ids));

    require_once dirname(dirname(dirname(__FILE__))) . '/bootstrap.php';
    require_once(APP_DIR . '/libraries/globals.php');
    require_once('OpenLayersZoomPlugin.php');
    require_once('libraries/OpenLayersZoom/Zoomify/ZoomifyFileProcessor.php');

    if (empty($collection_id) && empty($item_ids) && !$all) {
        echo __('Please provide a collection id, a list of item ids or set "$all" to true directly in this script.') . "\n";
        die;
    }

    $autoloader = Zend_Loader_Autoloader::getInstance();
    $application = new Omeka_Application(APPLICATION_ENV);
//        APP_DIR . '/config/application.ini');

    $application->getBootstrap()->setOptions(array(
        'resources' => array(
            'theme' => array(
                'basePath' => THEME_DIR,
                'webBasePath' => WEB_THEME
            )
        )
    ));
    $application->initialize();
    $db = get_db();

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

    $sql = "SELECT item_id, filename
    FROM {$db->File} files, {$db->Item} items
    WHERE files.item_id = items.id ";

    // Process a collection.
    if ($collection_id > 0) {
        $sql .= " AND items.collection_id = $collection_id";
    }
    // Process an item.
    elseif ($item_ids) {
        $sql .= " AND items.id IN (". implode(',', $item_ids) . ")";
    }
    // Process all items.
    elseif ($all) {
        // Nothing to add.
    }

    if ($except_collection_ids) {
        $sql .= " AND items.collection_id NOT IN (". implode(',', $except_collection_ids) . ")";
    }
    if ($except_items_ids) {
        $sql .= " AND items.id NOT IN (". implode(',', $except_items_ids) . ")";
    }
    if ($except_files_ids) {
        $sql .= " AND files.id NOT IN (". implode(',', $except_files_ids) . ")";
    }

    $file_ids = $db->fetchAll($sql);

    if (empty($file_ids)) {
        echo __('No file to process: check the selection.') . "\n";
        exit;
    }

    $originalDir = FILES_DIR . DIRECTORY_SEPARATOR . 'original' . DIRECTORY_SEPARATOR;

    foreach ($file_ids as $one_id) {
        $filename = $originalDir . $one_id['filename'];
        if (!preg_match($supportedFormatRegEx, $filename)) {
            echo __('Not a picture, skipped: %s', $filename) . "\n";
            continue;
        }

        $computer_size = filesize($filename);
        $decimals = 2;
        $sz = 'BKMGTP';
        $factor = floor((strlen($computer_size) - 1) / 3);
        $human_size = sprintf("%.{$decimals}f", $computer_size / pow(1024, $factor)) . @$sz[$factor];

        $item_id = $one_id['item_id'];
        $fp = new ZoomifyFileProcessor();
        list($root, $ext) = $fp->getRootAndDotExtension($filename);
        $sourcePath = $root . '_zdata';
        $destination = str_replace('/original/', '/zoom_tiles/', $sourcePath);

        if ($computer_size > $max_picture_size) {
            echo __('Picture too big, skipped: %s (%s)', $filename, $human_size) . "\n";
        }
        elseif (file_exists($destination)) {
            echo __('This picture has already been tiled (%s): %s (%d bytes)', $destination, $human_size, $computer_size) . "\n";
        }
        else {
            echo __('Processing... (file size: %d)', $computer_size) . "\n";
            $fp->ZoomifyProcess($filename);
            rename($sourcePath, $destination);
            echo __('Tiled %s [#%d]', $filename, $item_id) . "\n";
        }
    }

    echo "\n";
    echo __('Process completed.') . "\n";
    echo "\n";
    exit;
?>
