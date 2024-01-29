<?php

namespace KTableManager;

use rex_extension;
use rex_extension_point;
use rex_file;
use rex_sql;

class Discovery
{
    /**
     * @return array
     */
    public static function getTableManagerPaths(): array
    {
        return rex_extension::registerPoint(new rex_extension_point('KREATIF_TABLEMANAGER_PATHS', []));
    }

    /**
     * @param string $tableName
     * @return string[]
     */
    public static function getTableManagerFiles(string $tableName): array
    {
        $files = [];
        foreach (self::getTableManagerPaths() as $path) {
            $fileName = ltrim($tableName, 'rex_');
            $fileName = $path . '/' . $fileName . '.php';

            if (rex_file::get($fileName)) {
                $files[] = $fileName;
            }
        }
        return $files;
    }

    public static function executeTableManagers(string $tableName): bool
    {
        $files = static::getTableManagerFiles($tableName);
        if (count($files) > 0) {
            foreach ($files as $file) {
                include_once $file;
            }

            // remove all unused fields
            self::removeUnusedFields($tableName);
            return true;
        }
        return false;
    }

    public static function getPossibleTableManagers(): array
    {
        $files = [];
        foreach (self::getTableManagerPaths() as $path) {
            $_files = \rex_finder::factory($path)->filesOnly()->getIterator();
            foreach ($_files as $file) {
                $files[] = \rex::getTablePrefix() . preg_replace('/\.php/', '', $file->getFilename());
            }
        }
        return $files;
    }

    private static function removeUnusedFields(string $tableName): void
    {
        $sql = rex_sql::factory();
        $sql->setQuery('SELECT * FROM rex_yform_field WHERE table_name = :table_name and ktablemanager_tmp_updated = 0', [
            'table_name' => $tableName
        ]);
        $fields = $sql->getArray();
        $ids = array_map(function ($field) {
            return $field['id'];
        }, $fields);

        // remove all unused fields
        if (count($ids)) {
            $sql = rex_sql::factory();
            $sql->setQuery("DELETE FROM rex_yform_field WHERE id IN (" . $sql->in($ids) . ")");
        }
        // reset all updated fields
        $sql = rex_sql::factory();
        $sql->setQuery('UPDATE rex_yform_field SET ktablemanager_tmp_updated = 0 WHERE table_name = :table_name', [
            'table_name' => $tableName
        ]);
    }
}
