<?php


use KTableManager\Discovery;

echo rex_view::title($this->getProperty('page')['title']);

if($tableName = rex_get('synch_table', 'string')) {
    if (Discovery::executeTableManagers($tableName)) {
        $msg = rex_i18n::msg('label.table_configuration_synched');
        echo "<div class='alert alert-success'>$msg</div>";
    } else {
        $msg = rex_i18n::msg('label.table_configuration_file_not_found');
        echo "<div class='alert alert-danger'>$msg</div>";
    }
}

$fragment = new rex_fragment();
$fragment->setVar('managers',  Discovery::getPossibleTableManagers());
echo $fragment->parse('ktablemanager/list.php');
