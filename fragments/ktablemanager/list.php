<?php
/**
 * @var rex_fragment $this
 * @psalm-scope-this rex_fragment
 */

/** @var array $tableManagers */
$tableManagers = $this->getVar('managers');
$tableDataset = null;
?>

<div class="panel panel-default">
    <table class="table table-striped">
        <thead>
        <tr>
            <th><?=rex_i18n::msg('label.ktablemanager.name')?></th>
            <th><?=rex_i18n::msg('label.ktablemanager.table_name')?></th>
            <th><?=rex_i18n::msg('label.ktablemanager.actions')?></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($tableManagers as $tm): ?>
            <?php
            try {
                $tableDataset = rex_yform_manager_table::get($tm);
            } catch (rex_sql_exception $e) {
            }
            ?>
            <tr>
                <td data-title="<?=rex_i18n::msg('label.ktablemanager.name')?>"><?=$tableDataset ? rex_i18n::translate($tableDataset->getName()) : '<i>'.rex_i18n::msg('label.ktablemanager.table_not_created_yet'). '</i>'?></td>
                <td data-title="<?=rex_i18n::msg('label.ktablemanager.table_name')?>"><?=$tm?></td>
                <td data-title="<?=rex_i18n::msg('label.ktablemanager.actions')?>">
                    <a href="<?= rex_url::backendPage(
                        'ktablemanager',
                        ['synch_table' => $tm])?>"><?=rex_i18n::msg('label.ktablemanager.synch_table')?></a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>


</div>
