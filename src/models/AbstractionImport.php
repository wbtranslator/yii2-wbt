<?php

namespace wbtranslator\wbt\models;

use WBTranslator\Interfaces\GroupInterface;
use WBTranslator as WBTranslatorSdk;
use WBTranslator\Translation;
use yii\helpers\FileHelper;
use yii\base\Exception;
use yii\db\Query;
use Yii;

/**
 * Class AbstractionImport
 * @package wbtranslator\wbt\models
 */
class AbstractionImport extends AbstractionBase
{
    const GROUP_FOR_BD = 'YiiDB';

    private $translations = [];

    /**
     * @param \WBTranslator\Collection $translations
     * @return array
     */
    public function saveAbstractions(WBTranslatorSdk\Collection $translations): array
    {
        list($fileTranslations, $bdTranslations) = $this->splitTranslationType($translations);

        $dbResult = $fileResult = [];

        if ($bdTranslations) {
            $dbResult = $this->saveToBd($bdTranslations);
        };

        if ($fileTranslations) {
            $fileResult = $this->saveToFiles($fileTranslations);
        }

        return array_merge($dbResult, $fileResult);
    }

    /**
     * @param \WBTranslator\Collection $translations
     * @return array
     */
    private function splitTranslationType(WBTranslatorSdk\Collection $translations): array
    {
        $arrayForFiles = [];
        $arrayForBD = [];
        foreach ($translations as $translation) {

            $isInDB = strripos($translation->getGroup()->getName(), self::GROUP_FOR_BD);

            if ($isInDB === false) {
                $arrayForFiles[] = $translation;
            } else {
                $arrayForBD[] = $translation;
            }
        }


        return [$arrayForFiles, $arrayForBD];
    }

    /**
     * @param array $translations
     * @return array
     */
    private function saveToBd(array $translations): array
    {
        list($newTranslations, $updated) = $this->updateOldBDTranslations($translations);
        $this->createNewDBTranslations($newTranslations);

        return ['allDB' => count($translations), 'updatedDB' => $updated, 'createdDB' => count($newTranslations)];
    }

    /**
     * Updating old translations
     *
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
        return [$translations, $updated];
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

    /**
     * @param array $translations
     * @return array
     * @throws Exception
     */
    private function saveToFiles(array $translations): array
    {
        $files = $this->getPaths($translations);
        $result = [];

        foreach ($files as $file) {
            if (file_exists($file)) {

                $data = include $file;

                if (!is_array($data)) {
                    throw new Exception('invalid array');
                }

                $transForWrite = $this->mergeTranslations($data, $this->translations, $file);

                $result[] = ['updatedFiles' => $transForWrite['updatedFiles']];
                $result[] = ['newFiles' => $transForWrite['newFiles']];

                $this->writeFile($transForWrite['translations'], $file);

            } else {

                $directory = $this->filePathHelper->getDerictoryFromPath($file);
                $result[] = ['newFiles' => count($translations)];

                FileHelper::createDirectory($directory, 0755, true);
                $this->writeFile($this->translations, $file);
            }
        }

        $result['allFiles'] = count($translations);

        return $this->sortStatistic($result);
    }

    /**
     * @param array $stat
     * @return array
     * @internal param array $result
     */
    private function sortStatistic(array $stat):array
    {
        $result['allFiles'] = $stat['allFiles'];
        $result['updatedFiles'] = array_sum(array_column($stat, 'updatedFiles'));
        $result['newFiles'] = array_sum(array_column($stat, 'newFiles'));

        return $result;
    }

    /**
     * @param array $oldTranslations
     * @param array $newTranslations
     * @param string $file
     * @return array
     */
    private function mergeTranslations(array $oldTranslations, array $newTranslations, string $file)
    {
        $translations = $newTrans = [];
        $isAddToTrans = true;
        $countUpdated = 0;

        foreach ($newTranslations as $key => $newTranslation) {
            if ($newTranslation['filePath'] === $file) {
                foreach ($oldTranslations as $abstractName => $oldTranslation) {
                    if ($abstractName === $newTranslation['abstractName']) {

                        $translations[] = [
                            'abstractName' => $abstractName,
                            'translation' => $newTranslation['translation'],
                            'filePath' => $file
                        ];

                        if ($newTranslation['translation'] !== $oldTranslation){
                            $countUpdated++;
                        }

                        $isAddToTrans = false;
                        unset($oldTranslations[$abstractName]);
                    }
                }
                if ($isAddToTrans) {
                    $newTrans[] = $newTranslation;
                }

                $isAddToTrans = true;
            }
        }

        $oldTranslations = array_map(function ($item, $key) use ($file) {
            return [
                'abstractName' => $key,
                'translation' => $item,
                'filePath' => $file
            ];
        }, $oldTranslations, array_keys($oldTranslations));

        $transForWrite = array_merge($translations, $oldTranslations, $newTrans);

        return ['translations' => $transForWrite,
            'updatedFiles' => $countUpdated, //count($translations),
            'newFiles' => count($newTrans)
        ];
    }

    /**
     * @param array $translations
     * @param string $file
     * @return bool
     * @throws Exception
     */
    public function writeFile(array $translations, string $file)
    {
        $content = $this->toString($translations, ',' . PHP_EOL, $file);
        $result = file_put_contents($file,
            '<?php' . PHP_EOL . PHP_EOL . 'return [' . PHP_EOL . $content . PHP_EOL . '];');

        if ($result === false) {
            throw new Exception('Error writing file');
        } else {
            return true;
        }
    }

    /**
     * @param array $translations
     * @param string $glue
     * @param string $file
     * @return string
     */
    private function toString(array $translations, string $glue, string $file): string
    {
        $content = '';

        foreach ($translations as $translation) {

            if ($file === $translation['filePath']) {
                $content .= '"' . $translation['abstractName'] . '" => "' . $translation['translation'] . '"' . $glue;
            }
        }

        $content = substr($content, 0, 0 - strlen($glue));
        return $content;
    }

    /**
     * @param array $translations
     * @return array
     * @throws Exception
     */
    private function getPaths(array $translations): array
    {
        $paths = [];

        foreach ($translations as $translation) {

            if ($translation->getGroup()->getParent() instanceof GroupInterface) {
                $path = $this->getRecursivePath($translation->getGroup()->getParent());
            } else {
                $path = $translation->getGroup()->getName();
            }

            $pathToMessages = $this->getPathToMessage($path);

            foreach ($this->localeDirectorys as $localeDirectory) {
                $pos = strripos($localeDirectory, $path);

                if ($pos !== false) {

                    $fullStringLen = strlen($localeDirectory);
                    $partStringLen = strlen($path);
                    $langPathLen = $fullStringLen - $partStringLen - $pos;

                    $pathToMessages = substr($localeDirectory, 0, -$langPathLen);
                }
            }

            if (!$pathToMessages) {
                throw new Exception("Config file have no any relations with path $path");
            }

            $pathToFile = $this->filePathHelper->getFilePath($pathToMessages,
                $translation->getLanguage(),
                $translation->getGroup()->getName());

            $this->moderateTranslation($translation, $pathToFile);

            $paths[] = $pathToFile;
        }

        return array_unique($paths);
    }

    /**
     * @param GroupInterface $group
     * @param string $path
     * @return string
     */
    private function getRecursivePath(GroupInterface $group, string $path = ''): string
    {
        $path ? $path .= '::' . $group->getName() : $path .= $group->getName();

        if ($group->hasParent()) {
            return $this->getRecursivePath($group->getParent(), $path);
        } else {
            return $path;
        }
    }

    /**
     * @param Translation $translation
     * @param string $pathToFile
     */
    public function moderateTranslation(Translation $translation, string $pathToFile)
    {
        $this->translations[] = [
            'abstractName' => $translation->getAbstractName(),
            'translation' => $translation->getTranslation(),
            'language' => $translation->getLanguage(),
            'filePath' => $pathToFile,
        ];
    }

    /**
     * @param string $path
     * @return string
     */
    private function getPathToMessage(string $path): string
    {
        $path = $this->filePathHelper->getRelativePath($path);
        $pathToMessages = '';

        foreach ($this->localeDirectorys as $localeDirectory) {
            $pos = strripos($localeDirectory, $path);

            if ($pos !== false) {

                $fullStringLen = strlen($localeDirectory);
                $partStringLen = strlen($path);
                $langPathLen = $fullStringLen - $partStringLen - $pos;

                $pathToMessages = substr($localeDirectory, 0, -$langPathLen);
            }
        }

        return $pathToMessages;
    }
}
