<?php

namespace wbtranslator\wbt\helpers;

/**
 * Class MessageHelper
 * @package wbtranslator\wbt\helpers
 */
class MessageHelper
{
    /**
     * @param $statistic
     */
    public static function getMessageImport($statistic)
    {
        if (key_exists('allDB', $statistic)) {
            echo "Imported " . $statistic['allDB'] . " translations for the database \r\n";
        }

        if (key_exists('updatedDB', $statistic)) {
            echo "Updated " . $statistic['updatedDB'] . " translations for the database \r\n";
        }

        if (key_exists('updatedDB', $statistic)) {
            echo "Created new " . $statistic['updatedDB'] . " translations for the database \r\n";
        }

        if (key_exists('allFiles', $statistic)) {
            echo "Imported " . $statistic['allFiles'] . " translations for the translation files \r\n";
        }

        if (key_exists('updatedFiles', $statistic)) {
            echo "Updated " . $statistic['updatedFiles'] . " translations for the translation files \r\n";
        }

        if (key_exists('newFiles', $statistic)) {
            echo "Created new " . $statistic['newFiles'] . " translations for the translation files \r\n";
        }
    }
}