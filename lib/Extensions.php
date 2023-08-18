<?php

namespace KTableManager;

use rex_exception;
use rex_extension;
use rex_extension_point;
use rex_i18n;
use rex_url;

class Extensions
{

    const SYNCH_PARAM = 'synch';

    public static function init(): void
    {
        rex_extension::register('YFORM_DATA_LIST_LINKS', [static::class, 'ext__addSynchTableButton']);
    }

    /**
     * @param rex_extension_point<rex_extension> $ep
     * @return void
     * @throws rex_exception
     */
    public static function ext__addSynchTableButton(rex_extension_point $ep): void
    {
        $subject = $ep->getSubject();
        $table = $ep->getParam('table');

        $subject['table_links'][] = [
            'label' => rex_i18n::msg('label.ktablemanager.synch_table'),
            'url' => rex_url::backendPage(
                'yform/manager/data_edit',
                ['table_name' => $table->getTableName(), static::SYNCH_PARAM => 1]
            ),
            'attributes' => [
                'class' => ['btn btn-default'],
            ],
        ];
        if (rex_get(static::SYNCH_PARAM, 'int', 0)) {
            if (Discovery::executeTableManagers($table->getTableName())) {
                $msg = rex_i18n::msg('label.ktablemanager.table_configuration_synched');
                echo "<div class='alert alert-success'>$msg</div>";
            } else {
                $msg = rex_i18n::msg('label.ktablemanager.table_configuration_file_not_found');
                echo "<div class='alert alert-danger'>$msg</div>";
            }
        }
        $ep->setSubject($subject);
    }
}
