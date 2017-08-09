<?php

namespace wbtranslator\wbt\helpers;

use yii\db\Exception;
use Yii;

/**
 * Class FilePathHelper
 * @package wbtranslator\wbt\helpers
 */
class FilePathHelper
{
    const GROUP_DELIMITER = '::';
    const LANGUAGE_DELIMITER = '||';
    const PHP_FILE_EXTENSION = '.php';
    const DB_ABSTRACT_EXTENSION = 'YiiDB';
    const FILE_EXTENSION_DELIMITER = '.';

    /**
     * @var string
     */
    private $config = [];

    /**
     * @var string
     */
    private $relativePath;

    /**
     * @param string $file
     * @param string $config
     * @return FilePathHelper
     * @throws Exception
     */
    public function setConfig(string $file, string $config): FilePathHelper
    {
        $position = strripos($file, $config);

        if ($position !== false) {

            $this->config = [$config => $file];
            $this->relativePath = substr($file, $position);

        } else {
            throw new Exception("Your config for the module $config is not correct, the message file does not belong to this configuration");
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getAlterPathName(): string
    {
        $path = str_replace([DIRECTORY_SEPARATOR, self::PHP_FILE_EXTENSION], [self::GROUP_DELIMITER, ''], $this->relativePath);
        return str_replace(self::GROUP_DELIMITER . Yii::$app->language . self::GROUP_DELIMITER,
            self::LANGUAGE_DELIMITER, $path);

    }

    /**
     * @param string $alterPathName
     * @return string
     */
    public function getRelativePath(string $alterPathName): string
    {
        return str_replace(self::GROUP_DELIMITER,
            DIRECTORY_SEPARATOR, $alterPathName);
    }

    /**
     * @param string $path
     * @param string $lang
     * @param string $group
     * @return string
     */
    public function getFilePath(string $path, string $lang, string $group): string
    {
        return $path . DIRECTORY_SEPARATOR . $lang . DIRECTORY_SEPARATOR . $group . self::PHP_FILE_EXTENSION;
    }

    /**
     * @param string $path
     * @return string
     */
    public function getDerictoryFromPath(string $path):string
    {
        $dirArray = explode(DIRECTORY_SEPARATOR,$path);
        $lastDir = array_pop($dirArray);

        if (strpos($lastDir, self::FILE_EXTENSION_DELIMITER)){
            return implode(DIRECTORY_SEPARATOR,$dirArray );
        }
        return $path;
    }
}