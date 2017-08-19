<?php

namespace wbtranslator\wbt\models;

use wbtranslator\wbt\helpers\FilePathHelper;
use WBTranslator\Sdk\WBTranslatorSdk;
use wbtranslator\wbt\WbtPlugin;
use WBTranslator\Sdk\Collection;
use WBTranslator\Sdk\Config;
use Yii;

/**
 * Class WBTranslatorAbstractionsModel
 * @package wbtranslator\wbt\models
 */
class WBTranslatorAbstractionsModel
{
    /**
     * @var WBTranslatorSdk
     */
    protected $sdk;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var array
     */
    protected $wbTranslatorBd;

    /**
     * @var FilePathHelper
     */
    protected $filePathHelper;

    /**
     * WBTranslatorAbstractionsModel constructor.
     */
    public function __construct()
    {
        $this->locale = Yii::$app->language;
        $module = WbtPlugin::getInstance();

        $this->filePathHelper = new FilePathHelper;

        $this->config['api_key'] = $module->apiKey;

        if (!$this->config['api_key']) {
            throw new WBTranslatorException('Parameter WBT_API_KEY is required', 422);
        }

        $paths = $this->filePathHelper->getPathsFromPluginSettings($module);
        $basePath = $this->filePathHelper->getBasePath($paths);

        $sdkConfig = new Config;

        $sdkConfig->setApiKey($this->config['api_key'])
            ->setBasePath($basePath)
            ->setLocale(!empty($this->config['locale']) ? $this->config['locale'] : Yii::$app->language)
            ->setLangPaths($this->filePathHelper->createRelativePaths($paths));

        if (!empty($this->config['group_delimiter'])) {
            $sdkConfig->setDelimiter($this->config['group_delimiter']);
        }

        $this->sdk = new WBTranslatorSdk($sdkConfig);
    }

    public function export()
    {
        $module = WbtPlugin::getInstance();
        $fileCollection = $dbCollection = new Collection();

        if (key_exists('PhpMessageSource', $module->langMap)) {
            $fileCollection = $this->sdk->locator()->scan();
        }

        if (key_exists('DbMessageSource', $module->langMap)) {
            $dbCollection = (new WBTranslatorBD($module))->export();
        }

        $collection = new Collection(
            array_merge($dbCollection->toArray(), $fileCollection->toArray())
        );

        if ($collection) {
            return $this->sdk->translations()->create($collection);
        }
    }

    public function import()
    {
        $translations = $this->sdk->translations()->all();

        $module = WbtPlugin::getInstance();
        $arrayForFiles = [];
        $arrayForBD = [];

        foreach ($translations as $translation) {

            $isInDB = strripos($translation->getGroup()->getName(), WBTranslatorBD::GROUP_FOR_BD);

            if ($isInDB === false) {
                $arrayForFiles[] = $translation;
            } else {
                $arrayForBD[] = $translation;
            }
        }

        if (key_exists('PhpMessageSource', $module->langMap)) {
            $collection = new Collection($arrayForFiles);
            $this->sdk->locator()->put($collection);
        }

        if (key_exists('DbMessageSource', $module->langMap)) {
            $dbCollection = (new WBTranslatorBD($module))->put($arrayForBD);
        }

        return [
            'files' => $collection ?? [],
            'db' => $dbCollection ?? []
        ];
    }
}