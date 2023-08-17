<?php

namespace KTableManager;

use rex_extension;
use rex_extension_point;
use rex_file;

class Discovery
{
    /**
     * @var array $paths
     */

    public static function getTableManagerFilenames(): array
    {
        return rex_extension::registerPoint(new rex_extension_point('KREATIF_TABLEMANAGER_PATHS', $paths));
    }

    /**
     * @param string $tableName
     * @return string[]
     */
    public static function getTableManagerFiles(string $tableName): array
    {
        $files = [];
        foreach (self::getTableManagerFilenames() as $path) {
            $fileName = ltrim($tableName, 'rex_');
            $fileName = $path . '/' . $fileName . '.php';

            if ($file = rex_file::get($fileName)) {
                $files[] = $fileName;
            }
        }
        return $files;
    }

    public static function executeTableManagers(string $tableName): bool
    {
        $files = static::getTableManagerFiles($tableName);
        if(count($files) > 0) {
            foreach($files as $file) {
                include_once $file;
            }
            return true;
        }
        return false;
    }

}
