<?php

use KTableManager\Extensions;

if (!rex::isBackend()) {
    return;
}
if (!rex::getUser()) {
    return;
}

Extensions::init();
