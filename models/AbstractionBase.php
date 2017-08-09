<?php

namespace wbtranslator\wbt\models;

use wbtranslator\wbt\helpers\FilePathHelper;
use wbtranslator\wbt\WbtPlugin;
use yii\helpers\FileHelper;
use Exception;
use Yii;

/**
 * Class AbstractionBase
 * @package wbtranslator\wbt\models
 */
abstract class AbstractionBase
{
    /**
     * @var array
     */
    protected $langPath = [];

    /**
     * @var string
     */
    protected $locale;

    /**
     * @var array
     */
    protected $localeDirectorys = [];

    /**
     * @var array
     */
    protected $localeBD = [
        'sourceMessage' => 'source_message',
        'message' => 'message'
    ];

    /**
     * @var FilePathHelper
     */
    public $filePathHelper;

    /**
     * AbstractionBase constructor.
     * @param string|null $locale
     */
    public function __construct(string $locale = null)
    {
        $this->locale = Yii::$app->language;
        $module = WbtPlugin::getInstance();

        if (!$module->langMap){
            $this->getDefaultLangPath();
        } else {

            if (key_exists('PhpMessageSource', $module->langMap)){
                $this->getPathsFromPluginSettings($module);
            }

            if (key_exists('DbMessageSource', $module->langMap)){
                $this->getPathsFromDB($module);
            }
        }

        foreach ($this->langPath as $key => $langPath) {
            $this->localeDirectorys[$key] = $this->getLocaleDirectory($this->locale, $langPath);
        }

        $this->filePathHelper = new FilePathHelper;
    }

    /**
     * @param string|null $locale
     * @param string $path
     * @return string
     */
    public function getLocaleDirectory(string $locale = null, string $path): string
    {
        $path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR .
            ($locale ?: $this->locale) . DIRECTORY_SEPARATOR;
        return FileHelper::normalizePath($path);
    }

    /**
     * Get default langPath
     */
    private function getDefaultLangPath()
    {
        if (key_exists('app*', Yii::$app->i18n->translations)) {
            if (key_exists('class', Yii::$app->i18n->translations['app*'])) {

                $myWay = mb_strtolower(Yii::$app->i18n->translations['app*']['class']);
                $classWay = mb_strtolower(yii\i18n\PhpMessageSource::class);

                if ($myWay === $classWay) {
                    if (key_exists('basePath', Yii::$app->i18n->translations['app*'])) {

                        $key = array_pop(explode(DIRECTORY_SEPARATOR, Yii::getAlias('@app')));
                        $this->langPath[$key] = Yii::getAlias(Yii::$app->i18n->translations['app*']['basePath']);
                    }
                }
            }
        }
    }

    /**
     * Get ways from plugin settings
     *
     * @param $module
     * @throws Exception
     */
    private function getPathsFromPluginSettings(WbtPlugin $module)
    {
        $pathArray = $module->langMap['PhpMessageSource'];

        if (is_array($pathArray)) {

            // Prepare paths with alias
            array_walk($pathArray, function (&$item) {
                return $item = Yii::getAlias($item);
            });

            $this->langPath = $pathArray;
        } else {
            throw new Exception('Settings for translator is not array');
        };
    }

    /**
     * @param WbtPlugin $module
     */
    private function getPathsFromDB(WbtPlugin $module)
    {
        if (key_exists('messageTable', $module->langMap['DbMessageSource'])){
            $this->localeBD['message'] = $module->langMap['DbMessageSource']['messageTable'];
        }

        if (key_exists('sourceMessageTable', $module->langMap['DbMessageSource'])){
            $this->localeBD['sourceMessage'] = $module->langMap['DbMessageSource']['sourceMessageTable'];
        }
    }
}