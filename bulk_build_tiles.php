<?php
    /**
    * bulk_build_tiles.php
    * This script will build all zoom_tiles for a specific collection
    *
    * You should edit manually the collection id, the item ids or set "$all" to
    * true. In all cases, processed files are not retiled.
    *
    * The process can be launched a second time to check processed files easily.
    *
    * *IMPORTANT*: Check or update the rights of the subfolder of `files/zoom_tiles`,
    * in particular when the folder of items were created by the server and you
    * try to update them.
    *
    * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
    * @author Sylvain Machefert - Bordeaux 3
    */

    // Building tiles asks for more memory than usual php, maybe need to modify
    // default setting.
    ini_set('memory_limit', '1024M');
    // max_picture_size in bytes, to prevent memory errors for big files.
    $max_picture_size = 256000000;

    // Set this to "true" to print tiled files and non-images files too.
    $all_messages = false;

    // The collection ids to process.
    $collection_ids = array();
    // Or, when no collection is set, the list of item ids.
    $item_ids = array();
    // Or, when collection and items are not set, images of all the items.
    $all = false;

    // Process selected files, except these ones. This may be usefull when a
    // collection is selected, or when all files are selected.
    $except_collection_ids = array();
    $except_items_ids = array();
    $except_files_ids = array();

    // Parameters of the tile processor.
    $params = array();
    // A check is done here and not by the processor.
    $params['destinationRemove'] = true;

    // Main checks.
    $collection_ids = array_filter(array_map('intval', $collection_ids));
    $item_ids = array_filter(array_map('intval', $item_ids));
    $except_collection_ids = array_filter(array_map('intval', $except_collection_ids));
    $except_items_ids = array_filter(array_map('intval', $except_items_ids));
    $except_files_ids = array_filter(array_map('intval', $except_files_ids));

    require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'bootstrap.php';
    require_once APP_DIR . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'globals.php';
    require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'OpenLayersZoomPlugin.php';
    require_once dirname(__FILE__)
        . DIRECTORY_SEPARATOR . 'vendor'
        . DIRECTORY_SEPARATOR . 'daniel-km'
        . DIRECTORY_SEPARATOR . 'zoomify'
        . DIRECTORY_SEPARATOR . 'src'
        . DIRECTORY_SEPARATOR . 'Zoomify.php';

    if (empty($collection_ids) && empty($item_ids) && !$all) {
        echo __('Please provide a list of collection or item ids or set "$all" to true directly in this script.') . PHP_EOL;
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

    $sql = "SELECT files.item_id AS item_id, files.filename AS filename, files.id AS file_id
    FROM {$db->File} files, {$db->Item} items
    WHERE files.item_id = items.id ";

    // Process collections.
    if ($collection_ids) {
        $sql .= " AND items.collection_id IN (". implode(',', $collection_ids) . ")";
    }
    // Process items.
    elseif ($item_ids) {
        $sql .= " AND items.id IN (". implode(',', $item_ids) . ")";
    }
    // Process all files.
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
        echo __('No file to process: check the selection.') . PHP_EOL;
        exit;
    }

    $originalDir = FILES_DIR . DIRECTORY_SEPARATOR . 'original' . DIRECTORY_SEPARATOR;

    $zoomify = new \DanielKm\Zoomify\Zoomify($params);
    foreach ($file_ids as $one_id) {
        $filename = $one_id['filename'];
        $filepath = $originalDir . $filename;
        $item_id = $one_id['item_id'];
        $file_id = $one_id['file_id'];
        if (!preg_match($supportedFormatRegEx, $filename)) {
            if ($all_messages) {
                echo __('Not a picture, skipped: #%d "%s" (item #%d)',
                    $file_id, $filename, $item_id) . PHP_EOL;
            }
            continue;
        }

        $computer_size = filesize($filepath);
        $decimals = 2;
        $sz = 'BKMGTP';
        $factor = floor((strlen($computer_size) - 1) / 3);
        $human_size = sprintf("%.{$decimals}f", $computer_size / pow(1024, $factor)) . @$sz[$factor];

        $extension = pathinfo($filepath, PATHINFO_EXTENSION);
        $root = $extension ? substr($filepath, 0, strrpos($filepath, '.')) : $filepath;
        $sourcePath = $root . '_zdata';
        $destination = str_replace('/original/', '/zoom_tiles/', $sourcePath);

        if ($computer_size > $max_picture_size) {
            echo __('Picture too big, skipped: #%d "%s" (item #%d, size: %s)',
                $file_id, $filename, $item_id, $human_size) . PHP_EOL;
        }
        elseif (file_exists($destination)) {
            if ($all_messages) {
                echo __('This picture has already been tiled: #%d "%s" (item #%d, size: %s)',
                    $file_id, $filename, $item_id, $human_size) . PHP_EOL;
            }
        }
        else {
            echo __('Processing file #%d "%s" (item #%d, size: %s)...',
                $file_id, $filename, $item_id, $human_size) . PHP_EOL;

            $zoomify->process($filepath, $destination);
        }
    }

    echo PHP_EOL;
    echo __('Process completed.') . PHP_EOL;
    echo PHP_EOL;
    if ($all_messages) {
        echo __('The process can be launched a second time without "all_messages" to check unprocessed files easily.');
    }
    else {
        echo __('The process can be launched a second time to check unprocessed files easily.');
    }
    echo PHP_EOL;
    exit;
?>
