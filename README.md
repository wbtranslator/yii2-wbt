# WBTranslator Plugin Yii2
#### Installation by composer
```
composer require wbtranslator/yii2-wbt
```
#### Set configs for plugin

If you are using basic version, set your default language in `console.php`.

```
 $config = [
     ...
     'language' => 'ru',
     ...
 ];
```
If you are using Advanced template, set default language in `common/main.php` or in `console/main.php`.

Add wbt_plugin to your bootstrap config in console app
```
 $config = [
     ...
    'bootstrap' => ['log', 'wbt_plugin'],
     ...
 ];
```


Add configs for plugin in the same config file, `$config` array. 
```
 'modules' => [
         ...
         'wbt_plugin' => [
             'class' => wbtranslator\wbt\WbtPlugin::class,
             'langMap' => [
                 'PhpMessageSource' => [
                     'basic' => '@app/messages',
                 ]
             ],
             'apiKey' => 'your_project_api_key'
         ],
         ...
     ],
```
In config option `langMap` you can customise your translations storage. 

In config option `PhpMessageSource` in section `basic` you must write your project name.

If you are using Advanced template, and you have more then one default storage with your translations, you can add it's 
all to `PhpMessageSource` config option.

```
 'modules' => [
         ...
         'wbt_plugin' => [
             'class' => wbtranslator\wbt\WbtPlugin::class,
             'langMap' => [
                 'PhpMessageSource' => [
                     'common' => '@common/messages',
                     'common' => '@common/my-messages',
                     'frontend' => '@frontend/messages',
                     'backend' => '@backend/messages',
                     'console' => '@console/messages',
                 ]
             ],
             'apiKey' => 'your_project_api_key'
         ],
         ...
     ],
```

In plugin option `PhpMessageSource` key - is the name of your application and application folder, value - is path to your
translation folder in this application.

Also you can extract your translations from database.

```
 'modules' => [
         ...
         'wbt_plugin' => [
             'class' => wbtranslator\wbt\WbtPlugin::class,
             'langMap' => [
                 'DbMessageSource' => [
                    'messageTable' => 'message',
                    'sourceMessageTable' => 'source_message'
                 ],
             ],
             'apiKey' => 'your_project_api_key'
         ],
         ...
     ],
```
In options `messageTable`, `sourceMessageTable` you can fill out your custom tables for translations. 

```
'wbt_plugin' => [
             ...
             'apiKey' => 'your_project_api_key'
             ...
         ],
```
`apiKey` - is required option, that you can find in API section of your project.

###Set folders permissions with translations to write access from server
```sh
$ chmod -R 775 ./resources/lang/
```

#### Use web interfase
#### Send abstractions to WBTranslator from console command 
```	
php yii  wbt_plugin/wbt/export
```


#### Get abstractions from WBTranslator and save them to lang directorys
```	
php yii  wbt_plugin/wbt/import
```
