<?php

namespace wbtranslator\wbt\models;

use wbtranslator\wbt\helpers\FilePathHelper;
use WBTranslator\Sdk\Translation;
use WBTranslator\Sdk\Collection;
use wbtranslator\wbt\WbtPlugin;
use WBTranslator\Sdk\Group;
use yii\db\Query;
use Yii;

class WBTranslatorBD
{
    const GROUP_FOR_BD = 'YiiDB';

    /**
     * @var array
     */
    protected $localeBD = [
        'sourceMessage' => 'source_message',
        'message' => 'message'
    ];

    /**
     * @var string
     */
    protected $locale;

    /**
     * WBTranslatorBD constructor.
     * @param WbtPlugin $module
     */
    public function __construct(WbtPlugin $module)
    {
        $this->locale = Yii::$app->language;

        if (key_exists('messageTable', $module->langMap['DbMessageSource'])){
            $this->localeBD['message'] = $module->langMap['DbMessageSource']['messageTable'];
        }

        if (key_exists('sourceMessageTable', $module->langMap['DbMessageSource'])){
            $this->localeBD['sourceMessage'] = $module->langMap['DbMessageSource']['sourceMessageTable'];
        }
    }

    /**
     * @return array
     */
    private function getTranslations(): array
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

    /**
     * @return Collection
     */
    public function export(): Collection
    {
        $abstractions = $this->getTranslations();

        $collection = new Collection();

        foreach ($this->getTranslations() as $groupKey => $abstractNames) {

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
     * @param array $arrayForBD
     */
    public function put(array $translations)
    {
        $newTranslations = $this->updateOldBDTranslations($translations);
        $this->createNewDBTranslations($newTranslations);
    }

    /**
     * @param array $translations
     * @return array
     */
    private function updateOldBDTranslations(array $translations): array
    {
        //Get exits translations
        $query = (new Query())
            ->from($this->localeBD['sourceMessage'])
            ->leftJoin($this->localeBD['message'],
                $this->localeBD['message'] . '.id = ' . $this->localeBD['sourceMessage'] . '.id');

        foreach ($translations as $translation) {
            $query->orWhere(['message' => $translation->getAbstractName()]);
        }

        $oldTranslations = $query->all();

        // Updating ald translations
        $updated = 0;
        foreach ($translations as $key => $translation) {
            foreach ($oldTranslations as $oldTranslation) {

                $isOldTranslation = $oldTranslation['message'] === $translation->getAbstractName()
                    && $translation->getLanguage() === $oldTranslation['language'];

                if ($isOldTranslation) {
                    //Check arrays for translations update;
                    if ($translation->getTranslation() !== $oldTranslation['translation']) {

                        Yii::$app->db->createCommand()->update($this->localeBD['message'], [
                            'translation' => $translation->getTranslation()
                        ], [
                            'id' => $oldTranslation['id'],
                            'language' => $oldTranslation['language']
                        ])->execute();

                        $updated++;
                    }

                    unset($translations[$key]);
                }
            }
        }
        return $translations;
    }

    /**
     * Add new translations
     *
     * @param $translations
     */
    private function createNewDBTranslations(array $translations)
    {
        $abstractNames = array_map(function ($item) {
            return $item->getAbstractName();
        }, $translations);

        //Take messages(abstractNames) for new translations
        $newIdsTranslations = array_unique($abstractNames);

        //Get IDs for translations
        $messages = (new Query())
            ->from($this->localeBD['sourceMessage'])
            ->where(['message' => $newIdsTranslations])
            ->all();

        // Array with new translations
        $translations = array_map(function ($item) use ($messages) {
            foreach ($messages as $message) {

                if ($message['message'] === $item->getAbstractName()) {
                    return [$message['id'], $item->getLanguage(), $item->getTranslation()
                    ];
                }
            }
        }, $translations);

        // save translations
        Yii::$app->db
            ->createCommand()
            ->batchInsert($this->localeBD['message'], ['id', 'language', 'translation'], $translations)
            ->execute();
    }
}