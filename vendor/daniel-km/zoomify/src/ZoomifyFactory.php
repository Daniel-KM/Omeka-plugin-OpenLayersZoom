<?php
namespace DanielKm\Zoomify;

/**
 * Class ZoomifyFactory
 * @package DanielKm\Zoomify
 */
class ZoomifyFactory
{
    /**
     * Initialize the Zoomify library.
     *
     * @param array $config
     * @return Zoomify
     */
    public function __invoke(array $config = null)
    {
        if (is_null($config)) {
            $config = array();
        }

        // Check the autoload.
        if (!class_exists('DanielKm\Zoomify\Zoomify')) {
            require_once __DIR__ . DIRECTORY_SEPARATOR . 'Zoomify.php';
        }
        return new Zoomify($config);
    }
}
