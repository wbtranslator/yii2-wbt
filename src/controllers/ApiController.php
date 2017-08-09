<?php

namespace wbtranslator\wbt\controllers;

use wbtranslator\wbt\models\AbstractionExport;
use wbtranslator\wbt\models\AbstractionImport;
use yii\filters\ContentNegotiator;
use WBTranslator\WBTranslatorSdk;
use wbtranslator\wbt\WbtPlugin;
use yii\web\Controller;
use yii\web\Response;
use yii\base\Module;
use Exception;
use Yii;

/**
 * Class ApiController
 */
class ApiController extends Controller
{
    /**
     * @var WBTranslatorSdk
     */
    protected $sdk;

    /**
     * Should help to return json response
     *
     * @return array
     */
    public function behaviors()
    {
        return [
            [
                'class' => ContentNegotiator::className(),
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ],
            ],
        ];
    }

    /**
     * ApiController constructor.
     * @param string $id
     * @param \yii\base\Module $module
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
        $abstractionImport->saveAbstractions($translations);

        return $this->actionResponseSuccess();
    }
}