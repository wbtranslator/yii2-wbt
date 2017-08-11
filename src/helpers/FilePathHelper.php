<?php

namespace wbtranslator\wbt\helpers;

use wbtranslator\wbt\WbtPlugin;
use yii\db\Exception;
use Yii;

/**
 * Class FilePathHelper
 * @package wbtranslator\wbt\helpers
 */
class FilePathHelper
{
    const DB_ABSTRACT_EXTENSION = 'YiiDB';

    /**
     * Get path to project(basePath) from first element
     *
     * @param array $paths
     * @return string
     */
    public function getBasePath(array $paths): string
    {
        reset($paths);
        $keyApp = key($paths);
        $absolutPath = array_shift($paths);

        $pos = strpos($absolutPath, $keyApp);

        if ($pos !== false) {
            return substr($absolutPath, 0, $pos);
        }

        throw new Exception('Error in config file');
    }

    public function getPathsFromPluginSettings(WbtPlugin $module):array
    {
        $pathArray = $module->langMap['PhpMessageSource'];

        if (is_array($pathArray)) {

            // Prepare paths with alias
            array_walk($pathArray, function (&$item) {
                return $item = Yii::getAlias($item);
            });

            return $pathArray;
        } else {
            throw new Exception('Settings for translator is not array');
        };
    }

    /**
     * @param array $paths
     * @return array
     */
    public function createRelativePaths(array $paths): array
    {
        $apps = array_keys($paths);

        $relatives = array_map(function($path, $app){
            $pos = strpos($path, $app);

            if ($pos !== false) {
                return substr($path, $pos);
            }

        },$paths, $apps);

        return $relatives;
    }
}