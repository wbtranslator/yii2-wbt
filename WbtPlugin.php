<?php

namespace wbtranslator\wbt;

use common\modules\wbtplugin\commands\WbtController;
use common\modules\wbtplugin\controllers\ApiController;
use yii\db\Exception;

/**
 * wbt-plugin module definition class
 */
class WbtPlugin extends \yii\base\Module implements \yii\base\BootstrapInterface
{
    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'common\modules\wbtplugin\controllers';

    /**
     * @var string
     */
    public $defaultController = ApiController::class;

    /**
     * @var array
     */
    public $langMap;

    /**
     * @var string
     */
    public $apiKey;

    /**
     * @param \yii\base\Application $app
     */
    public function bootstrap($app)
    {
        if ($app instanceof \yii\console\Application) {
            $this->controllerNamespace = 'common\modules\wbtplugin\commands';
        }
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if (!$this->apiKey) {
            throw new Exception("apiKey is missing");
        }
    }
}
