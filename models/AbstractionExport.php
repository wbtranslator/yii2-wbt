<?php

namespace wbtranslator\wbt\models;

use wbtranslator\wbt\helpers\FilePathHelper;
use wbtranslator\wbt\WbtPlugin;
use WBTranslator\Translation;
use WBTranslator\Collection;
use WBTranslator\Group;
use yii\db\Query;

/**
 * Class AbstractionExport
 * @package WBT\PluginYii\Models
 */
class AbstractionExport extends AbstractionBase
{
    /**
     * @return array
     */
    private function getDataFromFile(): array
    {
        $abstractions = [];

        foreach ($this->localeDirectorys as $key => $localeDirectory) {
            foreach (\yii\helpers\FileHelper::findFiles($localeDirectory) as $file) {

                $absolutePath = \yii\helpers\FileHelper::normalizePath($file);

                if (file_exists($absolutePath)) {

                    $data = include $absolutePath;$data = include $absolutePath;

                    if (!empty($data) && is_array($data)) {

                        $alterPathName = $this->filePathHelper
                            ->setConfig($absolutePath, $key)
                            ->getAlterPathName();

                        $abstractions[$alterPathName] = $data;
                    }
                }
            }
        }

        return $abstractions;
    }

    /**
     * @return array
     */
    private function getDataFromBD(): array
    {
        $rows = (new Query())
            ->from($this->localeBD['sourceMessage'])
            ->leftJoin($this->localeBD['message'],
                $this->localeBD['message'] . '.id = ' . $this->localeBD['sourceMessage'] . '.id')
            ->where(['language' => $this->locale])
            ->all();

        $abstractions = [];

        foreach ($rows as $row) {

            $row = (object)$row;
            $cat = FilePathHelper::DB_ABSTRACT_EXTENSION . '::' . $row->category;
            $abstractions[$cat][$row->message] = $row->translation;
        }

        return $abstractions;
    }

    /**
     * @return array
     */
    private function getAbstrations(): array
    {
        $resourses = [];
        $module = WbtPlugin::getInstance();

        // if you are use plugin with basic settings
        if (!$module->langMap) {
            $resourses = array_merge($resourses, $this->getDataFromFile());

            // if you have settings for  BD or PHPFile translations
        } else {

            if (key_exists('PhpMessageSource', $module->langMap)) {
                $resourses = array_merge($resourses, $this->getDataFromFile());
            }
            if (key_exists('DbMessageSource', $module->langMap)) {
                $resourses = array_merge($resourses, $this->getDataFromBD());
            }
        }

        return $resourses;
    }

    /**
     * @return Collection
     */
    public function export(): Collection
    {
        $collection = new Collection();

        foreach ($this->getAbstrations() as $groupKey => $abstractNames) {

            $group = $this->getGoups($groupKey);

            //Pack a translation
            foreach ($abstractNames as $abstractName => $originalValue) {

                if (!$abstractName) {
                    continue;
                }

                $translation = new Translation();
                $translation->addGroup($group);
                $translation->setAbstractName($abstractName);
                $translation->setOriginalValue(!empty($originalValue) ? (string)$originalValue : '');
                $collection->add($translation);
            }
        }

        return $collection;
    }

    /**
     * @param string $groupKey
     * @return Group
     */
    private function getGoups(string $groupKey): Group
    {
        $group = new Group();
        $isGroupHasDBName = strrpos($groupKey, FilePathHelper::DB_ABSTRACT_EXTENSION);

        if ($isGroupHasDBName === false) {

            $group_layers = explode(FilePathHelper::LANGUAGE_DELIMITER, $groupKey);
            $group->setName(array_pop($group_layers));

            foreach ($group_layers as $gr) {

                $parentGroup = new Group();
                $parentGroup->setName($gr);
                $group->addParent($parentGroup);
            }
        } else {
            $group->setName($groupKey);
        }

        return $group;
    }

}
