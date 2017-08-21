<?php

namespace wbtranslator\wbt\commands;

use wbtranslator\wbt\models\WBTranslatorAbstractionsModel;
use wbtranslator\wbt\helpers\MessageHelper;
use WBTranslator\WBTranslatorSdk;
use wbtranslator\wbt\WbtPlugin;
use yii\console\Controller;
use yii\base\Exception;
use yii\base\Module;
use Yii;

/**
 * Class WbtController
 * @package wbtranslator\wbt\commands
 */
class WbtController extends Controller
{
    /**
     * @return mixed
     */
    public function actionExport()
    {
        echo "Process ... \r\n";

        $model = new WBTranslatorAbstractionsModel;
        $result = $model->export();

        echo 'Send ' . !empty($result) ? count($result) : 0 . " abstractions to WBTranslator \r\n";
        echo "\r\n";

        return Controller::EXIT_CODE_NORMAL;
    }

    /**
     * @return mixed
     */
    public function actionImport()
    {
        $model = new WBTranslatorAbstractionsModel;
        $result = $model->import();

        echo 'Get ' . count($result) . "abstractions from WBTranslator  \r\n";
        echo "\r\n";

        return Controller::EXIT_CODE_NORMAL;
    }
}