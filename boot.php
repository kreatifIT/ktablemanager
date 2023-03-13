<?php

use KTableManager\TableManager;

if (\rex::isBackend() && \rex::getUser()) {
    \rex_extension::register('YFORM_DATA_LIST_LINKS', [TableManager::class, 'ext__addSynchTableButton']);
}
