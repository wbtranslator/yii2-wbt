<?php

namespace wbtranslator\wbt\commands;

use wbtranslator\wbt\models\AbstractionExport;
use wbtranslator\wbt\models\AbstractionImport;
use wbtranslator\wbt\helpers\MessageHelper;
use WBTranslator\WBTranslatorSdk;
use wbtranslator\wbt\WbtPlugin;
use yii\base\Exception;
use yii\base\Module;
use Yii;

/**
 * Class WbtController
 * @package wbtranslator\wbt\commands
 */
class WbtController extends \yii\console\Controller
{
    /**
     * @var WBTranslatorSdk
     */
    protected $sdk;

    /**
     * WbtController constructor.
     * @param string $id
     * @param Module $module
     * @param array $config
     */
    public function __construct(string $id, Module $module, array $config = [])
    {
        parent::__construct($id, $module, $config);

        $module = WbtPlugin::getInstance();

        $client = new \GuzzleHttp\Client([
            'base_uri' => 'http://192.168.88.149:8080/api/project/'
        ]);

        $this->sdk = new WBTranslatorSdk($module->apiKey, $client ?? null);
    }

    /**
     * @param null $messsage
     * @param int $code
     * @return mixed
     */
    protected function actionResponseError($messsage = null, $code = 400)
    {
        return [
            'message' => $messsage,
            'status' => 'error',
            'code' => $code
        ];
    }

    /**
     * @param null $messsage
     * @param int $code
     * @return mixed
     */
    protected function actionResponseSuccess($messsage = null, $code = 200)
    {
        return [
            'message' => $messsage,
            'status' => 'success',
            'code' => $code
        ];
    }

    /**
     * @return mixed
     */
    public function actionExport()
    {
        $abstractionExport = new AbstractionExport();
        $dataForExport = $abstractionExport->export();

        try {
            $this->sdk->translations()->create($dataForExport);

        } catch (Exception $e) {

            Yii::error('TRANSLATOR: ' . $e->getMessage());
            return $this->actionResponseError($e->getMessage());
        }

        return $this->actionResponseSuccess();
    }

    /**
     * @return mixed
     */
    public function actionImport()
    {
        try {
            $translations = $this->sdk->translations()->all();

        } catch (\Exception $e) {
            return $this->actionResponseError();
        }

        $abstractionImport = new AbstractionImport();
        $res = $abstractionImport->saveAbstractions($translations);

        MessageHelper::getMessageImport($res);

        return $this->actionResponseSuccess();
    }
}