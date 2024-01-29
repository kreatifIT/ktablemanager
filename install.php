<?php

// field is used temporarily to get all updated fields + tables
// after Tablemanager syncs all fields for updated tables, who are not updated will be deleted
rex_sql_table::get(rex::getTable('yform_field'))
    ->ensurePrimaryIdColumn()
    ->ensureColumn(new rex_sql_column('ktablemanager_tmp_updated', 'tinyint(1)'))
    ->ensure();
