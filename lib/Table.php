<?php

namespace KTableManager;

use rex;
use rex_sql;
use rex_sql_exception;
use rex_yform_manager_table;
use rex_yform_manager_table_api;
use yform\usability\Usability;

class Table
{
    public static function clearFieldSchema(string $tableName): void
    {
        $tableName = rex::getTablePrefix() . ltrim($tableName, 'rex_');
        $table = rex_yform_manager_table::get($tableName);
        $sql = rex_sql::factory();
        $query = "DELETE FROM rex_yform_field WHERE table_name = :tname";
        $sql->setQuery($query, ['tname' => $tableName]);
        $table->deleteCache();
        $sql->execute();
    }

    /**
     * @throws rex_sql_exception
     */
    public static function getTableDataset(string $table): array
    {
        return rex_sql::factory()->setTable(rex::getTablePrefix() . 'yform_table')->setWhere('table_name = :name', [
            'name' => $table])->select()->getArray()[0];
    }

    /**
     * @throws rex_sql_exception
     */
    public static function getHighestPrio(): int
    {
        return rex_sql::factory()->setTable(rex::getTablePrefix() . 'yform_table')->select('MAX(prio) as _max')->getArray()[0]['_max'];
    }

    /**
     * @throws rex_sql_exception
     */
    public static function ensureTableConfig(string $table, array $config): void {
        $tableDataset = self::getTableDataset($table);
        $sql = rex_sql::factory();
        $sql->setTable(rex::getTablePrefix() . 'yform_table');
        $sql->setValues($config);
        if($tableDataset) {
            $sql->setWhere('id = :id', [
                'id' => $tableDataset['id']
            ]);
            $sql->update();
        } else {
            $sql->insert();
        }
    }

    /**
     * @throws rex_sql_exception
     */
    public static function ensureFields(string $table, array $fields): void
    {
        foreach ($fields as $key => $field) {
            $yformType = $field['yformType'] ?? 'value';
            $fieldName = $field['fieldName'];
            $typeName = $field['typeName'];
            $createValues = $field['createValues'] ?: [];
            $updateValues = $field['updateValues'] ?: [];
            $updateValues = array_merge($updateValues, ['prio' => $key]);

            if ('validate' == $yformType) {
                Usability::ensureValidateField($table, $fieldName, $typeName, $createValues, $updateValues);
            } else {
                Usability::ensureValueField($table, $fieldName, $typeName, $createValues, $updateValues);
            }
        }

        rex_yform_manager_table_api::generateTableAndFields(rex_yform_manager_table::get($table));
    }

    /**
     * @throws rex_sql_exception
     */
    public static function removeField(string $table, string $fieldName): void
    {
        $sql = rex_sql::factory();
        $sql->setTable('rex_yform_field');
        $sql->setWhere('table_name = :tname AND name = :fname', [
            'tname' => $table,
            'fname' => $fieldName,
        ]);
        $sql->delete();
    }
}
