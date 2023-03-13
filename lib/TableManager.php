<?php

namespace KTableManager;

use rex;
use rex_clang;
use yform\usability\Usability;

class TableManager
{
    public bool $allowsInserts = false;
    private array $fields = [];
    private string $table;

    private int $row = 0;
    private int $col = 0;
    private int $langTabs = 0;
    private int $fieldset = 0;
    private static array $paths = [];
    private static array $tables = [];

    public function __construct(string $table)
    {
        $this->table = rex::getTable(ltrim($table, 'rex_'));
        $this->clearFieldSchema($table);
        static::$tables[$table] = $this;
    }

    /**
     * @param string    $name
     * @param string    $label
     * @param null|bool $search
     * @param null|bool $listHidden
     * @param array     $options
     * @return void
     */
    public function addCheckboxField(
        string $name,
        string $label,
        ?bool $search = null,
        ?bool $listHidden = null,
        array $options = []
    ): void {
        $this->addField(
            $name,
            'checkbox',
            $label,
            $search,
            $listHidden,
            array_merge([
                'db_type' => 'tinyint(1)',
            ], $options)
        );
    }

    /**
     * @param string    $name
     * @param string    $label
     * @param null|bool $search
     * @param null|bool $listHidden
     * @param array     $options
     * @return void
     */
    public function addChoiceField(
        string $name,
        string $label,
        ?bool $search = null,
        ?bool $listHidden = null,
        array $options = []
    ): void {
        $this->addField(
            $name,
            'choice',
            $label,
            $search,
            $listHidden,
            array_merge([
                'db_type' => 'text',
            ], $options)
        );
    }

    /**
     * @param int      $size
     * @param callable $fields
     * @return void
     */
    public function addColumn(int $size, callable $fields)
    {
        $this->col++;
        $this->fields[] = [
            'fieldName' => "row{$this->row}__col_{$this->col}_start",
            'typeName' => 'html',
            'createValues' => [
                'list_hidden' => 1,
                'search' => 0,
                'label' => '',
            ],
            'updateValues' => [
                'db_type' => 'none',
                'html' => '<div class="col-lg-'.$size.'">',
            ],
        ];
        $fields();
        $this->fields[] = [
            'fieldName' => "row{$this->row}__col_{$this->col}_end",
            'typeName' => 'html',
            'createValues' => [
                'list_hidden' => 1,
                'search' => 0,
                'label' => '',
            ],
            'updateValues' => [
                'db_type' => 'none',
                'html' => '</div>',
            ],
        ];
    }

    /**
     * @param string    $name
     * @param string    $label
     * @param null|bool $search
     * @param null|bool $listHidden
     * @param array     $options
     * @return void
     */
    public function addDataDumpField(
        string $name,
        string $label,
        ?bool $search = null,
        ?bool $listHidden = null,
        array $options = []
    ): void {
        $this->addField(
            $name,
            'data_dump',
            $label,
            $search,
            $listHidden,
            array_merge([
                'db_type' => 'text',
            ], $options)
        );
    }

    /**
     * @param string    $name
     * @param string    $label
     * @param null|bool $search
     * @param null|bool $listHidden
     * @param array     $options
     * @return void
     */
    public function addDateTimeField(
        string $name,
        string $label,
        ?bool $search = null,
        ?bool $listHidden = null,
        array $options = []
    ): void {
        $this->addField(
            $name,
            'datestamp',
            $label,
            $search,
            $listHidden,
            array_merge([
                'format' => 'Y-m-d H:i:s',
                'db_type' => 'datetime',
            ], $options)
        );
    }

    /**
     * @param string    $name
     * @param string    $label
     * @param null|bool $search
     * @param null|bool $listHidden
     * @param array     $options
     * @return void
     */
    public function addEmailField(
        string $name,
        string $label,
        ?bool $search = null,
        ?bool $listHidden = null,
        array $options = []
    ): void {
        $this->addField(
            $name,
            'email',
            $label,
            $search,
            $listHidden,
            array_merge([
                'db_type' => 'varchar(191)',
            ], $options)
        );
    }

    /**
     * @param string    $name
     * @param string    $type
     * @param string    $label
     * @param null|bool $search
     * @param null|bool $listHidden
     * @param array     $options
     * @return void
     */
    public function addField(
        string $name,
        string $type,
        string $label,
        ?bool $search,
        ?bool $listHidden,
        array $options = []
    ) {
        $index = $this->getIndex($name);
        // exists already
        if (is_int($index)) {
            return;
        }

        $this->fields[] = [
            'fieldName' => $name,
            'typeName' => $type,
            'createValues' => [],
            'updateValues' => array_merge($options, [
                'label' => $label,
                'list_hidden' => $listHidden ?? true,
                'search' => $search ?? false,
            ]),
        ];
    }

    public function addValidateField(
        string $name,
        string $type,
        array $options = []
    ) {
        $this->fields[] = [
            'yformType' => 'validate',
            'fieldName' => $name,
            'typeName' => $type,
            'createValues' => [],
            'updateValues' => $options,
        ];
    }

    /**
     * @param string $label
     * @return void
     */
    public function addFieldset(string $label)
    {
        $this->fields[] = [
            'fieldName' => "fieldset_{$this->fieldset}",
            'typeName' => 'fieldset',
            'createValues' => [
                'list_hidden' => 1,
                'search' => 0,
            ],
            'updateValues' => [
                'db_type' => 'none',
                'label' => $label,
            ],
        ];
        $this->fieldset++;
    }

    /**
     * @param string    $name
     * @param string    $label
     * @param null|bool $search
     * @param null|bool $listHidden
     * @return void
     */
    public function addImageField(string $name, string $label, ?bool $search = null, ?bool $listHidden = null): void
    {
        $this->addField($name, 'be_media', $label, $search, $listHidden, [
            'preview' => true,
            'db_type' => 'varchar(191)',
        ]);
    }

    /**
     * @param string    $name
     * @param string    $label
     * @param null|bool $search
     * @param null|bool $listHidden
     * @param array     $options
     * @return void
     */
    public function addIntegerField(
        string $name,
        string $label,
        ?bool $search = null,
        ?bool $listHidden = null,
        array $options = []
    ): void {
        $this->addField(
            $name,
            'integer',
            $label,
            $search,
            $listHidden,
            array_merge([
                'db_type' => 'int',
            ], $options)
        );
    }

    /**
     * @param callable $fields
     * @return void
     */
    public function addLangFields(callable $fields)
    {
        $this->langTabs++;

        $this->fields[] = [
            'fieldName' => "tab_start_{$this->langTabs}",
            'typeName' => 'lang_tabs',
            'createValues' => [
                'list_hidden' => 1,
                'search' => 0,
                'label' => '',
            ],
            'updateValues' => [
                'db_type' => 'none',
                'partial' => 'start',
            ],
        ];
        $idx = 0;
        foreach (rex_clang::getAll() as $clang) {
            if ($idx > 0) {
                $breakIndex = $idx - 1;

                $this->fields[] = [
                    'fieldName' => "tab_break_{$breakIndex}_{$this->langTabs}",
                    'typeName' => 'lang_tabs',
                    'createValues' => [
                        'list_hidden' => 1,
                        'search' => 0,
                        'label' => '',
                    ],
                    'updateValues' => [
                        'db_type' => 'none',
                        'partial' => 'break',
                    ],
                ];
            }
            $fields($clang->getId());
            $idx++;
        }

        $this->fields[] = [
            'fieldName' => "tab_end_{$this->langTabs}",
            'typeName' => 'lang_tabs',
            'createValues' => [
                'list_hidden' => 1,
                'search' => 0,
                'label' => '',
            ],
            'updateValues' => [
                'db_type' => 'none',
                'partial' => 'end',
            ],
        ];
    }

    /**
     * @param string      $name
     * @param string      $label
     * @param null|int    $defaultZoom
     * @param null|string $defaultPoint
     * @param null|string $type
     * @param null|bool   $search
     * @param null|bool   $listHidden
     * @return void
     */
    public function addMapField(
        string $name,
        string $label,
        ?int $defaultZoom = null,
        ?string $defaultPoint = null,
        ?string $type = null,
        ?bool $search = null,
        ?bool $listHidden = null
    ): void {
        $this->addField($name, 'map', $label, $search, $listHidden, [
            'default_zoom' => $defaultZoom ?: 14,
            'default_point' => $defaultPoint ?: '46.4776762,11.33599039',
            'type' => $type ?: 'Point',
            'db_type' => 'text',
        ]);
    }

    /**
     * @param string    $name
     * @param string    $label
     * @param null|bool $search
     * @param null|bool $listHidden
     * @param array     $options
     * @return void
     */
    public function addMediaField(
        string $name,
        string $label,
        ?bool $search = null,
        ?bool $listHidden = null,
        array $options = []
    ): void {
        $this->addField(
            $name,
            'be_media',
            $label,
            $search,
            $listHidden,
            array_merge([
                'preview' => false,
                'db_type' => 'text',
            ], $options)
        );
    }

    /**
     * @param string    $name
     * @param string    $label
     * @param string    $table
     * @param string    $field
     * @param string    $relationTable
     * @param null|bool $required
     * @param null|bool $search
     * @param null|bool $listHidden
     * @param array     $options
     * @return void
     */
    public function addMultiRelation(
        string $name,
        string $label,
        string $table,
        string $field,
        string $relationTable,
        ?bool $required = null,
        ?bool $search = null,
        ?bool $listHidden = null,
        array $options = []
    ) {
        $this->addField(
            $name,
            'be_manager_relation',
            $label,
            $search,
            $listHidden,
            array_merge([
                'db_type' => 'int',
                'table' => $table,
                'empty_option' => !$required ?? true,
                'field' => $field,
                'relation_table' => $relationTable,
                'type' => 3,
            ], $options)
        );
    }

    /**
     * @param bool   $langId
     * @param string $fieldName
     * @return void
     */
    public function addNameField($langId = false, string $fieldName = 'name'): void
    {
        $listHidden = 0;
        $search = 1;

        if (is_int($langId)) {
            $fieldName .= "_$langId";
            $listHidden = $langId == 1 ? 0 : 1;
            $search = $langId == 1 ? 1 : 0;
        }

        $this->fields[] = [
            'fieldName' => $fieldName,
            'typeName' => 'text',
            'createValues' => [
                'list_hidden' => $listHidden,
                'search' => $search,
                'label' => 'translate:label.designation',
                'db_type' => 'varchar(191)',
            ],
        ];
    }

    /**
     * @param string    $name
     * @param string    $label
     * @param null|bool $search
     * @param null|bool $listHidden
     * @param array     $options
     * @return void
     */
    public function addNumberField(
        string $name,
        string $label,
        ?bool $search = null,
        ?bool $listHidden = null,
        array $options = []
    ): void {
        $this->addField(
            $name,
            'number',
            $label,
            $search,
            $listHidden,
            array_merge([
                'scale' => 2,
                'precision' => 10,
            ], $options)
        );
    }

    /**
     * @param callable $fields
     * @return void
     */
    public function addRow(callable $fields)
    {
        $this->col = 0;
        $this->row++;

        $this->fields[] = [
            'fieldName' => "row{$this->row}_start",
            'typeName' => 'html',
            'createValues' => [
                'list_hidden' => 1,
                'search' => 0,
                'label' => '',
            ],
            'updateValues' => [
                'db_type' => 'none',
                'html' => '<div class="row">',
            ],
        ];

        $fields();

        $this->fields[] = [
            'fieldName' => "row{$this->row}_end",
            'typeName' => 'html',
            'createValues' => [
                'list_hidden' => 1,
                'search' => 0,
                'label' => '',
            ],
            'updateValues' => [
                'db_type' => 'none',
                'html' => '</div>',
            ],
        ];
    }

    /**
     * @param string    $name
     * @param string    $label
     * @param string    $table
     * @param string    $field
     * @param null|bool $required
     * @param null|bool $search
     * @param null|bool $listHidden
     * @param array     $options
     * @return void
     */
    public function addSingleRelation(
        string $name,
        string $label,
        string $table,
        string $field,
        ?bool $required = null,
        ?bool $search = null,
        ?bool $listHidden = null,
        array $options = []
    ) {
        $this->addField(
            $name,
            'be_manager_relation',
            $label,
            $search,
            $listHidden,
            array_merge([
                'db_type' => 'int',
                'empty_option' => !$required ?? true,
                'table' => $table,
                'field' => $field,
                'type' => 2,
            ], $options)
        );
    }

    /**
     * @param string    $name
     * @param string    $label
     * @param string    $class
     * @param null|bool $search
     * @param null|bool $listHidden
     * @param array     $options
     * @return void
     */
    public function addTextareaField(
        string $name,
        string $label,
        string $class = '',
        ?bool $search = null,
        ?bool $listHidden = null,
        array $options = []
    ) {
        $this->addField(
            $name,
            'textarea',
            $label,
            $search,
            $listHidden,
            array_merge([
                'db_type' => 'text',
                'attributes' => json_encode([
                    'class' => $class.' form-control',
                    'rows' => 3,
                ]),
            ], $options)
        );
    }

    /**
     * @param        $langId
     * @param string $fieldName
     * @return void
     */
    public function addTitleField($langId = false, string $fieldName = 'title'): void
    {
        $this->addNameField($langId, $fieldName);
    }

    /**
     * @param string    $name
     * @param string    $label
     * @param null|bool $search
     * @param null|bool $listHidden
     * @param array     $options
     * @return void
     */
    public function addTextField(
        string $name,
        string $label,
        ?bool $search = null,
        ?bool $listHidden = null,
        array $options = []
    ): void {
        $this->addField(
            $name,
            'text',
            $label,
            $search,
            $listHidden,
            array_merge([
                'db_type' => 'varchar(191)',
            ], $options)
        );
    }

    /**
     * @return void
     */
    public function createDateFields()
    {
        $this->fields[] = [
            'fieldName' => "createdate",
            'typeName' => 'datestamp',
            'createValues' => [
                'list_hidden' => 0,
                'search' => 0,
                'show_value' => 1,
                'label' => 'translate:created_at',
            ],
            'updateValues' => [
                'db_type' => 'datetime',
                'format' => 'Y-m-d H:i:s',
                'only_empty' => 1,
                'no_db' => 0,
            ],
        ];

        $this->fields[] = [
            'fieldName' => "updatedate",
            'typeName' => 'datestamp',
            'createValues' => [
                'list_hidden' => 1,
                'search' => 0,
                'show_value' => 1,
                'label' => 'translate:updated_at',
            ],
            'updateValues' => [
                'db_type' => 'datetime',
                'format' => 'Y-m-d H:i:s',
                'only_empty' => 0,
                'no_db' => 0,
            ],
        ];
    }

    /**
     * @param string $fields
     * @return void
     */
    public function createPriorityField(string $fields = 'name_1')
    {
        $this->fields[] = [
            'fieldName' => "prio",
            'typeName' => 'prio',
            'createValues' => [
                'label' => 'translate:label.priority',
                'fields' => $fields,
            ],
            'updateValues' => [
                'db_type' => 'int',
                'list_hidden' => 1,
                'search' => 0,
            ],
        ];
    }

    /**
     * @return void
     */
    public function createStatusField($default = 1)
    {
        $this->fields[] = [
            'fieldName' => "status",
            'typeName' => 'choice',
            'createValues' => [
                'list_hidden' => 1,
                'search' => 0,
                'label' => 'translate:status',
            ],
            'updateValues' => [
                'db_type' => 'int',
                'expanded' => 0,
                'multiple' => 0,
                'default' => $default,
                'choices' => json_encode(
                    [
                        'translate:active' => 1,
                        'translate:inactive' => 0,
                    ]
                ),
            ],
        ];
    }

    /**
     * @return void
     */
    public function createUserFields()
    {
        $this->fields[] = [
            'fieldName' => "createuser",
            'typeName' => 'be_user',
            'createValues' => [
                'label' => 'translate:created_by',
            ],
            'updateValues' => [
                'db_type' => 'varchar(191)',
                'list_hidden' => 1,
                'search' => 0,
                'only_empty' => 1,
                'show_value' => 1,
            ],
        ];

        $this->fields[] = [
            'fieldName' => "updateuser",
            'typeName' => 'be_user',
            'createValues' => [
                'label' => 'translate:updated_by',
            ],
            'updateValues' => [
                'db_type' => 'varchar(191)',
                'list_hidden' => 1,
                'search' => 0,
                'only_empty' => 0,
                'show_value' => 1,
            ],
        ];
    }

    /**
     * @param callable $fields
     * @return void
     */
    public function forEachLang(callable $fields)
    {
        foreach (\rex_clang::getAll() as $clang) {
            $fields($clang->getId());
        }
    }

    /**
     * @param $fieldName
     * @return false|int|string
     */
    public function getIndex($fieldName)
    {
        return array_search($fieldName, array_column($this->fields, 'fieldName'));
    }

    /**
     * @param string   $field
     * @param callable $fields
     * @param          $clangId
     * @throws \Exception
     * @return void
     */
    public function insertAfter(string $field, callable $fields, $clangId = null)
    {
        if (!$this->allowsInserts) {
            throw new \Exception('Inserts are not allowed in this context');
        }

        $fieldsCopy = $this->fields;
        $indexFromField = $this->getIndex($field);

        $this->fields = [];
        $fields($clangId);
        $this->fields = array_merge(
            array_slice($fieldsCopy, 0, $indexFromField + 1),
            $this->fields,
            array_slice(
                $fieldsCopy,
                $indexFromField + 1
            )
        );
    }

    /**
     * @param string   $field
     * @param callable $fields
     * @throws \Exception
     * @return void
     */
    public function insertAfterLangField(string $field, callable $fields)
    {
        if (!$this->allowsInserts) {
            throw new \Exception('Inserts are not allowed in this context');
        }
        foreach (\rex_clang::getAll() as $clang) {
            $this->insertAfter($field.'_'.$clang->getId(), $fields, $clang->getId());
        }
    }

    /**
     * @param string $name
     * @throws \rex_sql_exception
     * @return void
     */
    public function removeField(string $name)
    {
        $index = $this->getIndex($name);
        if ($index === false) {
            return;
        } else {
            $sql = \rex_sql::factory();
            $sql->setTable('rex_yform_field');
            $sql->setWhere('table_name = :tname AND name = :fname', [
                'tname' => $this->table,
                'fname' => $name,
            ]);
            $sql->delete();

            unset($this->fields[$index]);
            // workaround repair indexes
            $this->fields = array_values($this->fields);
        }
    }

    /**
     * @throws \rex_sql_exception
     * @return void
     */
    public function synchronize(): void
    {
        foreach ($this->fields as $key => $field) {
            $yformType = $field['yformType'] ?: 'value';
            $fieldName = $field['fieldName'];
            $typeName = $field['typeName'];
            $createValues = $field['createValues'] ?: [];
            $updateValues = $field['updateValues'] ?: [];
            $updateValues = array_merge($updateValues, ['prio' => $key]);

            if ('validate' == $yformType) {
                Usability::ensureValidateField($this->table, $fieldName, $typeName, $createValues, $updateValues);
            } else {
                Usability::ensureValueField($this->table, $fieldName, $typeName, $createValues, $updateValues);
            }
        }

        \rex_yform_manager_table_api::generateTableAndFields(\rex_yform_manager_table::get($this->table));
    }

    /**
     * @param string $tableName
     * @throws \rex_sql_exception
     * @return void
     */
    public static function clearFieldSchema(string $tableName)
    {
        $tableName = \rex::getTablePrefix().ltrim($tableName, 'rex_');
        $table = \rex_yform_manager_table::get($tableName);
        $sql = \rex_sql::factory();
        $query = "DELETE FROM rex_yform_field WHERE table_name = :tname";
        $sql->setQuery($query, ['tname' => $tableName]);
        $table->deleteCache();
        $sql->execute();
    }

    /**
     * @param string $table
     * @return null|static
     */
    public static function extendTableManager(string $table): ?self
    {
        $tm = static::$tables[$table];
        $tm->allowsInserts = true;
        return $tm;
    }

    /**
     * @param \rex_extension_point $ep
     * @throws \rex_exception
     * @return void
     */
    public static function ext__addSynchTableButton(\rex_extension_point $ep)
    {
        $synchParam = 'synch';
        $subject = $ep->getSubject();
        $table = $ep->getParam('table');

        $subject['table_links'][] = [
            'label' => \rex_i18n::msg('label.synch_table'),
            'url' => \rex_url::backendPage(
                'yform/manager/data_edit',
                ['table_name' => $table->getTableName(), $synchParam => 1]
            ),
            'attributes' => [
                'class' => ['btn btn-default'],
            ],
        ];
        if (rex_get($synchParam, 'int', 0)) {
            $paths = TableManager::$paths;
            $paths = \rex_extension::registerPoint(new \rex_extension_point('KREATIF_TABLEMANAGER_PATHS', $paths));
            $found = false;
            foreach ($paths as $path) {
                $fileName = ltrim($table->getTableName(), 'rex_');
                $fileName = $path.'/'.$fileName.'.php';

                if (\rex_file::get($fileName)) {
                    include_once $fileName;
                    $synchText = \rex_i18n::msg('label.table_configuration_synched');
                    echo "<div class='alert alert-success'>$synchText</div>";
                    $found = true;
                }
            }
            if (!$found) {
                $fileNotFoundText = \rex_i18n::msg('label.table_configuration_file_not_found');
                echo "<div class='alert alert-danger'>$fileNotFoundText</div>";
            }
        }
        $ep->setSubject($subject);
    }
}
