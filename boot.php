<?php

use KTableManager\TableManager;
if (!rex::isBackend()) {
    return;
}
if (!rex::getUser()) {
    return;
}
rex_extension::register('YFORM_DATA_LIST_LINKS', [TableManager::class, 'ext__addSynchTableButton']);
