OpenLayers Zoom (plugin for Omeka)
==================================

[OpenLayers Zoom] is a plugin for the [Omeka] platform adds a zoom widget that
creates zoom-able tiles from images and presents it in a pure javascript zoom
viewer (no Flash).

Tiles are automatically created when the selected image is saved.

This plugin is compatible with [IIP Image] realtime tiles server, which avoids
creation and storage of tiles.

This plugin uses the [OpenLayers] widget to display images and the base of the
code was built for [OldsMapOnline].

Tiles that are created follow the [Zoomify] format.

This plugin is upgradable to [Omeka S] via the plugin [Upgrade to Omeka S], that
installs the module [IIIF Server], that contains the same tile builder and a
simple image server.

Visit the [OpenLayers Zoom demo] for more info.


Installation
------------

PHP should be installed with the extension `exif` in order to get the size of
images. This is the case for all major distributions and providers. At least one
of the php extensions `[GD]` or `[Imagick]` are recommended. They are installed
by default in most servers. If not, the image server will use the command line
[ImageMagick] tool `convert`.

Unzip [OpenLayers Zoom] into the plugin directory, rename the folder `OpenLayersZoom`
if needed, then install it from the settings panel.


Usage
-----

The viewer is displayed via three mechanisms, plus the old one. So, according to
your needs, you may use the default hook or add the code below in the
`items/show.php` file of your theme or anywhere else.

* Default hook `public_items_show`

This hook is set by default, but an option allows to remove it.

* Helper (recommended)

This can be used anywhere in the theme. The record can be an item or a file.

```php
    <?php echo $this->openLayersZoom()->zoom($record); ?>
```

If a collection or an item contains multiple files and some are zoomed, and some
are not, you have to check if the image is zoomed. The `files_for_item()` may be
replaced by such lines:

```php
    <h3><?php echo __('Files'); ?></h3>
    <div id="item-images">
    <?php
    foreach ($item->getFiles() as $file):
        $isFileZoomed = $this->openLayersZoom()->isZoomed($file);
        // Zoom file markup.
        if ($isFileZoomed):
            echo $this->openLayersZoom()->zoom($file);
        // Standard file markup (see options in globals.php if needed).
        else:
            echo file_markup($file, array(), array('class' => 'item-file'));
        endif;
    endforeach;
    ?>
    </div>
```

Other useful functions, depending on your collection (when there are multiple
file on an item and some are zoomed, other ones not):

```php
    $filesCount = $item->fileCount();
    $zoomCount = $this->openLayersZoom()->zoomedFilesCount($item);
    $hasZoomedImage = (boolean) $zoomCount;
    $zoomedFiles = $this->openLayersZoom()->getZoomedFiles($item);
```

* Shortcode

    - Currently, only one shortcode can be added by page.
    - In a field that can be shortcoded: `[zoom]` (default is the current item
    or file).
    - Default in theme: `<?php echo $this->shortcodes('[zoom]'); ?>`
    - With all options:

```php
    <?php echo $this->shortcodes('[zoom record_id=1 record_type=item]'); ?>
```

* Old hook `open_layers_zoom_display_file`

This hook will be removed in the next release. In the `items/show.php` of your
theme, add:

```php
    <div class="openlayerszoom-images">
        <?php
        foreach ($item->Files as $file):
            fire_plugin_hook('open_layers_zoom_display_file', array('file' => $file));
        endforeach; ?>
    </div>
```

Note that the id attribute `item-images` of the div wrapper of the previous
releases has been replaced by the class `openlayerszoom-images` to simplify
the loading of multiple zoomed files. This class is needed for the javascript
and is automatically added.

Finally, copy `views/shared/css/OpenLayersZoom.css` in your theme if you want to
modify the size/appearance of the zoom viewer.

Note: Some issues may appear on some browsers when multiple OpenLayersZoom are
displayed on the same page.


Use
---

Edit an item with an image attached to it. On the left is a zoom tab. Check the
box next to the image thumbnail and save changes. The image will now be
presented as a zoomed image in the public item page.

Currently, tiling is made without job process, so you may have to increase the
max allowed time (and the memory limit) for process in `php.ini`.

It is possible to bulk create tiles with the script `bulk_build_tiles.php`
provided at the root of the plugin. Simply edit it, set the collections or the
items to process, and run it:

```sh
    # Go to the root of the plugin.
    cd /path/to/my/omeka/plugins/OpenLayersZoom
    # Edit the file to set the items to zoom (see instruction inside).
    nano bulk_build_tiles.php
    # To run the script.
    php -f bulk_build_tiles.php
```

The script can be launched multiple times: if a file is already tiled, it won’t
retiled, but skipped.

*IMPORTANT*: Check or update the rights of the subfolder of `files/zoom_tiles`,
in particular when the folder of items was created by the server and you try to
update them.

For huge images, it’s recommanded to create tiles offline via a specialized
photo software, eventually with a [Zoomify] plugin, or to use a script that
calls the `ZoomifyFileProcessor.php` library, else to use [IIP Image].

If an [IIP Image] server is used, you have to add the query url for each item
that use it in the element `Tile Server URL` of the element set `Item Type Metadata`.
This element is hard coded in the code, and is not created during the install.
So you have to edit item types, then create this element, and add it to each
item type you use. Only one image can be used by item and it must be uploaded
too, because the widget should know the size of the image to compute the tiles
to ask to the server. To check your config, set the url from the [official example]
`http://vips.vtech.fr/cgi-bin/iipsrv.fcgi?zoomify=/mnt/MD1/AD00/plan_CHU-4HD-01/FOND.TIF`
in the field `Tile Server URL`, and upload an image with a size of 9911 x 6100
pixels (width x height). If the size is different, it is not important, but some
tiles may be misplaced. Anyway, it will proove that the plugin works.


Warning
-------

Use it at your own risk.

It’s always recommended to backup your files and your databases and to check
your archives regularly so you can roll back if needed.


Troubleshooting
---------------

See online issues on the [plugin issues] page on GitHub.


License
-------

This plugin is published under [GNU/GPL].

This program is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation; either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT
ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
details.

You should have received a copy of the GNU General Public License along with
this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.


Contact
-------

Current maintainers:

* Daniel Berthereau (see [Daniel-KM])
* [Peter Binkley] for [University of Alberta Libraries]
* [Matt Miller]

First version of this plugin has been built by [Matt Miller].
Thanks to Nancy Moussa @ U of Michigan for bug fixes and individual unzoom feature.
It has been improved by [Peter Binkley] for [University of Alberta Libraries].
The upgrade for Omeka 2.0 has been built for [Mines ParisTech].


Copyright
---------

* Copyright Daniel Berthereau, 2013-2018
* Copyright Peter Binkley, 2012-2013
* Copyright Matt Miller, 2012

See copyrights for libraries in files inside `vendor` folder.


[OpenLayers Zoom]: https://github.com/Daniel-KM/Omeka-plugin-OpenLayersZoom
[Omeka]: https://omeka.org
[IIP Image]: http://iipimage.sourceforge.net
[OpenLayers]: http://www.openlayers.org
[OldsMapOnline]: http://www.oldmapsonline.org
[Zoomify]: http://www.zoomify.com
[Omeka S]: https://omeka.org/s
[Upgrade to Omeka S]: https://github.com/Daniel-KM/Omeka-plugin-UpgradeToOmekaS
[IIIF Server]: https://github.com/Daniel-KM/Omeka-S-module-IiifServer
[OpenLayers Zoom demo]: http://thisismattmiller.com/zoom
[OpenLayers Zoom]: https://github.com/thisismattmiller/OpenLayers-Omeka-Zoom-Plugin
[GD]: https://secure.php.net/manual/en/book.image.php
[Imagick]: https://php.net/manual/en/book.imagick.php
[ImageMagick]: https://www.imagemagick.org/
[official example]: https://openlayers.org/en/latest/examples/zoomify.html
[plugin issues]: https://github.com/Daniel-KM/Omeka-plugin-OpenLayersZoom/issues
[GNU/GPL]: https://www.gnu.org/licenses/gpl-3.0.html
[Daniel-KM]: https://github.com/Daniel-KM "Daniel Berthereau"
[Peter Binkley]: https://github.com/pbinkley
[University of Alberta Libraries]: https://github.com/ualbertalib
[Matt Miller]: https://github.com/thisismattmiller
[Mines ParisTech]: http://bib.mines-paristech.fr
