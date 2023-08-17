<?php


use KTableManager\Discovery;

echo rex_view::title($this->getProperty('page')['title']);

if($tableName = rex_get('synch_table', 'string')) {
    if (Discovery::executeTableManagers($tableName)) {
        $msg = rex_i18n::msg('label.ktablemanager.table_configuration_synched_specific');
        $msg = str_replace('{{TABLE}}',$tableName, $msg);
        echo "<div class='alert alert-success'>$msg</div>";
    } else {
        $msg = rex_i18n::msg('label.ktablemanager.table_configuration_file_not_found_specific');
        $msg = str_replace('{{TABLE}}',$tableName, $msg);
        echo "<div class='alert alert-danger'>$msg</div>";
    }
}

$fragment = new rex_fragment();
$fragment->setVar('managers',  Discovery::getPossibleTableManagers());
echo $fragment->parse('ktablemanager/list.php');
