Zoomify (php library)
=====================

[Zoomify] is a stand-alone library to generate tiles of big images in order to
zoom them instantly. The tiles are created with the Zoomify format and they can
be used with [OpenSeadragon], [OpenLayers] and various viewers.

It is integrated in the module [IIIF Server] of the open source digital library
[Omeka S] to create images compliant with the specifications of the [International Image Interoperability Framework].

This library is available as a packagist [package].


Usage
-----

### Direct use without the factory

```php
    // Setup the Zoomify library.
    $zoomify = new \DanielKm\Zoomify\Zoomify($config);

    // Process a source file and save tiles in a destination folder.
    $result = $zoomify->process($source, $destination);
```

### Direct invocation with the factory

```php
    // Setup the Zoomify library.
    $factory = new \DanielKm\Zoomify\ZoomifyFactory;
    $zoomify = $factory($config);

    // Process a source file and save tiles in a destination folder.
    $result = $zoomify->process($source, $destination);
```


Supported image libraries
-------------------------

The format of the image source can be anything that is managed by the image
library:

- PHP Extension [GD] (>=2.0)
- PHP extension [Imagick] (>=6.5.6)
- Command line `convert` [ImageMagick] (>=6.0)

The PHP library `exif` should be installed (generally enabled by default).


History
-------

The [Zoomify viewer] was a popular viewer to display large images in the past
with Flash (and now without it, of course). It’s still used in various places,
because it’s not only a viewer, but a tile builder too and it has some
enterprise features. Its popularity was related to the fact that an extension
was added to a popular commercial image application. An old description of the
format can be found [here].

The Zoomify class is a port of the ZoomifyImage python script to a PHP class.
The original python script was written by Adam Smith, and was ported to PHP
(in the form of ZoomifyFileProcessor) by Wes Wright. The port to Imagick was
done by Daniel Berthereau for the [Bibliothèque patrimoniale] of [Mines ParisTech]
in the plugin [OpenLayers Zoom] for [Omeka Classic].

Ported from Python to PHP by Wes Wright
Cleanup for Drupal by Karim Ratib (kratib@open-craft.com)
Cleanup for Omeka Classic by Daniel Berthereau (daniel.github@berthereau.net)
Conversion to ImageMagick by Daniel Berthereau
Set as a stand-alone library in Packagist and integrated in Omeka S.

Some code is shared with the [Deepzoom Library].


Warning
-------

Use it at your own risk.

It’s always recommended to backup your files and your databases and to check
your archives regularly so you can roll back if needed.


Troubleshooting
---------------

See the online [issues] page on GitHub.


License
-------

This library is licensed under the [GNU/GPL] v3.

This program is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the
Free Software Foundation; either version 2 of the License, or (at your option)
any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program; if not, write to the Free Software Foundation, Inc.,
59 Temple Place, Suite 330, Boston, MA  02111-1307  USA


Copyright
---------

* Copyright 2005 Adam Smith (asmith@agile-software.com)
* Copyright Wes Wright (http://greengaloshes.cc)
* Copyright Justin Henry (http://greengaloshes.cc)
* Copyright 2014-2017 Daniel Berthereau (Daniel.github@Berthereau.net)


[Zoomify]: https://github.com/Daniel-KM/LibraryZoomify
[OpenSeadragon]: https://openseadragon.github.io/examples/tilesource-zoomify/
[OpenLayers]: https://openlayers.org/en/latest/examples/zoomify.html
[International Image Interoperability Framework]: http://iiif.io
[IIIF Server]: https://github.com/Daniel-KM/Omeka-S-module-IiifServer
[Omeka S]: https://omeka.org/s
[package]: https://packagist.org/packages/daniel-km/zoomify
[GD]: https://secure.php.net/manual/en/book.image.php
[Imagick]: https://php.net/manual/en/book.imagick.php
[ImageMagick]: https://www.imagemagick.org/
[Zoomify viewer]: http://www.zoomify.com/
[here]: https://ecommons.cornell.edu/bitstream/handle/1813/5410/Introducing_Zoomify_Image.pdf
[Omeka Classic]: https://omeka.org
[OpenLayers Zoom]: https://github.com/Daniel-KM/OpenLayersZoom
[Deepzoom Library]: https://github.com/Daniel-KM/LibraryDeepzoom
[issues]: https://github.com/Daniel-KM/LibraryZoomify/issues
[GNU/GPL]: https://www.gnu.org/licenses/gpl-3.0.html
[Bibliothèque patrimoniale]: https://patrimoine.mines-paristech.fr
[Mines ParisTech]: http://mines-paristech.fr
[Daniel-KM]: https://github.com/Daniel-KM "Daniel Berthereau"
