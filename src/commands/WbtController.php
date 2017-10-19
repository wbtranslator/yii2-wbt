<?php

namespace wbtranslator\wbt\commands;

use wbtranslator\wbt\models\WBTranslatorAbstractionsModel;
use WBTranslator\WBTranslatorSdk;
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
        $amount = count($result['files']) + count($result['db']);
        echo 'Get ' . $amount . "abstractions from WBTranslator  \r\n";
        echo "\r\n";

        return Controller::EXIT_CODE_NORMAL;
    }
}